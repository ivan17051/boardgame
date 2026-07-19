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
                    $q->orderBy('nama')->with([
                        'activeRental.additionalItems',
                        'activeRental.cashFlows',
                    ]);
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
                ? $query->get(['id', 'id_toko', 'nama', 'harga', 'is_discount'])
                : $query->get(['id', 'nama', 'harga', 'is_discount']);
        }

        $rentalPromos = collect();
        if (Schema::hasTable('m_rental_promo')) {
            $rentalPromos = TokoScope::scopeRentalPromos(RentalPromo::query())
                ->activeOnDate(now())
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

    public function items(Rental $rental): JsonResponse
    {
        if (! $rental->isActive()) {
            abort(404);
        }

        TokoScope::authorizeRental($rental);
        $rental->loadMissing('additionalItems');

        $lines = $rental->additionalItems->map(function (RentalAdditionalItem $row) {
            return [
                'id' => (int) $row->id_additional_item,
                'nama' => $row->nama,
                'harga' => (float) $row->harga,
                'qty' => (int) $row->qty,
                'subtotal' => (float) $row->subtotal,
            ];
        })->values()->all();

        $total = round(array_sum(array_column($lines, 'subtotal')), 3);
        $payment = RentalPayment::additionalPaymentState($rental, $total);

        return response()->json([
            'rental_id' => $rental->id,
            'items' => $lines,
            'additional_total' => $payment['additional_total'],
            'additional_paid' => $payment['additional_paid'],
            'additional_due' => $payment['additional_due'],
            'is_fully_paid' => $payment['is_fully_paid'],
            'metode_pembayaran' => $payment['metode_pembayaran'],
        ]);
    }

    public function syncItems(Request $request, Rental $rental): JsonResponse
    {
        if (! $rental->isActive()) {
            abort(404);
        }

        TokoScope::authorizeRental($rental);

        $validated = $request->validate([
            'additional_items' => ['nullable', 'array'],
            'additional_items.*.id' => ['required', 'integer'],
            'additional_items.*.qty' => ['required', 'integer', 'min:1', 'max:999'],
        ]);

        $lines = RentalCheckout::resolveAdditionalLines($validated['additional_items'] ?? []);
        $total = round(array_sum(array_column($lines, 'subtotal')), 3);

        DB::transaction(function () use ($rental, $lines, $total) {
            $locked = Rental::query()
                ->whereKey($rental->id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->firstOrFail();

            RentalAdditionalItem::query()->where('id_rental', $locked->id)->delete();

            foreach ($lines as $line) {
                RentalAdditionalItem::query()->create([
                    'id_rental' => $locked->id,
                    'id_additional_item' => $line['id'],
                    'nama' => $line['nama'],
                    'harga' => $line['harga'],
                    'qty' => $line['qty'],
                    'subtotal' => $line['subtotal'],
                ]);
            }

            $locked->update(['total_harga_additional' => $total]);

            RentalPayment::syncAdditionalCashFlow($locked, $total);
        });

        $fresh = $rental->fresh();
        $payment = RentalPayment::additionalPaymentState($fresh, $total);

        return response()->json([
            'message' => 'Item tambahan disimpan.',
            'items' => $lines,
            'additional_total' => $payment['additional_total'],
            'additional_paid' => $payment['additional_paid'],
            'additional_due' => $payment['additional_due'],
            'is_fully_paid' => $payment['is_fully_paid'],
            'metode_pembayaran' => $payment['metode_pembayaran'],
        ]);
    }

    public function payItems(Request $request, Rental $rental): JsonResponse
    {
        if (! $rental->isActive()) {
            abort(404);
        }

        TokoScope::authorizeRental($rental);
        $rental->loadMissing('meja');

        if ($request->has('additional_items')) {
            $itemsInput = $request->input('additional_items');
            if (is_string($itemsInput)) {
                $decoded = json_decode($itemsInput, true);
                $request->merge(['additional_items' => is_array($decoded) ? $decoded : []]);
            }
        }

        $validated = $request->validate([
            'additional_items' => ['nullable', 'array'],
            'additional_items.*.id' => ['required', 'integer'],
            'additional_items.*.qty' => ['required', 'integer', 'min:1', 'max:999'],
            'metode_pembayaran' => ['required', 'string', 'max:100', Rule::in(['tunai', 'transfer', 'qris', 'kartu', 'lainnya'])],
            'jumlah_bayar' => ['required', 'numeric', 'min:0'],
            'bukti' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,pdf'],
        ]);

        $result = DB::transaction(function () use ($rental, $request, $validated) {
            $locked = Rental::query()
                ->whereKey($rental->id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->firstOrFail();

            $locked->loadMissing('meja');

            if (array_key_exists('additional_items', $validated)) {
                $lines = RentalCheckout::resolveAdditionalLines($validated['additional_items'] ?? []);
            } else {
                $lines = $locked->additionalItems()->get()->map(function (RentalAdditionalItem $row) {
                    return [
                        'id' => (int) $row->id_additional_item,
                        'nama' => $row->nama,
                        'harga' => (float) $row->harga,
                        'qty' => (int) $row->qty,
                        'subtotal' => (float) $row->subtotal,
                        'is_discount' => (float) $row->subtotal < 0,
                    ];
                })->all();
            }

            $total = round(array_sum(array_column($lines, 'subtotal')), 3);
            if ($total == 0.0 || count($lines) === 0) {
                throw ValidationException::withMessages([
                    'additional_items' => ['Tidak ada item tambahan yang bisa dibayar.'],
                ]);
            }

            $now = now();
            $mejaNama = $locked->meja->nama ?? '';
            $fbLabel = $mejaNama !== ''
                ? "Additional Item (F&B) — {$locked->nama_customer} · {$mejaNama} (dari sewa #{$locked->id})"
                : "Additional Item (F&B) — {$locked->nama_customer} (dari sewa #{$locked->id})";

            // New completed rental row (item-only) so paid F&B appears in Data Sewa.
            $fbRental = Rental::query()->create([
                'id_meja' => null,
                'nama_customer' => $locked->nama_customer,
                'tipe_customer' => $locked->tipe_customer ?? RentalCheckout::CUSTOMER_NON_MEMBER,
                'waktu_start' => $now,
                'waktu_end' => $now,
                'total_durasi' => 0,
                'harga' => 0,
                'id_promo' => null,
                'promo_nama' => null,
                'promo_hourly_rate' => null,
                'promo_duration_limit' => null,
                'promo_jam_mulai' => null,
                'promo_jam_selesai' => null,
                'promo_tgl_awal' => null,
                'promo_tgl_akhir' => null,
                'total_harga_sewa' => 0,
                'total_harga_additional' => $total,
                'total_harga' => $total,
                'total' => $total,
                'status' => 'completed',
                'guest_token' => null,
            ]);

            foreach ($lines as $line) {
                RentalAdditionalItem::query()->create([
                    'id_rental' => $fbRental->id,
                    'id_additional_item' => $line['id'],
                    'nama' => $line['nama'],
                    'harga' => $line['harga'],
                    'qty' => $line['qty'],
                    'subtotal' => $line['subtotal'],
                ]);
            }

            $uid = auth()->id() ?? 0;
            $buktiPath = RentalPayment::storeBukti($request->file('bukti'), null);

            CashFlow::query()->create([
                'id_rental' => $fbRental->id,
                'tipe_transaksi' => 'income',
                'kategori_pendapatan' => CashFlow::KATEGORI_ADDITIONAL_FB,
                'metode_pembayaran' => $validated['metode_pembayaran'],
                'total' => $total,
                'jumlah_bayar' => (float) $validated['jumlah_bayar'],
                'keterangan' => $fbLabel,
                'waktu_pembayaran' => $now,
                'bukti_transaksi' => $buktiPath,
                'idc' => $uid,
                'idm' => $uid,
                'doc' => $now,
                'dom' => $now,
            ]);

            $fbRental->update([
                'metode_pembayaran' => $validated['metode_pembayaran'],
                'jumlah_bayar' => (float) $validated['jumlah_bayar'],
                'bukti_transaksi' => $buktiPath,
                'waktu_pembayaran' => $now,
            ]);

            // Clear paid items from the active meja rental so checkout won't double-charge.
            RentalAdditionalItem::query()->where('id_rental', $locked->id)->delete();
            $locked->update(['total_harga_additional' => 0]);

            CashFlow::query()
                ->where('id_rental', $locked->id)
                ->where('kategori_pendapatan', CashFlow::KATEGORI_ADDITIONAL_FB)
                ->delete();

            return [
                'lines' => [],
                'total' => 0.0,
                'fb_rental_id' => (int) $fbRental->id,
                'paid_total' => $total,
            ];
        });

        return response()->json([
            'message' => 'Pembayaran item tersimpan sebagai transaksi terpisah (Data Sewa #'.$result['fb_rental_id'].'). Sewa meja masih berjalan.',
            'items' => [],
            'additional_total' => 0,
            'additional_paid' => 0,
            'additional_due' => 0,
            'is_fully_paid' => true,
            'metode_pembayaran' => null,
            'fb_rental_id' => $result['fb_rental_id'],
            'paid_total' => $result['paid_total'],
        ]);
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

        $itemsInput = $validated['additional_items'] ?? null;
        if ($itemsInput === null) {
            $itemsInput = $rental->additionalItems()
                ->get(['id_additional_item', 'qty'])
                ->map(function ($row) {
                    return ['id' => (int) $row->id_additional_item, 'qty' => (int) $row->qty];
                })
                ->all();
        }

        $endAt = RentalCheckout::resolveEndTime($rental, $validated['ended_at'] ?? $request->query('ended_at'));
        $calc = RentalCheckout::computeTotals($rental, $endAt, $itemsInput);

        return response()->json($this->checkoutPayload($rental, $endAt, $calc));
    }

    public function checkout(Request $request, Rental $rental): JsonResponse
    {
        if (! $rental->isActive()) {
            abort(404);
        }

        TokoScope::authorizeRental($rental);

        $validated = $this->validateCheckoutRequest($request);
        $paymentScope = $validated['payment_scope'] ?? 'all';

        $paymentPayload = [
            'payment_scope' => $paymentScope,
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

            $itemsInput = $validated['additional_items'] ?? null;
            if ($itemsInput === null) {
                $itemsInput = $locked->additionalItems()
                    ->get(['id_additional_item', 'qty'])
                    ->map(function ($row) {
                        return ['id' => (int) $row->id_additional_item, 'qty' => (int) $row->qty];
                    })
                    ->all();
            }

            $endAt = RentalCheckout::resolveEndTime($locked, $validated['ended_at'] ?? null);
            $calc = RentalCheckout::computeTotals($locked, $endAt, $itemsInput);

            $locked->update([
                'waktu_end' => $endAt,
                'total_durasi' => $calc['total_minutes'],
                'total_harga_sewa' => $calc['total_harga_sewa'],
                'total_harga_additional' => $calc['total_harga_additional'],
                'total_harga' => $calc['total_harga'],
                'total' => $calc['total_harga'],
                'status' => 'completed',
            ]);

            RentalAdditionalItem::query()->where('id_rental', $locked->id)->delete();
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

            $this->syncIncomeCashFlows($locked, $endAt, $calc);

            if (! empty($paymentPayload['metode_pembayaran']) && $paymentPayload['jumlah_bayar'] !== null) {
                $this->applyCheckoutPayment($locked, $calc, $paymentPayload, $endAt);
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
            'payment_scope' => ['nullable', Rule::in(['all', 'sewa', 'additional'])],
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

        if (empty($validated['payment_scope'])) {
            $validated['payment_scope'] = 'all';
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

        $snapshot = RentalCheckout::resolvePromoSnapshot($idPromo, (int) $meja->id_toko, $at, false);
        if (! $snapshot) {
            throw ValidationException::withMessages([
                'id_promo' => ['Promo tidak valid, tidak aktif untuk tanggal ini, atau tidak berlaku untuk toko meja ini.'],
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
        $dues = RentalPayment::checkoutDues(
            $rental,
            (float) $calc['total_harga_sewa'],
            (float) $calc['total_harga_additional']
        );

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
            'promo_duration_limit' => $rental->hasPromoDurationLimit() ? (float) $rental->promo_duration_limit : null,
            'total_harga_sewa' => $calc['total_harga_sewa'],
            'total_harga_sewa_formatted' => number_format($calc['total_harga_sewa'], 0, ',', '.'),
            'total_harga_additional' => $calc['total_harga_additional'],
            'total_harga_additional_formatted' => number_format($calc['total_harga_additional'], 0, ',', '.'),
            'total_harga' => $calc['total_harga'],
            'total_harga_formatted' => number_format($calc['total_harga'], 0, ',', '.'),
            'sewa_due' => $dues['sewa_due'],
            'additional_due' => $dues['additional_due'],
            'total_due' => $dues['total_due'],
            'additional_paid' => $dues['additional_paid'],
            'sewa_paid' => $dues['sewa_paid'],
            'breakdown_html' => $calc['breakdown_html'],
            'additional_lines' => $calc['additional_lines'],
        ];
    }

    /**
     * @param  array<string, mixed>  $calc
     */
    private function syncIncomeCashFlows(Rental $rental, CarbonInterface $endAt, array $calc): void
    {
        $rental->loadMissing('meja.toko');
        $mejaNama = $rental->meja->nama ?? 'Meja';
        $tokoNama = $rental->meja->toko->nama ?? '';
        $deskripsiSewa = $tokoNama !== ''
            ? "Sewa meja {$mejaNama} ({$tokoNama}) — {$rental->nama_customer}"
            : "Sewa meja {$mejaNama} — {$rental->nama_customer}";

        $uid = auth()->id() ?? 0;
        $now = $endAt;

        $sewaFlow = RentalPayment::findCashFlow($rental, CashFlow::KATEGORI_SEWA_MEJA);
        if (! $sewaFlow) {
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
        } else {
            $sewaFlow->update([
                'total' => $calc['total_harga_sewa'],
                'keterangan' => $deskripsiSewa,
                'idm' => $uid,
                'dom' => $now,
            ]);
        }

        RentalPayment::syncAdditionalCashFlow(
            $rental,
            (float) $calc['total_harga_additional'],
            "Additional Item (F&B) — {$rental->nama_customer} · {$mejaNama}",
            false,
            null,
            null,
            null,
            $now
        );
    }

    /**
     * @param  array<string, mixed>  $calc
     * @param  array<string, mixed>  $paymentPayload
     */
    private function applyCheckoutPayment(
        Rental $rental,
        array $calc,
        array $paymentPayload,
        CarbonInterface $endAt
    ): void {
        $scope = $paymentPayload['payment_scope'] ?? 'all';
        $metode = (string) $paymentPayload['metode_pembayaran'];
        $jumlah = (float) $paymentPayload['jumlah_bayar'];
        $bukti = $paymentPayload['bukti'] ?? null;

        $dues = RentalPayment::checkoutDues(
            $rental,
            (float) $calc['total_harga_sewa'],
            (float) $calc['total_harga_additional']
        );

        $buktiPath = null;
        if ($bukti) {
            $buktiPath = RentalPayment::storeBukti($bukti, null);
        }

        $paySewa = in_array($scope, ['all', 'sewa'], true) && $dues['sewa_due'] > 0;
        $payAdditional = in_array($scope, ['all', 'additional'], true) && $dues['additional_due'] > 0;

        if ($scope === 'additional' && $dues['additional_due'] <= 0) {
            throw ValidationException::withMessages([
                'payment_scope' => ['Item tambahan sudah lunas / tidak ada tagihan item.'],
            ]);
        }

        if ($scope === 'sewa' && $dues['sewa_due'] <= 0) {
            throw ValidationException::withMessages([
                'payment_scope' => ['Sewa meja sudah lunas.'],
            ]);
        }

        if ($paySewa) {
            $sewaFlow = RentalPayment::findCashFlow($rental, CashFlow::KATEGORI_SEWA_MEJA);
            if ($sewaFlow) {
                RentalPayment::applyPaymentToCashFlow(
                    $sewaFlow,
                    $metode,
                    $scope === 'all' ? (float) $calc['total_harga_sewa'] : $jumlah,
                    null,
                    $buktiPath,
                    $endAt
                );
            }
        }

        if ($payAdditional) {
            $addFlow = RentalPayment::findCashFlow($rental, CashFlow::KATEGORI_ADDITIONAL_FB);
            if ($addFlow) {
                $payAmount = $scope === 'additional'
                    ? ($dues['additional_paid'] + $dues['additional_due'])
                    : (float) $calc['total_harga_additional'];
                RentalPayment::applyPaymentToCashFlow(
                    $addFlow,
                    $metode,
                    $payAmount,
                    null,
                    $buktiPath,
                    $endAt
                );
            }
        }

        // Rental-level payment snapshot for compatibility
        $rentalJumlah = $jumlah;
        if ($scope === 'all') {
            $rentalJumlah = $dues['total_due'] > 0 ? $jumlah : (float) $calc['total_harga'];
        } elseif ($scope === 'sewa') {
            $rentalJumlah = $jumlah;
        }

        $existingMetode = $rental->metode_pembayaran;
        $shouldWriteRental = $paySewa || ($scope === 'all' && $dues['total_due'] > 0);
        if ($shouldWriteRental || ($scope === 'all' && ! $existingMetode)) {
            $rental->update([
                'metode_pembayaran' => $metode,
                'jumlah_bayar' => $rentalJumlah,
                'bukti_transaksi' => $buktiPath ?? $rental->bukti_transaksi,
                'waktu_pembayaran' => $endAt,
                'total' => (float) $calc['total_harga'],
            ]);
        } elseif ($scope === 'additional' && $buktiPath && ! $rental->bukti_transaksi) {
            // keep rental payment fields as-is when only F&B paid
        }
    }
}
