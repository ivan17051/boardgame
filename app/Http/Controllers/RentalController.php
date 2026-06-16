<?php

namespace App\Http\Controllers;

use App\Models\AdditionalItem;
use App\Models\CashFlow;
use App\Models\Meja;
use App\Models\Rental;
use App\Models\RentalAdditionalItem;
use App\Models\RentalPromo;
use App\Models\Toko;
use App\Support\RentalCheckout;
use App\Support\RentalInvoice;
use App\Support\RentalPayment;
use App\Support\TokoScope;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RentalController extends Controller
{
    public function index()
    {
        $tokos = TokoScope::scopeTokos(Toko::query())
            ->with([
                'meja' => function ($q) {
                    $q->orderBy('nama')->with('activeRental');
                },
            ])
            ->orderBy('nama')
            ->get();

        $additionalItems = collect();
        if (Schema::hasTable('m_additional_item')) {
            $query = TokoScope::scopeAdditionalItems(AdditionalItem::query())
                ->active()
                ->orderBy('nama');

            $additionalItems = TokoScope::canSeeAll()
                ? $query->get(['id', 'id_toko', 'nama', 'harga'])
                : $query->get(['id', 'nama', 'harga']);
        }

        $rentalPromos = collect();
        if (Schema::hasTable('m_rental_promo')) {
            $rentalPromos = TokoScope::scopeRentalPromos(RentalPromo::query())
                ->activeAt(now())
                ->orderBy('nama')
                ->get(['id', 'id_toko', 'nama', 'promo_hourly_rate', 'promo_duration_limit', 'jam_mulai', 'jam_selesai', 'tgl_awal', 'tgl_akhir']);
        }

        return view('rental.index', compact('tokos', 'additionalItems', 'rentalPromos'));
    }

    public function invoice(Rental $rental)
    {
        TokoScope::authorizeRental($rental);

        if (! RentalInvoice::canIssue($rental)) {
            abort(404);
        }

        $invoice = RentalInvoice::build($rental);

        return view('cashflow.invoice', $invoice);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_meja' => ['required', 'integer', 'exists:m_meja,id'],
            'nama_customer' => ['required', 'string', 'max:255'],
            'tipe_customer' => ['required', Rule::in([
                RentalCheckout::CUSTOMER_MEMBER,
                RentalCheckout::CUSTOMER_NON_MEMBER,
            ])],
            'id_promo' => ['nullable', 'integer'],
        ]);

        DB::transaction(function () use ($validated) {
            $meja = Meja::query()
                ->whereKey($validated['id_meja'])
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if (! $meja) {
                throw ValidationException::withMessages([
                    'id_meja' => ['Meja tidak tersedia atau sedang dipakai.'],
                ]);
            }

            TokoScope::authorizeMeja($meja);

            $now = now();
            $rate = RentalCheckout::rateForMeja($meja, $validated['tipe_customer']);

            Rental::query()->create(array_merge([
                'id_meja' => $meja->id,
                'nama_customer' => $validated['nama_customer'],
                'tipe_customer' => $validated['tipe_customer'],
                'waktu_start' => $now,
                'waktu_end' => null,
                'total_durasi' => null,
                'harga' => $rate,
                'total_harga' => null,
                'total_harga_sewa' => null,
                'total_harga_additional' => null,
                'status' => 'active',
            ], $this->promoFieldsForRental($meja, $validated['id_promo'] ?? null, now())));

            $meja->update(['status' => 'rented']);
        });

        return response()->json(['message' => 'Check-in berhasil. Meja disewa.']);
    }

    public function checkoutPreview(Request $request, Rental $rental): JsonResponse
    {
        if (! $rental->isActive()) {
            abort(404);
        }

        TokoScope::authorizeRental($rental);
        $rental->loadMissing('meja.toko');

        $validated = $request->validate([
            'ended_at' => ['nullable', 'integer'],
            'additional_items' => ['nullable', 'array'],
            'additional_items.*.id' => ['required', 'integer'],
            'additional_items.*.qty' => ['required', 'integer', 'min:1', 'max:999'],
        ]);

        $endAt = RentalCheckout::resolveEndTime($rental, $validated['ended_at'] ?? $request->query('ended_at'));
        $calc = RentalCheckout::computeTotals(
            $rental,
            $endAt,
            $validated['additional_items'] ?? []
        );

        return response()->json($this->checkoutPayload($rental, $endAt, $calc));
    }

    public function checkout(Request $request, Rental $rental): JsonResponse
    {
        if (! $rental->isActive()) {
            abort(404);
        }

        TokoScope::authorizeRental($rental);

        $validated = $this->validateCheckoutRequest($request);

        $paymentPayload = [
            'metode_pembayaran' => $validated['metode_pembayaran'] ?? null,
            'jumlah_bayar' => array_key_exists('jumlah_bayar', $validated) && $validated['jumlah_bayar'] !== null
                ? (float) $validated['jumlah_bayar']
                : null,
            'bukti' => $request->file('bukti'),
        ];

        $result = DB::transaction(function () use ($rental, $validated, $paymentPayload) {
            $locked = Rental::query()
                ->whereKey($rental->id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                abort(404);
            }

            $endAt = RentalCheckout::resolveEndTime($locked, $validated['ended_at'] ?? null);
            $calc = RentalCheckout::computeTotals(
                $locked,
                $endAt,
                $validated['additional_items'] ?? []
            );

            $locked->update([
                'waktu_end' => $endAt,
                'total_durasi' => $calc['total_minutes'],
                'total_harga_sewa' => $calc['total_harga_sewa'],
                'total_harga_additional' => $calc['total_harga_additional'],
                'total_harga' => $calc['total_harga'],
                'total' => $calc['total_harga'],
                'status' => 'completed',
            ]);

            foreach ($calc['additional_lines'] as $line) {
                RentalAdditionalItem::query()->create([
                    'id_rental' => $locked->id,
                    'id_additional_item' => $line['id'],
                    'nama' => $line['nama'],
                    'harga' => $line['harga'],
                    'qty' => $line['qty'],
                    'subtotal' => $line['subtotal'],
                ]);
            }

            Meja::query()
                ->whereKey($locked->id_meja)
                ->lockForUpdate()
                ->update(['status' => 'active']);

            $this->createIncomeCashFlows($locked, $endAt, $calc);

            if (! empty($paymentPayload['metode_pembayaran']) && $paymentPayload['jumlah_bayar'] !== null) {
                RentalPayment::saveOnRental(
                    $locked,
                    $paymentPayload['metode_pembayaran'],
                    $paymentPayload['jumlah_bayar'],
                    $paymentPayload['bukti'],
                    $endAt
                );
            }

            return [
                'rental_id' => $locked->id,
            ];
        });

        return response()->json([
            'message' => 'Checkout & pembayaran tersimpan. Meja tersedia kembali.',
            'invoice_url' => $this->primaryInvoiceUrl($result['rental_id']),
        ]);
    }

    public function cancel(Rental $rental): JsonResponse
    {
        if (! $rental->isActive()) {
            abort(404);
        }

        TokoScope::authorizeRental($rental);

        DB::transaction(function () use ($rental) {
            $locked = Rental::query()
                ->whereKey($rental->id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                abort(404);
            }

            Meja::query()
                ->whereKey($locked->id_meja)
                ->lockForUpdate()
                ->update(['status' => 'active']);

            $locked->delete();
        });

        return response()->json([
            'message' => 'Sewa dibatalkan dan data rental dihapus.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateCheckoutRequest(Request $request): array
    {
        $additionalItems = $request->input('additional_items');
        if (is_string($additionalItems)) {
            $decoded = json_decode($additionalItems, true);
            $request->merge(['additional_items' => is_array($decoded) ? $decoded : []]);
        }

        $validated = $request->validate([
            'ended_at' => ['nullable', 'integer'],
            'additional_items' => ['nullable', 'array'],
            'additional_items.*.id' => ['required', 'integer'],
            'additional_items.*.qty' => ['required', 'integer', 'min:1', 'max:999'],
            'metode_pembayaran' => ['nullable', 'string', 'max:100', Rule::in(['tunai', 'transfer', 'qris', 'kartu', 'lainnya'])],
            'jumlah_bayar' => ['nullable', 'numeric', 'min:0'],
            'bukti' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,pdf'],
        ], [
            'jumlah_bayar.min' => 'Jumlah bayar minimal 0.',
        ]);

        if (! empty($validated['metode_pembayaran'])) {
            if ($validated['jumlah_bayar'] === null) {
                throw ValidationException::withMessages([
                    'jumlah_bayar' => ['Jumlah bayar wajib diisi jika metode pembayaran dipilih.'],
                ]);
            }
        }

        return $validated;
    }

    private function primaryInvoiceUrl(?int $rentalId): ?string
    {
        if (! $rentalId) {
            return null;
        }

        $rental = Rental::query()->find($rentalId);

        if (! $rental || ! RentalInvoice::canIssue($rental)) {
            return null;
        }

        return route('rental.invoice', $rental);
    }

    public function showBukti(Rental $rental)
    {
        if (empty($rental->bukti_transaksi)) {
            abort(404);
        }

        TokoScope::authorizeRental($rental);

        $disk = \Illuminate\Support\Facades\Storage::disk('public');

        if (! $disk->exists($rental->bukti_transaksi)) {
            abort(404);
        }

        $path = $disk->path($rental->bukti_transaksi);
        $mime = $disk->mimeType($rental->bukti_transaksi) ?: 'application/octet-stream';

        return response()->file($path, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.basename($rental->bukti_transaksi).'"',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function promoFieldsForRental(Meja $meja, ?int $idPromo, CarbonInterface $at): array
    {
        $empty = [
            'id_promo' => null,
            'promo_nama' => null,
            'promo_hourly_rate' => null,
            'promo_duration_limit' => null,
            'promo_jam_mulai' => null,
            'promo_jam_selesai' => null,
            'promo_tgl_awal' => null,
            'promo_tgl_akhir' => null,
        ];

        if (! $idPromo) {
            return $empty;
        }

        $snapshot = RentalCheckout::resolvePromoSnapshot($idPromo, (int) $meja->id_toko, $at, true);
        if (! $snapshot) {
            throw ValidationException::withMessages([
                'id_promo' => ['Promo tidak berlaku saat ini (di luar periode/tanggal atau jam promo) atau tidak aktif untuk toko meja ini.'],
            ]);
        }

        return [
            'id_promo' => $snapshot['id_promo'],
            'promo_nama' => $snapshot['promo_nama'],
            'promo_hourly_rate' => $snapshot['promo_hourly_rate'],
            'promo_duration_limit' => $snapshot['promo_duration_limit'],
            'promo_jam_mulai' => $snapshot['promo_jam_mulai'],
            'promo_jam_selesai' => $snapshot['promo_jam_selesai'],
            'promo_tgl_awal' => $snapshot['promo_tgl_awal'] ?: null,
            'promo_tgl_akhir' => $snapshot['promo_tgl_akhir'] ?: null,
        ];
    }

    /**
     * @param  array<string, mixed>  $calc
     */
    private function checkoutPayload(Rental $rental, CarbonInterface $endAt, array $calc): array
    {
        return [
            'rental_id' => $rental->id,
            'ended_at' => $endAt->getTimestamp(),
            'nama_meja' => $rental->meja->nama,
            'nama_toko' => $rental->meja->toko->nama ?? '',
            'nama_customer' => $rental->nama_customer,
            'tipe_customer' => $rental->tipe_customer,
            'tipe_customer_label' => $rental->isMember() ? 'Member' : 'Non-Member',
            'waktu_start' => $rental->waktu_start->format('d/m/Y H:i:s'),
            'waktu_end' => $endAt->format('d/m/Y H:i:s'),
            'durasi_hms' => $calc['durasi_hms'],
            'durasi_menit' => $calc['total_minutes'],
            'billed_hours' => $calc['billed_hours'],
            'harga_per_jam' => (float) $rental->harga,
            'harga_per_jam_formatted' => number_format((float) $rental->harga, 0, ',', '.'),
            'has_promo' => $rental->hasPromo(),
            'promo_nama' => $rental->promo_nama,
            'promo_hourly_rate' => $rental->hasPromo() ? (float) $rental->promo_hourly_rate : null,
            'promo_duration_limit' => $rental->hasPromo() ? (float) $rental->promo_duration_limit : null,
            'total_harga_sewa' => $calc['total_harga_sewa'],
            'total_harga_sewa_formatted' => number_format($calc['total_harga_sewa'], 0, ',', '.'),
            'total_harga_additional' => $calc['total_harga_additional'],
            'total_harga_additional_formatted' => number_format($calc['total_harga_additional'], 0, ',', '.'),
            'total_harga' => $calc['total_harga'],
            'total_harga_formatted' => number_format($calc['total_harga'], 0, ',', '.'),
            'breakdown_html' => $calc['breakdown_html'],
            'additional_lines' => $calc['additional_lines'],
        ];
    }

    /**
     * @param  array<string, mixed>  $calc
     */
    private function createIncomeCashFlows(Rental $rental, CarbonInterface $endAt, array $calc): void
    {
        $rental->loadMissing('meja.toko');
        $mejaNama = $rental->meja->nama ?? 'Meja';
        $tokoNama = $rental->meja->toko->nama ?? '';
        $deskripsiSewa = $tokoNama !== ''
            ? "Sewa meja {$mejaNama} ({$tokoNama}) — {$rental->nama_customer}"
            : "Sewa meja {$mejaNama} — {$rental->nama_customer}";

        $uid = auth()->id() ?? 0;
        $now = $endAt;

        CashFlow::query()->create([
            'id_rental' => $rental->id,
            'tipe_transaksi' => 'income',
            'kategori_pendapatan' => CashFlow::KATEGORI_SEWA_MEJA,
            'total' => $calc['total_harga_sewa'],
            'keterangan' => $deskripsiSewa,
            'waktu_pembayaran' => $now,
            'idc' => $uid,
            'idm' => $uid,
            'doc' => $now,
            'dom' => $now,
        ]);

        if ($calc['total_harga_additional'] > 0) {
            CashFlow::query()->create([
                'id_rental' => $rental->id,
                'tipe_transaksi' => 'income',
                'kategori_pendapatan' => CashFlow::KATEGORI_ADDITIONAL_FB,
                'total' => $calc['total_harga_additional'],
                'keterangan' => "Additional Item (F&B) — {$rental->nama_customer} · {$mejaNama}",
                'waktu_pembayaran' => $now,
                'idc' => $uid,
                'idm' => $uid,
                'doc' => $now,
                'dom' => $now,
            ]);
        }
    }
}
