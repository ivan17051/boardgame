<?php

namespace App\Http\Controllers;

use App\Models\AdditionalItem;
use App\Models\CashFlow;
use App\Models\Meja;
use App\Models\Rental;
use App\Models\RentalAdditionalItem;
use App\Models\RentalPromo;
use App\Support\RentalCheckout;
use App\Support\RentalInvoice;
use App\Support\RentalPayment;
use App\Support\TokoScope;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ManualRentalController extends Controller
{
    public function index()
    {
        $mejas = TokoScope::scopeMejas(Meja::query())
            ->with('toko')
            ->orderBy('id_toko')
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
                ->active()
                ->orderBy('nama')
                ->get(['id', 'id_toko', 'nama', 'promo_hourly_rate', 'promo_duration_limit', 'jam_mulai', 'jam_selesai', 'tgl_awal', 'tgl_akhir']);
        }

        return view('rental.manual', compact('mejas', 'additionalItems', 'rentalPromos'));
    }

    public function store(Request $request): JsonResponse
    {
        $additionalItems = $request->input('additional_items');
        if (is_string($additionalItems)) {
            $decoded = json_decode($additionalItems, true);
            $request->merge(['additional_items' => is_array($decoded) ? $decoded : []]);
        }

        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'id_meja' => ['required', 'integer', 'exists:m_meja,id'],
            'nama_customer' => ['required', 'string', 'max:255'],
            'tipe_customer' => ['required', Rule::in([
                RentalCheckout::CUSTOMER_MEMBER,
                RentalCheckout::CUSTOMER_NON_MEMBER,
            ])],
            'jam_ditagihkan' => ['required', 'integer', 'min:1', 'max:999'],
            'id_promo' => ['nullable', 'integer'],
            'additional_items' => ['nullable', 'array'],
            'additional_items.*.id' => ['required', 'integer'],
            'additional_items.*.qty' => ['required', 'integer', 'min:1', 'max:999'],
            'metode_pembayaran' => ['required', 'string', 'max:100', Rule::in(['tunai', 'transfer', 'qris', 'kartu', 'lainnya'])],
            'jumlah_bayar' => ['required', 'numeric', 'min:0'],
            'bukti' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,pdf'],
        ], [
            'tanggal.required' => 'Tanggal transaksi wajib diisi.',
            'jam_ditagihkan.required' => 'Jam ditagihkan wajib diisi.',
            'metode_pembayaran.required' => 'Pilih metode pembayaran.',
            'jumlah_bayar.required' => 'Jumlah bayar wajib diisi.',
        ]);

        $result = DB::transaction(function () use ($validated, $request) {
            $meja = Meja::query()->whereKey($validated['id_meja'])->firstOrFail();
            TokoScope::authorizeMeja($meja);

            if ($meja->status === 'rented') {
                throw ValidationException::withMessages([
                    'id_meja' => ['Meja sedang disewa. Selesaikan di Kasir / Meja terlebih dahulu.'],
                ]);
            }

            $rate = RentalCheckout::rateForMeja($meja, $validated['tipe_customer']);
            $billedHours = (int) $validated['jam_ditagihkan'];
            $at = Carbon::parse($validated['tanggal'])->setTimeFrom(now());
            $promoSnapshot = RentalCheckout::resolvePromoSnapshot(
                isset($validated['id_promo']) ? (int) $validated['id_promo'] : null,
                (int) $meja->id_toko,
                $at,
                false
            );
            if (! empty($validated['id_promo']) && ! $promoSnapshot) {
                throw ValidationException::withMessages([
                    'id_promo' => ['Promo tidak valid atau tidak aktif untuk toko meja ini.'],
                ]);
            }
            $totalMinutes = $billedHours * 60;
            $waktuStart = Carbon::parse($validated['tanggal'])->setTimeFrom(now());
            $waktuEnd = $waktuStart->copy()->addMinutes($totalMinutes);

            $promoRate = $promoSnapshot['promo_hourly_rate'] ?? null;
            $promoLimit = $promoSnapshot['promo_duration_limit'] ?? null;
            if ($promoSnapshot) {
                $promoMinutes = RentalCheckout::promoEligibleMinutes(
                    $waktuStart,
                    $waktuEnd,
                    $promoSnapshot['promo_jam_mulai'],
                    $promoSnapshot['promo_jam_selesai'],
                    $promoSnapshot['promo_tgl_awal'] ?? null,
                    $promoSnapshot['promo_tgl_akhir'] ?? null
                );
                $sewaCalc = RentalCheckout::computeTableRentalPriceFromSession(
                    $totalMinutes,
                    $promoMinutes,
                    $rate,
                    $promoRate,
                    $promoLimit
                );
            } else {
                $sewaCalc = RentalCheckout::computeTableRentalPrice($billedHours, $rate, null, null);
            }
            $totalHargaSewa = $sewaCalc['total_harga_sewa'];
            $additionalLines = RentalCheckout::resolveAdditionalLines($validated['additional_items'] ?? []);
            $totalHargaAdditional = round(
                array_sum(array_column($additionalLines, 'subtotal')),
                3
            );
            $totalHarga = max(0, round($totalHargaSewa + $totalHargaAdditional, 3));

            $at = $waktuEnd->copy();

            $promoFields = [
                'id_promo' => null,
                'promo_nama' => null,
                'promo_hourly_rate' => null,
                'promo_duration_limit' => null,
                'promo_jam_mulai' => null,
                'promo_jam_selesai' => null,
                'promo_tgl_awal' => null,
                'promo_tgl_akhir' => null,
            ];
            if ($promoSnapshot) {
                $promoFields = [
                    'id_promo' => $promoSnapshot['id_promo'],
                    'promo_nama' => $promoSnapshot['promo_nama'],
                    'promo_hourly_rate' => $promoSnapshot['promo_hourly_rate'],
                    'promo_duration_limit' => $promoSnapshot['promo_duration_limit'],
                    'promo_jam_mulai' => $promoSnapshot['promo_jam_mulai'],
                    'promo_jam_selesai' => $promoSnapshot['promo_jam_selesai'],
                    'promo_tgl_awal' => $promoSnapshot['promo_tgl_awal'] ?: null,
                    'promo_tgl_akhir' => $promoSnapshot['promo_tgl_akhir'] ?: null,
                ];
            }

            $rental = Rental::query()->create(array_merge([
                'id_meja' => $meja->id,
                'nama_customer' => $validated['nama_customer'],
                'tipe_customer' => $validated['tipe_customer'],
                'waktu_start' => $waktuStart,
                'waktu_end' => $waktuEnd,
                'total_durasi' => $totalMinutes,
                'harga' => $rate,
                'total_harga_sewa' => $totalHargaSewa,
                'total_harga_additional' => $totalHargaAdditional,
                'total_harga' => $totalHarga,
                'total' => $totalHarga,
                'status' => 'completed',
                'guest_token' => null,
            ], $promoFields));

            foreach ($additionalLines as $line) {
                RentalAdditionalItem::query()->create([
                    'id_rental' => $rental->id,
                    'id_additional_item' => $line['id'],
                    'nama' => $line['nama'],
                    'harga' => $line['harga'],
                    'qty' => $line['qty'],
                    'subtotal' => $line['subtotal'],
                ]);
            }

            $this->createIncomeCashFlows($rental, $at, $totalHargaSewa, $totalHargaAdditional, true);

            RentalPayment::saveOnRental(
                $rental,
                $validated['metode_pembayaran'],
                (float) $validated['jumlah_bayar'],
                $request->file('bukti'),
                $at
            );

            return ['rental' => $rental->fresh()];
        });

        $invoiceUrl = RentalInvoice::canIssue($result['rental'])
            ? route('rental.invoice', $result['rental'])
            : null;

        return response()->json([
            'message' => 'Sewa manual berhasil disimpan.',
            'invoice_url' => $invoiceUrl,
        ]);
    }

    private function createIncomeCashFlows(
        Rental $rental,
        Carbon $at,
        float $totalHargaSewa,
        float $totalHargaAdditional,
        bool $manual = false
    ): void {
        $rental->loadMissing('meja.toko');
        $mejaNama = $rental->meja->nama ?? 'Meja';
        $tokoNama = $rental->meja->toko->nama ?? '';
        $suffix = $manual ? ' (Input manual)' : '';
        $deskripsiSewa = $tokoNama !== ''
            ? "Sewa meja {$mejaNama} ({$tokoNama}) — {$rental->nama_customer}{$suffix}"
            : "Sewa meja {$mejaNama} — {$rental->nama_customer}{$suffix}";

        $uid = auth()->id() ?? 0;

        CashFlow::query()->create([
            'id_rental' => $rental->id,
            'tipe_transaksi' => 'income',
            'kategori_pendapatan' => CashFlow::KATEGORI_SEWA_MEJA,
            'total' => $totalHargaSewa,
            'keterangan' => $deskripsiSewa,
            'waktu_pembayaran' => $at,
            'idc' => $uid,
            'idm' => $uid,
            'doc' => $at,
            'dom' => $at,
        ]);

        if ($totalHargaAdditional != 0) {
            CashFlow::query()->create([
                'id_rental' => $rental->id,
                'tipe_transaksi' => 'income',
                'kategori_pendapatan' => CashFlow::KATEGORI_ADDITIONAL_FB,
                'total' => $totalHargaAdditional,
                'keterangan' => "Additional Item (F&B) — {$rental->nama_customer} · {$mejaNama}{$suffix}",
                'waktu_pembayaran' => $at,
                'idc' => $uid,
                'idm' => $uid,
                'doc' => $at,
                'dom' => $at,
            ]);
        }
    }
}
