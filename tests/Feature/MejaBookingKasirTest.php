<?php

namespace Tests\Feature;

use App\Models\Meja;
use App\Models\MejaBooking;
use App\Models\User;
use App\Support\MejaBookingSchedule;
use Carbon\Carbon;
use Tests\TestCase;

class MejaBookingKasirTest extends TestCase
{
    public function test_kasir_sewa_shows_booking_ui_and_enforces_slot_rules(): void
    {
        $user = User::query()->where('role', 'admin')->where('is_active', true)->first();
        $this->assertNotNull($user);

        $marker = '__test_booking_kasir__';
        MejaBooking::query()->where('nama_customer', $marker)->delete();

        $startFar = now()->addDays(2)->setSecond(0);
        $endFar = $startFar->copy()->addHours(2);
        $startNear = now()->addMinutes(20)->seconds(0);
        $endNear = $startNear->copy()->addHours(2);

        $mejaFar = $this->firstMejaWithoutOverlap($startFar, $endFar, true);
        $mejaNear = $this->firstMejaWithoutOverlap($startNear, $endNear, true);
        $this->assertNotNull($mejaFar);
        $this->assertNotNull($mejaNear);

        $this->actingAs($user)
            ->get('/sewa')
            ->assertOk()
            ->assertSee('Booking meja')
            ->assertSee('id="bookingModal"', false);

        $payload = [
            'id_meja' => $mejaFar->id,
            'nama_customer' => $marker,
            'waktu_mulai' => $startFar->format('Y-m-d H:i:s'),
            'durasi_jam' => 2,
        ];

        try {
            $this->actingAs($user)
                ->postJson('/sewa/bookings', $payload)
                ->assertOk()
                ->assertJsonFragment(['message' => 'Booking meja disimpan.']);

            $this->actingAs($user)
                ->postJson('/sewa/bookings', $payload)
                ->assertStatus(422);

            $walkIn = [
                'id_meja' => $mejaFar->id,
                'nama_customer' => 'Walk-in tes',
                'tipe_customer' => 'non_member',
            ];

            if ($mejaFar->status === 'active') {
                $this->actingAs($user)
                    ->postJson('/sewa', $walkIn)
                    ->assertStatus(409)
                    ->assertJsonFragment(['needs_confirmation' => true]);
            }

            $booking = MejaBooking::query()->where('nama_customer', $marker)->first();
            $this->assertNotNull($booking);

            $this->actingAs($user)
                ->postJson('/sewa/bookings/'.$booking->id.'/cancel')
                ->assertOk();

            $this->assertSame(MejaBooking::STATUS_CANCELLED, $booking->fresh()->status);

            $near = $startNear;
            $this->actingAs($user)
                ->postJson('/sewa/bookings', [
                    'id_meja' => $mejaNear->id,
                    'nama_customer' => $marker,
                    'waktu_mulai' => $near->format('Y-m-d H:i:s'),
                    'durasi_jam' => 2,
                ])
                ->assertOk();

            if ($mejaNear->fresh()->status === 'active') {
                $this->actingAs($user)
                    ->postJson('/sewa', [
                        'id_meja' => $mejaNear->id,
                        'nama_customer' => 'Walk-in tes',
                        'tipe_customer' => 'non_member',
                    ])
                    ->assertStatus(422);
            }
        } finally {
            MejaBooking::query()->where('nama_customer', $marker)->delete();
            Carbon::setTestNow();
        }
    }

    private function firstMejaWithoutOverlap(Carbon $start, Carbon $end, bool $skipHardBlockedWalkIn = false): ?Meja
    {
        foreach (Meja::query()->orderBy('id')->get() as $meja) {
            if (! MejaBookingSchedule::overlappingBookings((int) $meja->id, $start, $end)->isEmpty()) {
                continue;
            }

            if ($skipHardBlockedWalkIn) {
                $next = MejaBookingSchedule::nextUpcoming((int) $meja->id);
                if ($next && $next->isHardBlockedForWalkIn()) {
                    continue;
                }
            }

            return $meja;
        }

        return null;
    }
}
