<?php

namespace App\Support;

use App\Models\CashFlow;
use App\Models\Rental;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
class RentalPayment
{
    /**
     * @return Collection<int, CashFlow>
     */
    public static function incomeFlowsForRental(Rental $rental): Collection
    {
        return CashFlow::query()
            ->where('id_rental', $rental->id)
            ->where('tipe_transaksi', 'income')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, CashFlow>
     */
    public static function applyToRental(
        Rental $rental,
        string $metodePembayaran,
        float $jumlahBayar,
        ?UploadedFile $bukti = null
    ): Collection {
        $flows = self::incomeFlowsForRental($rental);
        if ($flows->isEmpty()) {
            return $flows;
        }

        $totalBill = (float) $flows->sum(fn (CashFlow $f) => (float) $f->total);
        $buktiPath = self::storeBukti($bukti);
        $now = now();
        $uid = auth()->id();

        $remaining = $jumlahBayar;
        $lastIndex = $flows->count() - 1;

        foreach ($flows->values() as $index => $flow) {
            if ($index === $lastIndex) {
                $share = round($remaining, 3);
            } elseif ($totalBill > 0) {
                $share = round(((float) $flow->total / $totalBill) * $jumlahBayar, 3);
                $remaining -= $share;
            } else {
                $share = 0;
            }

            $flow->update([
                'metode_pembayaran' => $metodePembayaran,
                'jumlah_bayar' => $share,
                'bukti_transaksi' => $buktiPath ?? $flow->bukti_transaksi,
                'dom' => $now,
                'idm' => $uid,
            ]);
        }

        return $flows->fresh();
    }

    public static function storeBukti(?UploadedFile $bukti): ?string
    {
        if (! $bukti) {
            return null;
        }

        return $bukti->store('cash-flow-bukti', 'public');
    }

    public static function requiresBukti(string $metode): bool
    {
        return $metode !== 'tunai';
    }
}
