<?php

namespace App\Support;

use App\Models\CashFlow;
use App\Models\Rental;
use Illuminate\Support\Collection;

class RentalInvoice
{
    /**
     * @return array<string, mixed>
     */
    public static function build(Rental $rental): array
    {
        $rental->loadMissing(['meja.toko', 'additionalItems', 'cashFlows']);

        $cashFlows = $rental->cashFlows
            ->where('tipe_transaksi', 'income')
            ->sortBy('id')
            ->values();

        $sewaFlow = $cashFlows->firstWhere('kategori_pendapatan', CashFlow::KATEGORI_SEWA_MEJA)
            ?? $cashFlows->first();
        $totalTagihan = $rental->billTotal();
        $totalDibayar = $rental->amountPaid();
        $totalMinutes = (float) ($rental->total_durasi ?? 0);
        $totalSeconds = (int) round($totalMinutes * 60);
        $totalHargaSewa = (float) ($rental->total_harga_sewa ?? ($sewaFlow ? $sewaFlow->total : 0));
        $totalHargaAdditional = (float) ($rental->total_harga_additional ?? $cashFlows
            ->where('kategori_pendapatan', CashFlow::KATEGORI_ADDITIONAL_FB)
            ->sum(function (CashFlow $c) {
                return (float) $c->total;
            }));

        return [
            'rental' => $rental,
            'cash_flows' => $cashFlows,
            'total_tagihan' => $totalTagihan,
            'total_dibayar' => $totalDibayar,
            'total_harga_sewa' => $totalHargaSewa,
            'total_harga_additional' => $totalHargaAdditional,
            'metode_label' => CashFlow::metodePembayaranLabel($rental->metode_pembayaran),
            'durasi_hms' => RentalCheckout::formatHms($totalSeconds),
            'billed_hours' => $totalMinutes > 0 ? round($totalMinutes / 60, 2) : 0,
        ];
    }

    public static function canIssue(Rental $rental): bool
    {
        if ($rental->kelengkapanStatus() !== 'lengkap') {
            return false;
        }

        return $rental->cashFlows()->where('tipe_transaksi', 'income')->exists();
    }
}
