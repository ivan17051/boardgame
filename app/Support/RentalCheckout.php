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

    /** Checkout harus minimal sejauh ini (menit) sebelum jam_selesai agar tarif promo berlaku. */
    public const PROMO_CHECKOUT_MINUTES_BEFORE_SELESAI = 30;

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
     * Null atau 0 = promo tanpa batas durasi (hanya dibatasi jam_selesai).
     */
    public static function normalizePromoDurationLimit($limit): ?float
    {
        if ($limit === null || $limit === '') {
            return null;
        }

        $hours = (float) $limit;

        return $hours > 0 ? $hours : null;
    }

    public static function hasPromoDurationLimit(?float $promoDurationLimitHours): bool
    {
        return self::normalizePromoDurationLimit($promoDurationLimitHours) !== null;
    }

    /**
     * Split pricing: promo rate for up to promo_duration_limit billed hours (if set), then normal rate.
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
        $hasPromo = $promoHourlyRate !== null;

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

        $limit = self::normalizePromoDurationLimit($promoDurationLimitHours);
        $promoRate = (float) $promoHourlyRate;

        if ($limit === null || $billedHours <= $limit) {
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
        ?string $jamSelesai,
        ?string $tglAwal = null,
        ?string $tglAkhir = null
    ): float {
        if ($end->lte($start)) {
            return 0.0;
        }

        $mulai = RentalPromo::normalizeTimeString($jamMulai);
        $selesai = RentalPromo::normalizeTimeString($jamSelesai);
        $tglAwal = RentalPromo::normalizeDateString($tglAwal);
        $tglAkhir = RentalPromo::normalizeDateString($tglAkhir);
        $promoSeconds = 0;
        $cursor = $start->copy()->startOfDay();
        $lastDay = $end->copy()->startOfDay();

        while ($cursor->lte($lastDay)) {
            $dayDate = $cursor->format('Y-m-d');
            if ($tglAwal && $dayDate < $tglAwal) {
                $cursor->addDay();
                continue;
            }
            if ($tglAkhir && $dayDate > $tglAkhir) {
                $cursor->addDay();
                continue;
            }

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

    public static function promoDurationLimitEndAt(CarbonInterface $start, float $promoDurationLimitHours): CarbonInterface
    {
        return $start->copy()->addMinutes((int) round($promoDurationLimitHours * 60));
    }

    /**
     * Akhir jendela jam promo pada hari/siklus yang relevan dengan waktu mulai sewa.
     */
    public static function promoJamSelesaiAt(CarbonInterface $start, ?string $jamMulai, ?string $jamSelesai): CarbonInterface
    {
        $mulai = RentalPromo::normalizeTimeString($jamMulai);
        $selesai = RentalPromo::normalizeTimeString($jamSelesai);
        $day = $start->copy()->startOfDay();
        $windowStart = $day->copy()->setTimeFromTimeString($mulai);
        $windowEnd = $day->copy()->setTimeFromTimeString($selesai);

        if ($mulai > $selesai) {
            $windowEnd->addDay();
            $previousWindowEnd = $day->copy()->setTimeFromTimeString($selesai);
            if ($start->lte($previousWindowEnd)) {
                return $previousWindowEnd;
            }
        }

        return $windowEnd;
    }

    /**
     * jam_selesai jendela promo pada siklus yang relevan dengan waktu $at (biasanya waktu checkout).
     */
    public static function promoJamSelesaiAtForTime(CarbonInterface $at, ?string $jamMulai, ?string $jamSelesai): ?CarbonInterface
    {
        if (! $jamMulai || ! $jamSelesai) {
            return null;
        }

        $mulai = RentalPromo::normalizeTimeString($jamMulai);
        $selesai = RentalPromo::normalizeTimeString($jamSelesai);
        $day = $at->copy()->startOfDay();
        $windowEnd = $day->copy()->setTimeFromTimeString($selesai);

        if ($mulai <= $selesai) {
            return $windowEnd;
        }

        if ($at->format('H:i:s') <= $selesai) {
            return $windowEnd;
        }

        return $day->copy()->addDay()->setTimeFromTimeString($selesai);
    }

    /**
     * Menit dari $at hingga jam_selesai jendela promo (positif = sebelum jam_selesai).
     */
    public static function minutesUntilJamSelesai(CarbonInterface $at, ?string $jamMulai, ?string $jamSelesai): ?float
    {
        $jamSelesaiAt = self::promoJamSelesaiAtForTime($at, $jamMulai, $jamSelesai);
        if (! $jamSelesaiAt) {
            return null;
        }

        return ($jamSelesaiAt->getTimestamp() - $at->getTimestamp()) / 60;
    }

    /**
     * Checkout kurang dari 30 menit sebelum jam_selesai → seluruh sewa ditagih tarif normal.
     */
    public static function forfeitsPromoDueToCheckoutProximity(
        CarbonInterface $end,
        ?string $jamMulai,
        ?string $jamSelesai,
        int $minimumMinutesBeforeSelesai = self::PROMO_CHECKOUT_MINUTES_BEFORE_SELESAI
    ): bool {
        if (! $jamMulai || ! $jamSelesai) {
            return false;
        }

        $minutesUntil = self::minutesUntilJamSelesai($end, $jamMulai, $jamSelesai);

        return $minutesUntil !== null
            && $minutesUntil >= 0
            && $minutesUntil < $minimumMinutesBeforeSelesai;
    }

    /**
     * Promo hangus di checkout: batas durasi melewati jam_selesai dan waktu selesai melewati batas durasi.
     */
    public static function forfeitsPromoAtCheckout(
        CarbonInterface $start,
        CarbonInterface $end,
        float $promoDurationLimitHours,
        ?string $jamMulai,
        ?string $jamSelesai
    ): bool {
        if ($promoDurationLimitHours <= 0 || ! $jamMulai || ! $jamSelesai) {
            return false;
        }

        $durationLimitEnd = self::promoDurationLimitEndAt($start, $promoDurationLimitHours);
        $jamSelesaiAt = self::promoJamSelesaiAt($start, $jamMulai, $jamSelesai);

        return $durationLimitEnd->gt($jamSelesaiAt)
            && $end->gt($jamSelesaiAt)
            && $end->gt($durationLimitEnd);
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
        ?float $promoDurationLimitHours,
        ?CarbonInterface $sessionStart = null,
        ?CarbonInterface $sessionEnd = null,
        ?string $jamMulai = null,
        ?string $jamSelesai = null
    ): array {
        $totalBilled = self::billedHours($totalMinutes);
        $hasPromo = $promoHourlyRate !== null;

        if (! $hasPromo) {
            $calc = self::computeTableRentalPrice($totalBilled, $normalHourlyRate, null, null);

            return array_merge($calc, ['promo_eligible_minutes' => 0.0]);
        }

        $limit = self::normalizePromoDurationLimit($promoDurationLimitHours);

        if ($sessionStart && $sessionEnd && self::forfeitsPromoDueToCheckoutProximity(
            $sessionEnd,
            $jamMulai,
            $jamSelesai
        )) {
            $calc = self::computeTableRentalPrice($totalBilled, $normalHourlyRate, null, null);

            return array_merge($calc, [
                'promo_eligible_minutes' => round($promoEligibleMinutes, 2),
                'promo_forfeited' => true,
                'promo_forfeit_reason' => 'checkout_proximity',
            ]);
        }

        if ($limit !== null && $sessionStart && $sessionEnd && self::forfeitsPromoAtCheckout(
            $sessionStart,
            $sessionEnd,
            $limit,
            $jamMulai,
            $jamSelesai
        )) {
            $calc = self::computeTableRentalPrice($totalBilled, $normalHourlyRate, null, null);

            return array_merge($calc, [
                'promo_eligible_minutes' => round($promoEligibleMinutes, 2),
                'promo_forfeited' => true,
                'promo_forfeit_reason' => 'duration_past_jam_selesai',
            ]);
        }

        $promoEligibleBilled = $promoEligibleMinutes > 0
            ? self::billedHours($promoEligibleMinutes)
            : 0;
        $promoRate = (float) $promoHourlyRate;
        $promoHoursApplied = $limit === null
            ? min((float) $promoEligibleBilled, (float) $totalBilled)
            : min((float) $promoEligibleBilled, $limit, (float) $totalBilled);
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
     *   promo_duration_limit: float|null,
     *   promo_jam_mulai: string,
     *   promo_jam_selesai: string,
     *   promo_tgl_awal: string,
     *   promo_tgl_akhir: string
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
        } else {
            $query->activeOnDate($at);
        }

        $promo = $query->first();

        if (! $promo) {
            return null;
        }

        if ($requireActiveTime && ! $promo->isActiveAt($at)) {
            return null;
        }

        if (! $requireActiveTime && ! $promo->isActiveOnDate($at)) {
            return null;
        }

        return [
            'id_promo' => (int) $promo->id,
            'promo_nama' => $promo->nama,
            'promo_hourly_rate' => (float) $promo->promo_hourly_rate,
            'promo_duration_limit' => self::normalizePromoDurationLimit($promo->promo_duration_limit),
            'promo_jam_mulai' => RentalPromo::normalizeTimeString($promo->jam_mulai),
            'promo_jam_selesai' => RentalPromo::normalizeTimeString($promo->jam_selesai),
            'promo_tgl_awal' => RentalPromo::normalizeDateString($promo->tgl_awal) ?? '',
            'promo_tgl_akhir' => RentalPromo::normalizeDateString($promo->tgl_akhir) ?? '',
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
     *   additional_lines: array<int, array{id: int, nama: string, harga: float, qty: int, subtotal: float, is_discount: bool}>,
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
        $promoLimit = $rental->hasPromo()
            ? self::normalizePromoDurationLimit($rental->promo_duration_limit)
            : null;
        $jamMulai = $rental->promo_jam_mulai
            ? RentalPromo::normalizeTimeString($rental->promo_jam_mulai)
            : null;
        $jamSelesai = $rental->promo_jam_selesai
            ? RentalPromo::normalizeTimeString($rental->promo_jam_selesai)
            : null;

        if ($rental->hasPromo() && $jamMulai && $jamSelesai) {
            $promoEligibleMinutes = self::promoEligibleMinutes(
                $start,
                $end,
                $jamMulai,
                $jamSelesai,
                $rental->promo_tgl_awal,
                $rental->promo_tgl_akhir
            );
            $sewaCalc = self::computeTableRentalPriceFromSession(
                $totalMinutes,
                $promoEligibleMinutes,
                $normalRate,
                $promoRate,
                $promoLimit,
                $start,
                $end,
                $jamMulai,
                $jamSelesai
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
        $totalHarga = max(0, round($totalHargaSewa + $totalHargaAdditional, 3));

        $additionalPositive = 0.0;
        $additionalDiscount = 0.0;
        foreach ($additionalLines as $line) {
            if ($line['subtotal'] < 0) {
                $additionalDiscount += abs($line['subtotal']);
            } else {
                $additionalPositive += $line['subtotal'];
            }
        }

        $menitStr = number_format($totalMinutes, 2, ',', '.');
        $hargaStr = number_format($normalRate, 0, ',', '.');
        $sewaStr = number_format($totalHargaSewa, 0, ',', '.');
        $addPositiveStr = number_format($additionalPositive, 0, ',', '.');
        $addDiscountStr = number_format($additionalDiscount, 0, ',', '.');
        $addNetStr = number_format($totalHargaAdditional, 0, ',', '.');
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
            $limitLabel = $rental->hasPromoDurationLimit()
                ? 'maks. '.number_format((float) $rental->promo_duration_limit, 2, ',', '.').' jam'
                : 'tanpa batas durasi';
            $namaPromo = htmlspecialchars($rental->promo_nama ?? 'Promo', ENT_QUOTES, 'UTF-8');
            $jamMulai = substr(RentalPromo::normalizeTimeString($rental->promo_jam_mulai), 0, 5);
            $jamSelesai = substr(RentalPromo::normalizeTimeString($rental->promo_jam_selesai), 0, 5);
            $periodeLabel = 'tanpa batas tanggal';
            if ($rental->promo_tgl_awal && $rental->promo_tgl_akhir) {
                $periodeLabel = \Carbon\Carbon::parse($rental->promo_tgl_awal)->format('d/m/Y')
                    .'–'.\Carbon\Carbon::parse($rental->promo_tgl_akhir)->format('d/m/Y');
            } elseif ($rental->promo_tgl_awal) {
                $periodeLabel = 'dari '.\Carbon\Carbon::parse($rental->promo_tgl_awal)->format('d/m/Y');
            } elseif ($rental->promo_tgl_akhir) {
                $periodeLabel = 'hingga '.\Carbon\Carbon::parse($rental->promo_tgl_akhir)->format('d/m/Y');
            }
            $breakdownHtml .= '<li><strong>Promo</strong>: '.$namaPromo
                .' — Rp '.$promoRateStr.' / jam ('.$limitLabel.', '.$periodeLabel.', jam '.$jamMulai.'–'.$jamSelesai.')</li>';
            if (($sewaCalc['promo_eligible_minutes'] ?? 0) > 0) {
                $breakdownHtml .= '<li><strong>Durasi dalam jam promo</strong>: '
                    .number_format($sewaCalc['promo_eligible_minutes'], 2, ',', '.').' menit</li>';
            }

            if ($sewaCalc['promo_hours'] > 0) {
                $breakdownHtml .= '<li><strong>Bagian promo</strong>: '
                    .number_format($sewaCalc['promo_hours'], 2, ',', '.').' jam × Rp '.$promoRateStr
                    .' = Rp '.number_format($sewaCalc['promo_part'], 0, ',', '.').'</li>';
            } elseif (($sewaCalc['promo_eligible_minutes'] ?? 0) > 0 && $sewaCalc['promo_part'] <= 0) {
                $forfeitReason = $sewaCalc['promo_forfeit_reason'] ?? null;
                if ($forfeitReason === 'checkout_proximity') {
                    $breakdownHtml .= '<li class="text-warning"><strong>Promo tidak berlaku</strong>: checkout kurang dari '
                        .self::PROMO_CHECKOUT_MINUTES_BEFORE_SELESAI.' menit sebelum jam promo selesai — tarif normal untuk seluruh sewa</li>';
                } else {
                    $breakdownHtml .= '<li class="text-warning"><strong>Promo tidak berlaku</strong>: checkout melewati jam promo dan batas durasi — tarif normal untuk seluruh sewa</li>';
                }
            }
            if ($sewaCalc['normal_hours'] > 0) {
                $breakdownHtml .= '<li><strong>Bagian tarif normal</strong>: '
                    .number_format($sewaCalc['normal_hours'], 2, ',', '.').' jam × Rp '.$hargaStr
                    .' = Rp '.number_format($sewaCalc['normal_part'], 0, ',', '.').'</li>';
            }
        }

        $breakdownHtml .= '<li><strong>Subtotal sewa meja</strong>: Rp '.$sewaStr.'</li>';

        if ($additionalPositive > 0) {
            $breakdownHtml .= '<li><strong>Item tambahan</strong>: Rp '.$addPositiveStr.'</li>';
        }
        if ($additionalDiscount > 0) {
            $breakdownHtml .= '<li><strong>Diskon</strong>: − Rp '.$addDiscountStr.'</li>';
        }
        if ($additionalPositive > 0 && $additionalDiscount > 0) {
            $breakdownHtml .= '<li><strong>Subtotal item & diskon</strong>: Rp '.$addNetStr.'</li>';
        } elseif ($totalHargaAdditional !== 0.0 && $additionalPositive <= 0 && $additionalDiscount <= 0) {
            $breakdownHtml .= '<li><strong>Item tambahan & diskon</strong>: Rp '.$addNetStr.'</li>';
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
     * @return array<int, array{id: int, nama: string, harga: float, qty: int, subtotal: float, is_discount: bool}>
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
            $harga = abs((float) $master->harga);
            $isDiscount = (bool) $master->is_discount;
            $subtotal = round($harga * $qty, 3);
            if ($isDiscount) {
                $subtotal = -$subtotal;
            }
            $lines[] = [
                'id' => $master->id,
                'nama' => $master->nama,
                'harga' => $harga,
                'qty' => $qty,
                'subtotal' => $subtotal,
                'is_discount' => $isDiscount,
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
