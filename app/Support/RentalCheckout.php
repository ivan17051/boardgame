<?php

namespace App\Support;

use App\Models\AdditionalItem;
use App\Models\Rental;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class RentalCheckout
{
    public const CUSTOMER_MEMBER = 'member';

    public const CUSTOMER_NON_MEMBER = 'non_member';

    /**
     * Jam ditagihkan: jam penuh + jika sisa menit > 15, tambah 1 jam (min. 1 jam).
     */
    public static function billedHours(float $totalMinutes): int
    {
        $minutes = max(0, (int) round($totalMinutes));
        $fullHours = intdiv($minutes, 60);
        $remainder = $minutes % 60;

        if ($remainder > 15) {
            $fullHours += 1;
        }

        return max(1, $fullHours);
    }

    public static function resolveEndTime(Rental $rental, $endedAtTimestamp = null): CarbonInterface
    {
        if ($endedAtTimestamp !== null && $endedAtTimestamp !== '') {
            $end = Carbon::createFromTimestamp((int) $endedAtTimestamp);
            $start = $rental->waktu_start;

            if ($end->lt($start)) {
                return $start->copy();
            }

            if ($end->gt(now()->addMinute())) {
                return now();
            }

            return $end;
        }

        return now();
    }

    /**
     * @param  array<int, array{id: int, qty: int}>|null  $additionalItemsInput
     * @return array{
     *   total_minutes: float,
     *   billed_hours: int,
     *   total_harga_sewa: float,
     *   total_harga_additional: float,
     *   total_harga: float,
     *   total_seconds: int,
     *   durasi_hms: string,
     *   breakdown_html: string,
     *   additional_lines: array<int, array{id: int, nama: string, harga: float, qty: int, subtotal: float}>
     * }
     */
    public static function computeTotals(Rental $rental, ?CarbonInterface $endAt = null, ?array $additionalItemsInput = null): array
    {
        $start = $rental->waktu_start;
        $end = $endAt ?? now();
        $totalSeconds = max(0, $end->getTimestamp() - $start->getTimestamp());
        $totalMinutes = $totalSeconds / 60;
        $billedHours = self::billedHours($totalMinutes);
        $hargaPerJam = (float) $rental->harga;
        $totalHargaSewa = round($billedHours * $hargaPerJam, 3);

        $additionalLines = self::resolveAdditionalLines($additionalItemsInput);
        $totalHargaAdditional = round(
            array_sum(array_column($additionalLines, 'subtotal')),
            3
        );
        $totalHarga = round($totalHargaSewa + $totalHargaAdditional, 3);

        $menitStr = number_format($totalMinutes, 2, ',', '.');
        $hargaStr = number_format($hargaPerJam, 0, ',', '.');
        $sewaStr = number_format($totalHargaSewa, 0, ',', '.');
        $addStr = number_format($totalHargaAdditional, 0, ',', '.');
        $totalStr = number_format($totalHarga, 0, ',', '.');
        $durasiHms = self::formatHms($totalSeconds);
        $durasiLabel = $endAt !== null ? 'dari mulai hingga selesai' : 'dari mulai hingga sekarang';
        $tipeLabel = $rental->tipe_customer === self::CUSTOMER_MEMBER ? 'Member' : 'Non-Member';

        $breakdownHtml = '<ul class="mb-0 ps-3">'
            .'<li><strong>Tipe customer</strong>: '.$tipeLabel.'</li>'
            .'<li><strong>Tarif meja</strong>: Rp '.$hargaStr.' / jam</li>'
            .'<li><strong>Jam ditagihkan</strong>: '.$billedHours.' jam (sisa &gt; 15 menit = +1 jam)</li>';
        
        return [
            'total_minutes' => round($totalMinutes, 2),
            'billed_hours' => $billedHours,
            'total_harga_sewa' => $totalHargaSewa,
            'total_harga_additional' => $totalHargaAdditional,
            'total_harga' => $totalHarga,
            'total_seconds' => $totalSeconds,
            'durasi_hms' => $durasiHms,
            'breakdown_html' => $breakdownHtml,
            'additional_lines' => $additionalLines,
        ];
    }

    /**
     * @param  array<int, array{id: int, qty: int}>|null  $input
     * @return array<int, array{id: int, nama: string, harga: float, qty: int, subtotal: float}>
     */
    public static function resolveAdditionalLines(?array $input): array
    {
        if (empty($input)) {
            return [];
        }

        $lines = [];
        $ids = collect($input)->pluck('id')->filter()->unique()->values()->all();
        $masters = TokoScope::scopeAdditionalItems(AdditionalItem::query())
            ->active()
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        foreach ($input as $row) {
            $id = (int) ($row['id'] ?? 0);
            $qty = max(1, (int) ($row['qty'] ?? 1));
            $master = $masters->get($id);
            if (! $master) {
                continue;
            }
            $harga = (float) $master->harga;
            $lines[] = [
                'id' => $master->id,
                'nama' => $master->nama,
                'harga' => $harga,
                'qty' => $qty,
                'subtotal' => round($harga * $qty, 3),
            ];
        }

        return $lines;
    }

    public static function formatHms(int $totalSeconds): string
    {
        $s = max(0, $totalSeconds);
        $h = intdiv($s, 3600);
        $m = intdiv($s % 3600, 60);
        $sec = $s % 60;

        return sprintf('%02d:%02d:%02d', $h, $m, $sec);
    }

    public static function rateForMeja($meja, string $tipeCustomer): float
    {
        $member = $tipeCustomer === self::CUSTOMER_MEMBER;
        if ($member && $meja->harga_member !== null && (float) $meja->harga_member > 0) {
            return (float) $meja->harga_member;
        }

        return (float) $meja->harga;
    }
}
