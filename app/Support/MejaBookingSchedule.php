<?php

namespace App\Support;

use App\Models\MejaBooking;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class MejaBookingSchedule
{
    /**
     * @return \Illuminate\Support\Collection<int, MejaBooking>
     */
    public static function overlappingBookings(int $idMeja, CarbonInterface $start, CarbonInterface $end, ?int $ignoreId = null)
    {
        $query = MejaBooking::query()
            ->booked()
            ->where('id_meja', $idMeja)
            ->where('waktu_mulai', '<', $end)
            ->where('waktu_selesai', '>', $start);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->orderBy('waktu_mulai')->get();
    }

    public static function assertSlotAvailableLocked(int $idMeja, CarbonInterface $start, CarbonInterface $end, ?int $ignoreId = null): void
    {
        $query = MejaBooking::query()
            ->booked()
            ->where('id_meja', $idMeja)
            ->lockForUpdate();

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        $overlap = $query->get()->first(function (MejaBooking $booking) use ($start, $end) {
            return $booking->waktu_mulai->lt($end) && $booking->waktu_selesai->gt($start);
        });

        if ($overlap) {
            throw ValidationException::withMessages([
                'waktu_mulai' => ['Meja sudah dibooking '.$overlap->waktu_mulai->format('d/m H:i').'–'.$overlap->waktu_selesai->format('H:i').' ('.$overlap->nama_customer.').'],
            ]);
        }
    }

    public static function assertWalkInAllowed(int $idMeja, bool $confirmed): void
    {
        MejaBooking::query()
            ->booked()
            ->where('id_meja', $idMeja)
            ->lockForUpdate()
            ->get();

        $next = self::nextUpcoming($idMeja);
        if (! $next) {
            return;
        }

        if ($next->isHardBlockedForWalkIn()) {
            $message = $next->isInBookedWindow()
                ? 'Meja sedang dalam jendela booking '.$next->nama_customer.' ('.$next->waktu_mulai->format('H:i').'–'.$next->waktu_selesai->format('H:i').'). Check-in tamu booking, atau batalkan/no-show dulu.'
                : 'Booking '.$next->nama_customer.' mulai dalam '.$next->minutesUntilStart().' menit ('.$next->waktu_mulai->format('H:i').'). Walk-in tidak diizinkan.';

            throw ValidationException::withMessages([
                'id_meja' => [$message],
            ]);
        }

        if (! $confirmed) {
            abort(response()->json([
                'message' => 'Meja ini ada booking '.$next->nama_customer.' pukul '.$next->waktu_mulai->format('H:i').'–'.$next->waktu_selesai->format('H:i').'. Sewa walk-in harus di-checkout sebelum jam tersebut. Lanjutkan?',
                'needs_confirmation' => true,
                'booking' => $next->warningPayload(),
            ], 409));
        }
    }

    public static function nextUpcoming(int $idMeja, ?CarbonInterface $at = null): ?MejaBooking
    {
        $at = $at ?: now();

        return MejaBooking::query()
            ->booked()
            ->where('id_meja', $idMeja)
            ->where('waktu_selesai', '>', $at)
            ->orderBy('waktu_mulai')
            ->first();
    }
}
