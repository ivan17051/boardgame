<?php

namespace App\Support;

use App\Models\AdditionalItem;
use App\Models\Rental;
use App\Models\RentalPromo;
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
     * Split pricing: promo rate for up to promo_duration_limit billed hours, then normal rate.
     *
     * @return array{
     *   total_harga_sewa: float,
     *   promo_part: float,
     *   normal_part: float,
     *   promo_hours: float,
     *   normal_hours: float
     * }
     */
    public static function computeTableRentalPrice(
        int $billedHours,
        float $normalHourlyRate,
        ?float $promoHourlyRate,
        ?float $promoDurationLimitHours
    ): array {
        $billedHours = max(0, $billedHours);
        $hasPromo = $promoHourlyRate !== null
            && $promoDurationLimitHours !== null
            && (float) $promoDurationLimitHours > 0;

        if (! $hasPromo) {
            $total = round($billedHours * $normalHourlyRate, 3);

            return [
                'total_harga_sewa' => $total,
                'promo_part' => 0.0,
                'normal_part' => $total,
                'promo_hours' => 0.0,
                'normal_hours' => (float) $billedHours,
            ];
        }

        $limit = (float) $promoDurationLimitHours;
        $promoRate = (float) $promoHourlyRate;

        if ($billedHours <= $limit) {
            $promoPart = round($billedHours * $promoRate, 3);

            return [
                'total_harga_sewa' => $promoPart,
                'promo_part' => $promoPart,
                'normal_part' => 0.0,
                'promo_hours' => (float) $billedHours,
                'normal_hours' => 0.0,
            ];
        }

        $promoHours = $limit;
        $normalHours = $billedHours - $limit;
        $promoPart = round($promoHours * $promoRate, 3);
        $normalPart = round($normalHours * $normalHourlyRate, 3);

        return [
            'total_harga_sewa' => round($promoPart + $normalPart, 3),
            'promo_part' => $promoPart,
            'normal_part' => $normalPart,
            'promo_hours' => $promoHours,
            'normal_hours' => (float) $normalHours,
        ];
    }

    /**
     * Menit sewa yang jatuh dalam jendela jam promo (berulang per hari).
     */
    public static function promoEligibleMinutes(
        CarbonInterface $start,
        CarbonInterface $end,
        ?string $jamMulai,
        ?string $jamSelesai
    ): float {
        if ($end->lte($start)) {
            return 0.0;
        }

        $mulai = RentalPromo::normalizeTimeString($jamMulai);
        $selesai = RentalPromo::normalizeTimeString($jamSelesai);
        $promoSeconds = 0;
        $cursor = $start->copy()->startOfDay();
        $lastDay = $end->copy()->startOfDay();

        while ($cursor->lte($lastDay)) {
            $windowStart = $cursor->copy()->setTimeFromTimeString($mulai);
            $windowEnd = $cursor->copy()->setTimeFromTimeString($selesai);
            if ($mulai > $selesai) {
                $windowEnd->addDay();
            }

            $overlapStart = $start->greaterThan($windowStart) ? $start->copy() : $windowStart;
            $overlapEnd = $end->lessThan($windowEnd) ? $end->copy() : $windowEnd;

            if ($overlapEnd->gt($overlapStart)) {
                $promoSeconds += $overlapEnd->getTimestamp() - $overlapStart->getTimestamp();
            }

            $cursor->addDay();
        }

        return $promoSeconds / 60;
    }

    /**
     * @return array{
     *   total_harga_sewa: float,
     *   promo_part: float,
     *   normal_part: float,
     *   promo_hours: float,
     *   normal_hours: float,
     *   promo_eligible_minutes: float
     * }
     */
    public static function computeTableRentalPriceFromSession(
        float $totalMinutes,
        float $promoEligibleMinutes,
        float $normalHourlyRate,
        ?float $promoHourlyRate,
        ?float $promoDurationLimitHours
    ): array {
        $totalBilled = self::billedHours($totalMinutes);
        $hasPromo = $promoHourlyRate !== null
            && $promoDurationLimitHours !== null
            && (float) $promoDurationLimitHours > 0;

        if (! $hasPromo) {
            $calc = self::computeTableRentalPrice($totalBilled, $normalHourlyRate, null, null);

            return array_merge($calc, ['promo_eligible_minutes' => 0.0]);
        }

        $promoEligibleBilled = $promoEligibleMinutes > 0
            ? self::billedHours($promoEligibleMinutes)
            : 0;
        $limit = (float) $promoDurationLimitHours;
        $promoRate = (float) $promoHourlyRate;
        $promoHoursApplied = min((float) $promoEligibleBilled, $limit, (float) $totalBilled);
        $normalHours = max(0, $totalBilled - $promoHoursApplied);
        $promoPart = round($promoHoursApplied * $promoRate, 3);
        $normalPart = round($normalHours * $normalHourlyRate, 3);

        return [
            'total_harga_sewa' => round($promoPart + $normalPart, 3),
            'promo_part' => $promoPart,
            'normal_part' => $normalPart,
            'promo_hours' => $promoHoursApplied,
            'normal_hours' => (float) $normalHours,
            'promo_eligible_minutes' => round($promoEligibleMinutes, 2),
        ];
    }

    /**
     * @return array{
     *   id_promo: int,
     *   promo_nama: string,
     *   promo_hourly_rate: float,
     *   promo_duration_limit: float,
     *   promo_jam_mulai: string,
     *   promo_jam_selesai: string
     * }|null
     */
    public static function resolvePromoSnapshot(
        ?int $idPromo,
        int $idTokoMeja,
        ?CarbonInterface $at = null,
        bool $requireActiveTime = true
    ): ?array {
        if (! $idPromo) {
            return null;
        }

        $at = $at ?? now();
        $query = TokoScope::scopeRentalPromos(RentalPromo::query())
            ->active()
            ->whereKey($idPromo)
            ->where('id_toko', $idTokoMeja);

        if ($requireActiveTime) {
            $query->activeAt($at);
        }

        $promo = $query->first();

        if (! $promo || ($requireActiveTime && ! $promo->isActiveAt($at))) {
            return null;
        }

        return [
            'id_promo' => (int) $promo->id,
            'promo_nama' => $promo->nama,
            'promo_hourly_rate' => (float) $promo->promo_hourly_rate,
            'promo_duration_limit' => (float) $promo->promo_duration_limit,
            'promo_jam_mulai' => RentalPromo::normalizeTimeString($promo->jam_mulai),
            'promo_jam_selesai' => RentalPromo::normalizeTimeString($promo->jam_selesai),
        ];
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
     *   additional_lines: array<int, array{id: int, nama: string, harga: float, qty: int, subtotal: float}>,
     *   promo_part: float,
     *   normal_part: float,
     *   promo_hours: float,
     *   normal_hours: float
     * }
     */
    public static function computeTotals(Rental $rental, ?CarbonInterface $endAt = null, ?array $additionalItemsInput = null): array
    {
        $start = $rental->waktu_start;
        $end = $endAt ?? now();
        $totalSeconds = max(0, $end->getTimestamp() - $start->getTimestamp());
        $totalMinutes = $totalSeconds / 60;
        $billedHours = self::billedHours($totalMinutes);
        $normalRate = (float) $rental->harga;
        $promoRate = $rental->hasPromo() ? (float) $rental->promo_hourly_rate : null;
        $promoLimit = $rental->hasPromo() ? (float) $rental->promo_duration_limit : null;

        if ($rental->hasPromo() && $rental->promo_jam_mulai && $rental->promo_jam_selesai) {
            $promoEligibleMinutes = self::promoEligibleMinutes(
                $start,
                $end,
                $rental->promo_jam_mulai,
                $rental->promo_jam_selesai
            );
            $sewaCalc = self::computeTableRentalPriceFromSession(
                $totalMinutes,
                $promoEligibleMinutes,
                $normalRate,
                $promoRate,
                $promoLimit
            );
        } else {
            $sewaCalc = self::computeTableRentalPrice($billedHours, $normalRate, $promoRate, $promoLimit);
            $sewaCalc['promo_eligible_minutes'] = 0.0;
        }
        $totalHargaSewa = $sewaCalc['total_harga_sewa'];

        $additionalLines = self::resolveAdditionalLines($additionalItemsInput);
        $totalHargaAdditional = round(
            array_sum(array_column($additionalLines, 'subtotal')),
            3
        );
        $totalHarga = round($totalHargaSewa + $totalHargaAdditional, 3);

        $menitStr = number_format($totalMinutes, 2, ',', '.');
        $hargaStr = number_format($normalRate, 0, ',', '.');
        $sewaStr = number_format($totalHargaSewa, 0, ',', '.');
        $addStr = number_format($totalHargaAdditional, 0, ',', '.');
        $totalStr = number_format($totalHarga, 0, ',', '.');
        $durasiHms = self::formatHms($totalSeconds);
        $durasiLabel = $endAt !== null ? 'dari mulai hingga selesai' : 'dari mulai hingga sekarang';
        $tipeLabel = $rental->tipe_customer === self::CUSTOMER_MEMBER ? 'Member' : 'Non-Member';

        $breakdownHtml = '<ul class="mb-0 ps-3">'
            .'<li><strong>Tipe customer</strong>: '.$tipeLabel.'</li>'
            .'<li><strong>Tarif normal</strong>: Rp '.$hargaStr.' / jam</li>'
            .'<li><strong>Jam ditagihkan</strong>: '.$billedHours.' jam (sisa &gt; 15 menit = +1 jam)</li>';

        if ($rental->hasPromo()) {
            $promoRateStr = number_format((float) $rental->promo_hourly_rate, 0, ',', '.');
            $limitStr = number_format((float) $rental->promo_duration_limit, 2, ',', '.');
            $namaPromo = htmlspecialchars($rental->promo_nama ?? 'Promo', ENT_QUOTES, 'UTF-8');
            $jamMulai = substr(RentalPromo::normalizeTimeString($rental->promo_jam_mulai), 0, 5);
            $jamSelesai = substr(RentalPromo::normalizeTimeString($rental->promo_jam_selesai), 0, 5);
            $breakdownHtml .= '<li><strong>Promo</strong>: '.$namaPromo
                .' — Rp '.$promoRateStr.' / jam (maks. '.$limitStr.' jam, jam '.$jamMulai.'–'.$jamSelesai.')</li>';
            if (($sewaCalc['promo_eligible_minutes'] ?? 0) > 0) {
                $breakdownHtml .= '<li><strong>Durasi dalam jam promo</strong>: '
                    .number_format($sewaCalc['promo_eligible_minutes'], 2, ',', '.').' menit</li>';
            }

            if ($sewaCalc['promo_hours'] > 0) {
                $breakdownHtml .= '<li><strong>Bagian promo</strong>: '
                    .number_format($sewaCalc['promo_hours'], 2, ',', '.').' jam × Rp '.$promoRateStr
                    .' = Rp '.number_format($sewaCalc['promo_part'], 0, ',', '.').'</li>';
            }
            if ($sewaCalc['normal_hours'] > 0) {
                $breakdownHtml .= '<li><strong>Bagian tarif normal</strong>: '
                    .number_format($sewaCalc['normal_hours'], 2, ',', '.').' jam × Rp '.$hargaStr
                    .' = Rp '.number_format($sewaCalc['normal_part'], 0, ',', '.').'</li>';
            }
        }

        $breakdownHtml .= '<li><strong>Subtotal sewa meja</strong>: Rp '.$sewaStr.'</li>';

        if ($totalHargaAdditional > 0) {
            $breakdownHtml .= '<li><strong>Item tambahan</strong>: Rp '.$addStr.'</li>';
        }

        $breakdownHtml .= '<li><strong>Total</strong>: Rp '.$totalStr.'</li>'
            .'<li class="text-secondary"><em>Durasi '.$durasiLabel.': '.$durasiHms.' ('.$menitStr.' menit)</em></li>'
            .'</ul>';

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
            'promo_part' => $sewaCalc['promo_part'],
            'normal_part' => $sewaCalc['normal_part'],
            'promo_hours' => $sewaCalc['promo_hours'],
            'normal_hours' => $sewaCalc['normal_hours'],
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
