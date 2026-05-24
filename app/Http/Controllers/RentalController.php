<?php

namespace App\Http\Controllers;

use App\Models\AdditionalItem;
use App\Models\CashFlow;
use App\Models\Meja;
use App\Models\Rental;
use App\Models\RentalAdditionalItem;
use App\Models\Toko;
use App\Support\RentalCheckout;
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
            $additionalItems = AdditionalItem::query()
                ->active()
                ->orderBy('nama')
                ->get(['id', 'nama', 'harga']);
        }

        return view('rental.index', compact('tokos', 'additionalItems'));
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

            Rental::query()->create([
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
            ]);

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
            'metode_pembayaran' => $validated['metode_pembayaran'],
            'jumlah_bayar' => (float) $validated['jumlah_bayar'],
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

            $flows = RentalPayment::applyToRental(
                $locked,
                $paymentPayload['metode_pembayaran'],
                $paymentPayload['jumlah_bayar'],
                $paymentPayload['bukti']
            );

            return [
                'rental_id' => $locked->id,
                'cash_flows' => $flows,
            ];
        });

        return response()->json([
            'message' => 'Checkout & pembayaran tersimpan. Meja tersedia kembali.',
            'invoice_url' => $this->primaryInvoiceUrl($result['cash_flows']),
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
            'metode_pembayaran' => ['required', 'string', 'max:100', Rule::in(['tunai', 'transfer', 'qris', 'kartu', 'lainnya'])],
            'jumlah_bayar' => ['required', 'numeric', 'min:0'],
            'bukti' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,pdf'],
        ], [
            'metode_pembayaran.required' => 'Pilih metode pembayaran.',
            'jumlah_bayar.required' => 'Jumlah bayar wajib diisi.',
        ]);

        if (RentalPayment::requiresBukti($validated['metode_pembayaran']) && ! $request->hasFile('bukti')) {
            throw ValidationException::withMessages([
                'bukti' => ['Bukti pembayaran wajib untuk metode non-tunai.'],
            ]);
        }

        return $validated;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, CashFlow>  $flows
     */
    private function primaryInvoiceUrl($flows): ?string
    {
        $primary = $flows->firstWhere('kategori_pendapatan', CashFlow::KATEGORI_SEWA_MEJA)
            ?? $flows->first();

        if (! $primary || $primary->kelengkapanStatus() !== 'lengkap') {
            return null;
        }

        return route('cashflow.invoice', $primary);
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
            'metode_pembayaran' => null,
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
                'metode_pembayaran' => null,
                'waktu_pembayaran' => $now,
                'idc' => $uid,
                'idm' => $uid,
                'doc' => $now,
                'dom' => $now,
            ]);
        }
    }
}
