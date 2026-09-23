<?php

namespace App\Http\Controllers;

use App\Models\Meja;
use App\Models\MejaBooking;
use App\Support\MejaBookingSchedule;
use App\Support\RentalCheckin;
use App\Support\RentalCheckout;
use App\Support\RentalMahjongScoring;
use App\Support\TokoScope;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MejaBookingController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_meja' => ['required', 'integer', 'exists:m_meja,id'],
            'nama_customer' => ['required', 'string', 'max:255'],
            'no_hp' => ['nullable', 'string', 'max:30'],
            'waktu_mulai' => ['required', 'date'],
            'durasi_jam' => ['required', 'integer', 'min:1', 'max:8'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        $start = Carbon::parse($validated['waktu_mulai']);
        if ($start->lt(now()->subMinute())) {
            throw ValidationException::withMessages([
                'waktu_mulai' => ['Waktu mulai booking harus sekarang atau di masa depan.'],
            ]);
        }

        $end = $start->copy()->addHours((int) $validated['durasi_jam']);

        $booking = DB::transaction(function () use ($validated, $start, $end) {
            $meja = Meja::query()
                ->whereKey($validated['id_meja'])
                ->lockForUpdate()
                ->first();

            if (! $meja) {
                throw ValidationException::withMessages([
                    'id_meja' => ['Meja tidak ditemukan.'],
                ]);
            }

            TokoScope::authorizeMeja($meja);
            MejaBookingSchedule::assertSlotAvailableLocked((int) $meja->id, $start, $end);

            return MejaBooking::query()->create([
                'id_meja' => $meja->id,
                'nama_customer' => $validated['nama_customer'],
                'no_hp' => $validated['no_hp'] ?? null,
                'waktu_mulai' => $start,
                'waktu_selesai' => $end,
                'status' => MejaBooking::STATUS_BOOKED,
                'created_by' => optional(auth()->user())->id,
                'catatan' => $validated['catatan'] ?? null,
            ]);
        });

        $booking->load('meja.toko');

        return response()->json([
            'message' => 'Booking meja disimpan.',
            'booking' => $booking->warningPayload(),
        ]);
    }

    public function checkIn(Request $request, MejaBooking $mejaBooking): JsonResponse
    {
        TokoScope::authorizeMejaBooking($mejaBooking);

        $validated = $request->validate([
            'nama_customer' => ['nullable', 'string', 'max:255'],
            'tipe_customer' => ['required', Rule::in([
                RentalCheckout::CUSTOMER_MEMBER,
                RentalCheckout::CUSTOMER_NON_MEMBER,
            ])],
            'id_promo' => ['nullable', 'integer'],
        ]);

        $rental = DB::transaction(function () use ($mejaBooking, $validated) {
            $booking = MejaBooking::query()
                ->whereKey($mejaBooking->id)
                ->lockForUpdate()
                ->first();

            if (! $booking || ! $booking->isBooked()) {
                throw ValidationException::withMessages([
                    'booking' => ['Booking tidak aktif atau sudah diproses.'],
                ]);
            }

            $meja = Meja::query()
                ->whereKey($booking->id_meja)
                ->lockForUpdate()
                ->first();

            if (! $meja) {
                throw ValidationException::withMessages([
                    'id_meja' => ['Meja tidak ditemukan.'],
                ]);
            }

            TokoScope::authorizeMeja($meja);

            if ($meja->status !== 'active') {
                throw ValidationException::withMessages([
                    'id_meja' => ['Meja sedang dipakai. Checkout sewa aktif dulu agar tamu booking bisa check-in.'],
                ]);
            }

            $nama = trim((string) ($validated['nama_customer'] ?? ''));
            if ($nama === '') {
                $nama = $booking->nama_customer;
            }

            $created = RentalCheckin::start($meja, [
                'nama_customer' => $nama,
                'tipe_customer' => $validated['tipe_customer'],
                'id_promo' => $validated['id_promo'] ?? null,
            ]);

            $booking->update([
                'status' => MejaBooking::STATUS_CHECKED_IN,
                'id_rental' => $created->id,
            ]);

            return $created;
        });

        $link = RentalMahjongScoring::ensureScoreLink($rental);

        return response()->json([
            'message' => 'Check-in booking berhasil. Meja disewa.',
            'rental_id' => (int) $rental->id,
            'score_url' => $link['score_url'],
        ]);
    }

    public function cancel(MejaBooking $mejaBooking): JsonResponse
    {
        TokoScope::authorizeMejaBooking($mejaBooking);

        DB::transaction(function () use ($mejaBooking) {
            $booking = MejaBooking::query()
                ->whereKey($mejaBooking->id)
                ->lockForUpdate()
                ->first();

            if (! $booking || ! $booking->isBooked()) {
                throw ValidationException::withMessages([
                    'booking' => ['Booking tidak aktif atau sudah diproses.'],
                ]);
            }

            $booking->update(['status' => MejaBooking::STATUS_CANCELLED]);
        });

        return response()->json([
            'message' => 'Booking dibatalkan.',
        ]);
    }

    public function noShow(MejaBooking $mejaBooking): JsonResponse
    {
        TokoScope::authorizeMejaBooking($mejaBooking);

        DB::transaction(function () use ($mejaBooking) {
            $booking = MejaBooking::query()
                ->whereKey($mejaBooking->id)
                ->lockForUpdate()
                ->first();

            if (! $booking || ! $booking->isBooked()) {
                throw ValidationException::withMessages([
                    'booking' => ['Booking tidak aktif atau sudah diproses.'],
                ]);
            }

            $booking->update(['status' => MejaBooking::STATUS_NO_SHOW]);
        });

        return response()->json([
            'message' => 'Booking ditandai no-show.',
        ]);
    }
}
