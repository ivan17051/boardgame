<?php

namespace App\Support;

use App\Models\Rental;

class RentalCheckout
{
    /**
     * @return array{total_minutes: float, total_harga: float, breakdown_html: string}
     */
    public static function computeTotals(Rental $rental): array
    {
        $start = $rental->waktu_start;
        $now = now();
        $totalSeconds = max(0, $now->getTimestamp() - $start->getTimestamp());
        $totalMinutes = $totalSeconds / 60;
        $hargaPerJam = (float) $rental->harga;
        $totalHarga = ($totalMinutes / 60) * $hargaPerJam;

        $menitStr = number_format($totalMinutes, 2, ',', '.');
        $hargaStr = number_format($hargaPerJam, 3, ',', '.');
        $totalStr = number_format($totalHarga, 3, ',', '.');

        $jamDecimal = number_format($totalMinutes / 60, 4, ',', '.');
        $breakdownHtml = '<ul class="mb-0 ps-3">'
            .'<li><strong>Total durasi</strong>: '.$menitStr.' menit (dari mulai hingga sekarang)</li>'
            .'<li><strong>Tarif meja</strong>: Rp '.$hargaStr.' / jam</li>'
            .'<li><strong>Rumus</strong>: (total durasi menit ÷ 60) × tarif per jam = ('.$menitStr.' ÷ 60) × '.$hargaStr.'</li>'
            .'<li><strong>Jam pemakaian setara</strong>: '.$jamDecimal.' jam</li>'
            .'<li class="mt-2"><strong>Total tagihan</strong>: <span class="text-primary">Rp '.$totalStr.'</span></li>'
            .'</ul>';

        return [
            'total_minutes' => round($totalMinutes, 2),
            'total_harga' => round($totalHarga, 3),
            'breakdown_html' => $breakdownHtml,
        ];
    }
}
