<?php

namespace App\Support;

use App\Models\CashFlow;
use App\Models\Rental;
use App\Models\RentalAdditionalItem;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class RentalPayment
{
    public static function saveOnRental(
        Rental $rental,
        string $metodePembayaran,
        float $jumlahBayar,
        ?UploadedFile $bukti = null,
        ?CarbonInterface $waktuPembayaran = null
    ): Rental {
        $buktiPath = self::storeBukti($bukti, $rental->bukti_transaksi);

        $rental->update([
            'metode_pembayaran' => $metodePembayaran,
            'total' => (float) ($rental->total_harga ?? $rental->total ?? 0),
            'jumlah_bayar' => $jumlahBayar,
            'bukti_transaksi' => $buktiPath ?? $rental->bukti_transaksi,
            'waktu_pembayaran' => $waktuPembayaran ?? $rental->waktu_pembayaran ?? now(),
        ]);

        self::syncCashFlowWaktuFromRental($rental);

        return $rental->fresh();
    }

    public static function syncCashFlowWaktuFromRental(Rental $rental): void
    {
        if (! $rental->waktu_pembayaran) {
            return;
        }

        $rental->cashFlows()
            ->where('tipe_transaksi', 'income')
            ->whereNull('metode_pembayaran')
            ->update(['waktu_pembayaran' => $rental->waktu_pembayaran]);
    }

    public static function storeBukti(?UploadedFile $bukti, ?string $existingPath = null): ?string
    {
        if (! $bukti) {
            return $existingPath;
        }

        $disk = Storage::disk('public');
        if ($existingPath && $disk->exists($existingPath)) {
            $disk->delete($existingPath);
        }

        return $bukti->store('rental-bukti', 'public');
    }

    public static function requiresBukti(string $metode): bool
    {
        return $metode !== 'tunai';
    }

    public static function findCashFlow(Rental $rental, string $kategori): ?CashFlow
    {
        return CashFlow::query()
            ->where('id_rental', $rental->id)
            ->where('kategori_pendapatan', $kategori)
            ->first();
    }

    /**
     * Paid amount on F&B cashflow when metode is set.
     */
    public static function additionalPaidAmount(Rental $rental): float
    {
        $flow = self::findCashFlow($rental, CashFlow::KATEGORI_ADDITIONAL_FB);
        if (! $flow || empty($flow->metode_pembayaran)) {
            return 0.0;
        }

        return (float) ($flow->jumlah_bayar ?? $flow->total ?? 0);
    }

    /**
     * @return array{
     *   additional_total: float,
     *   additional_paid: float,
     *   additional_due: float,
     *   is_fully_paid: bool,
     *   metode_pembayaran: string|null,
     *   cashflow_id: int|null
     * }
     */
    public static function additionalPaymentState(Rental $rental, ?float $additionalTotal = null): array
    {
        if ($additionalTotal === null) {
            $additionalTotal = (float) RentalAdditionalItem::query()
                ->where('id_rental', $rental->id)
                ->sum('subtotal');
        }

        $flow = self::findCashFlow($rental, CashFlow::KATEGORI_ADDITIONAL_FB);
        $paid = self::additionalPaidAmount($rental);
        $due = max(0.0, round($additionalTotal - $paid, 3));

        return [
            'additional_total' => round($additionalTotal, 3),
            'additional_paid' => round($paid, 3),
            'additional_due' => $due,
            'is_fully_paid' => $additionalTotal == 0.0 || $due <= 0.0,
            'metode_pembayaran' => $flow && ! empty($flow->metode_pembayaran) ? (string) $flow->metode_pembayaran : null,
            'cashflow_id' => $flow ? (int) $flow->id : null,
        ];
    }

    public static function sewaPaidAmount(Rental $rental): float
    {
        $flow = self::findCashFlow($rental, CashFlow::KATEGORI_SEWA_MEJA);
        if (! $flow || empty($flow->metode_pembayaran)) {
            return 0.0;
        }

        return (float) ($flow->jumlah_bayar ?? $flow->total ?? 0);
    }

    /**
     * @return array{sewa_due: float, additional_due: float, total_due: float, additional_paid: float, sewa_paid: float}
     */
    public static function checkoutDues(Rental $rental, float $sewaTotal, float $additionalTotal): array
    {
        $sewaPaid = self::sewaPaidAmount($rental);
        $additionalPaid = self::additionalPaidAmount($rental);
        $sewaDue = max(0.0, round($sewaTotal - $sewaPaid, 3));
        // Allow negative remaining (diskon) so it can offset sewa in total_due.
        $additionalRemaining = round($additionalTotal - $additionalPaid, 3);
        $additionalDue = max(0.0, $additionalRemaining);

        return [
            'sewa_due' => $sewaDue,
            'additional_due' => $additionalDue,
            'total_due' => max(0.0, round($sewaDue + $additionalRemaining, 3)),
            'additional_paid' => round($additionalPaid, 3),
            'sewa_paid' => round($sewaPaid, 3),
        ];
    }

    /**
     * Apply payment fields onto a cashflow row.
     */
    public static function applyPaymentToCashFlow(
        CashFlow $cashFlow,
        string $metodePembayaran,
        float $jumlahBayar,
        ?UploadedFile $bukti = null,
        ?string $sharedBuktiPath = null,
        ?CarbonInterface $waktuPembayaran = null
    ): CashFlow {
        $buktiPath = $sharedBuktiPath;
        if ($bukti) {
            $buktiPath = self::storeBukti($bukti, $cashFlow->bukti_transaksi);
        } elseif ($sharedBuktiPath) {
            $buktiPath = $sharedBuktiPath;
        } else {
            $buktiPath = $cashFlow->bukti_transaksi;
        }

        $now = $waktuPembayaran ?? now();
        $cashFlow->update([
            'metode_pembayaran' => $metodePembayaran,
            'jumlah_bayar' => $jumlahBayar,
            'bukti_transaksi' => $buktiPath,
            'waktu_pembayaran' => $now,
            'idm' => auth()->id() ?? $cashFlow->idm,
            'dom' => $now,
        ]);

        return $cashFlow->fresh();
    }

    /**
     * Upsert unpaid/paid additional_fb cashflow for current F&B total and optionally pay it.
     */
    public static function syncAdditionalCashFlow(
        Rental $rental,
        float $additionalTotal,
        ?string $keterangan = null,
        bool $pay = false,
        ?string $metodePembayaran = null,
        ?float $jumlahBayar = null,
        ?UploadedFile $bukti = null,
        ?CarbonInterface $waktu = null
    ): ?CashFlow {
        $rental->loadMissing('meja');
        $mejaNama = $rental->meja->nama ?? 'Meja';
        $keterangan = $keterangan ?: "Additional Item (F&B) — {$rental->nama_customer} · {$mejaNama}";
        $uid = auth()->id() ?? 0;
        $now = $waktu ?? now();

        $flow = self::findCashFlow($rental, CashFlow::KATEGORI_ADDITIONAL_FB);

        if ($additionalTotal == 0.0 && ! $flow) {
            return null;
        }

        if ($additionalTotal == 0.0 && $flow && empty($flow->metode_pembayaran)) {
            $flow->delete();

            return null;
        }

        if (! $flow) {
            $flow = CashFlow::query()->create([
                'id_rental' => $rental->id,
                'tipe_transaksi' => 'income',
                'kategori_pendapatan' => CashFlow::KATEGORI_ADDITIONAL_FB,
                'total' => $additionalTotal,
                'keterangan' => $keterangan,
                'waktu_pembayaran' => $now,
                'idc' => $uid,
                'idm' => $uid,
                'doc' => $now,
                'dom' => $now,
            ]);
        } else {
            $flow->update([
                'total' => $additionalTotal,
                'keterangan' => $keterangan,
                'idm' => $uid,
                'dom' => $now,
            ]);
            $flow = $flow->fresh();
        }

        if ($pay && $metodePembayaran !== null && $jumlahBayar !== null) {
            $flow = self::applyPaymentToCashFlow(
                $flow,
                $metodePembayaran,
                $jumlahBayar,
                $bukti,
                null,
                $now
            );
        }

        return $flow;
    }
}
