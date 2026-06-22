<?php

namespace Tests\Unit;

use App\Support\RentalCheckout;
use Carbon\Carbon;
use Tests\TestCase;

class RentalCheckoutPromoTest extends TestCase
{
    public function test_forfeits_promo_when_duration_limit_exceeds_jam_selesai_and_checkout_past_limit(): void
    {
        $start = Carbon::parse('2026-06-22 13:00:00');
        $end = Carbon::parse('2026-06-22 16:30:00');

        $this->assertTrue(RentalCheckout::forfeitsPromoAtCheckout($start, $end, 3, '12:00:00', '15:00:00'));
    }

    public function test_does_not_forfeit_when_checkout_before_duration_limit_end(): void
    {
        $start = Carbon::parse('2026-06-22 13:00:00');
        $end = Carbon::parse('2026-06-22 15:30:00');

        $this->assertFalse(RentalCheckout::forfeitsPromoAtCheckout($start, $end, 3, '12:00:00', '15:00:00'));
    }

    public function test_does_not_forfeit_when_duration_limit_does_not_exceed_jam_selesai(): void
    {
        $start = Carbon::parse('2026-06-22 13:00:00');
        $end = Carbon::parse('2026-06-22 16:30:00');

        $this->assertFalse(RentalCheckout::forfeitsPromoAtCheckout($start, $end, 1, '12:00:00', '15:00:00'));
    }

    public function test_forfeited_checkout_charges_entire_session_at_normal_rate(): void
    {
        $start = Carbon::parse('2026-06-22 13:00:00');
        $end = Carbon::parse('2026-06-22 16:30:00');
        $promoMinutes = RentalCheckout::promoEligibleMinutes($start, $end, '12:00:00', '15:00:00', null, null);

        $calc = RentalCheckout::computeTableRentalPriceFromSession(
            210,
            $promoMinutes,
            50000,
            30000,
            3,
            $start,
            $end,
            '12:00:00',
            '15:00:00'
        );

        $this->assertSame(0.0, $calc['promo_hours']);
        $this->assertSame(4.0, $calc['normal_hours']);
        $this->assertSame(200000.0, $calc['total_harga_sewa']);
    }

    public function test_unlimited_promo_applies_until_jam_selesai(): void
    {
        $start = Carbon::parse('2026-06-22 12:00:00');
        $end = Carbon::parse('2026-06-22 16:00:00');
        $promoMinutes = RentalCheckout::promoEligibleMinutes($start, $end, '12:00:00', '15:00:00', null, null);

        $calc = RentalCheckout::computeTableRentalPriceFromSession(
            240,
            $promoMinutes,
            50000,
            30000,
            null,
            $start,
            $end,
            '12:00:00',
            '15:00:00'
        );

        $this->assertSame(3.0, $calc['promo_hours']);
        $this->assertSame(1.0, $calc['normal_hours']);
        $this->assertSame(140000.0, $calc['total_harga_sewa']);
    }

    public function test_zero_duration_limit_treated_as_unlimited(): void
    {
        $this->assertNull(RentalCheckout::normalizePromoDurationLimit(0));
        $this->assertNull(RentalCheckout::normalizePromoDurationLimit(null));
        $this->assertSame(2.0, RentalCheckout::normalizePromoDurationLimit(2));
    }

    public function test_forfeits_promo_when_checkout_less_than_30_minutes_before_jam_selesai(): void
    {
        $start = Carbon::parse('2026-06-22 12:00:00');
        $end = Carbon::parse('2026-06-22 14:45:00');

        $this->assertTrue(RentalCheckout::forfeitsPromoDueToCheckoutProximity($end, '12:00:00', '15:00:00'));
        $this->assertFalse(RentalCheckout::forfeitsPromoDueToCheckoutProximity(
            Carbon::parse('2026-06-22 14:30:00'),
            '12:00:00',
            '15:00:00'
        ));
    }

    public function test_checkout_near_jam_selesai_charges_entire_session_at_normal_rate(): void
    {
        $start = Carbon::parse('2026-06-22 12:00:00');
        $end = Carbon::parse('2026-06-22 14:45:00');
        $promoMinutes = RentalCheckout::promoEligibleMinutes($start, $end, '12:00:00', '15:00:00', null, null);

        $calc = RentalCheckout::computeTableRentalPriceFromSession(
            165,
            $promoMinutes,
            50000,
            30000,
            null,
            $start,
            $end,
            '12:00:00',
            '15:00:00'
        );

        $this->assertSame(0.0, $calc['promo_hours']);
        $this->assertSame(3.0, $calc['normal_hours']);
        $this->assertSame(150000.0, $calc['total_harga_sewa']);
        $this->assertSame('checkout_proximity', $calc['promo_forfeit_reason'] ?? null);
    }

    public function test_checkout_30_minutes_or_more_before_jam_selesai_keeps_promo_price(): void
    {
        $start = Carbon::parse('2026-06-22 12:00:00');
        $end = Carbon::parse('2026-06-22 14:30:00');
        $promoMinutes = RentalCheckout::promoEligibleMinutes($start, $end, '12:00:00', '15:00:00', null, null);

        $calc = RentalCheckout::computeTableRentalPriceFromSession(
            150,
            $promoMinutes,
            50000,
            30000,
            null,
            $start,
            $end,
            '12:00:00',
            '15:00:00'
        );

        $this->assertSame(3.0, $calc['promo_hours']);
        $this->assertSame(0.0, $calc['normal_hours']);
        $this->assertSame(90000.0, $calc['total_harga_sewa']);
    }
}
