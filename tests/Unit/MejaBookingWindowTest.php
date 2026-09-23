<?php

namespace Tests\Unit;

use App\Models\MejaBooking;
use Carbon\Carbon;
use Tests\TestCase;

class MejaBookingWindowTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_blocks_walk_in_within_thirty_minutes_and_during_window(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-23 20:00:00'));

        $booking = new MejaBooking([
            'status' => MejaBooking::STATUS_BOOKED,
            'waktu_mulai' => Carbon::parse('2026-09-23 20:20:00'),
            'waktu_selesai' => Carbon::parse('2026-09-23 22:20:00'),
        ]);

        $this->assertTrue($booking->isWarningSoon());
        $this->assertTrue($booking->isHardBlockedForWalkIn());
        $this->assertSame(20, $booking->minutesUntilStart());

        $booking->waktu_mulai = Carbon::parse('2026-09-23 20:45:00');
        $this->assertFalse($booking->isWarningSoon());
        $this->assertFalse($booking->isHardBlockedForWalkIn());

        $booking->waktu_mulai = Carbon::parse('2026-09-23 20:10:00');
        $this->assertTrue($booking->isHardBlockedForWalkIn());

        $booking->waktu_mulai = Carbon::parse('2026-09-23 19:50:00');
        $this->assertTrue($booking->isInBookedWindow());
        $this->assertTrue($booking->isHardBlockedForWalkIn());
    }
}
