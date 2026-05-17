<?php

namespace App\Http\Controllers;

use App\Models\CashFlow;
use App\Models\Meja;
use App\Models\Rental;
use App\Models\Toko;
use App\Support\RentalCheckout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GuestRentalController extends Controller
{
    public function index(Request $request)
    {
        $tokos = Toko::query()->orderBy('nama')->get(['id', 'nama']);
        $toko = null;
        if ($request->filled('toko')) {
            $toko = Toko::query()->find((int) $request->query('toko'));
        }
        $mejasAvailable = $this->availableMejasQuery($toko)->get();

        return view('guest.rental', [
            'tokos' => $tokos,
            'selectedToko' => $toko,
            'mejasAvailable' => $mejasAvailable,
        ]);
    }

    public function active(Request $request): JsonResponse
    {
        $token = $this->guestTokenFromRequest($request);
        if (! $token) {
            return response()->json(['active' => false]);
        }

        $rental = Rental::query()
            ->where('guest_token', $token)
            ->where('status', 'active')
            ->with(['meja.toko'])
            ->first();

        if (! $rental) {
            return response()->json(['active' => false]);
        }

        return response()->json([
            'active' => true,
            'rental' => $this->rentalPayload($rental),
        ]);
    }

    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_meja' => ['required', 'integer', 'exists:m_meja,id'],
            'nama_customer' => ['required', 'string', 'max:255'],
        ]);

        $guestToken = Str::random(48);

        $rental = DB::transaction(function () use ($validated, $guestToken) {
            $meja = Meja::query()
                ->whereKey($validated['id_meja'])
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if (! $meja) {
                throw ValidationException::withMessages([
                    'id_meja' => ['Meja tidak tersedia atau sedang dipakai.'],
                ]);
            }

            $now = now();

            $rental = Rental::query()->create([
                'id_meja' => $meja->id,
                'nama_customer' => $validated['nama_customer'],
                'waktu_start' => $now,
                'waktu_end' => null,
                'total_durasi' => null,
                'harga' => $meja->harga,
                'total_harga' => null,
                'status' => 'active',
                'guest_token' => $guestToken,
            ]);

            $meja->update(['status' => 'rented']);

            return $rental->load(['meja.toko']);
        });

        return response()->json([
            'message' => 'Sewa dimulai. Selamat bermain!',
            'guest_token' => $guestToken,
            'rental' => $this->rentalPayload($rental),
        ]);
    }

    public function checkoutPreview(Request $request, Rental $rental): JsonResponse
    {
        $this->authorizeGuestRental($request, $rental);

        $rental->loadMissing('meja.toko');
        $calc = RentalCheckout::computeTotals($rental);

        return response()->json([
            'rental_id' => $rental->id,
            'nama_meja' => $rental->meja->nama,
            'nama_toko' => $rental->meja->toko->nama ?? '',
            'nama_customer' => $rental->nama_customer,
            'waktu_start' => $rental->waktu_start->format('d/m/Y H:i:s'),
            'durasi_menit' => $calc['total_minutes'],
            'durasi_menit_formatted' => number_format($calc['total_minutes'], 2, ',', '.'),
            'harga_per_jam' => (float) $rental->harga,
            'harga_per_jam_formatted' => number_format((float) $rental->harga, 0, ',', '.'),
            'total_harga' => $calc['total_harga'],
            'total_harga_formatted' => number_format($calc['total_harga'], 0, ',', '.'),
            'breakdown_html' => $calc['breakdown_html'],
        ]);
    }

    public function stop(Request $request, Rental $rental): JsonResponse
    {
        $this->authorizeGuestRental($request, $rental);

        $summary = DB::transaction(function () use ($rental) {
            $locked = Rental::query()
                ->whereKey($rental->id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                abort(404);
            }

            $calc = RentalCheckout::computeTotals($locked);
            $now = now();

            $locked->update([
                'waktu_end' => $now,
                'total_durasi' => $calc['total_minutes'],
                'total_harga' => $calc['total_harga'],
                'status' => 'completed',
                'guest_token' => null,
            ]);

            Meja::query()
                ->whereKey($locked->id_meja)
                ->lockForUpdate()
                ->update(['status' => 'active']);

            $locked->loadMissing('meja.toko');
            $mejaNama = $locked->meja->nama ?? 'Meja';
            $tokoNama = $locked->meja->toko->nama ?? '';
            $deskripsi = $tokoNama !== ''
                ? "Sewa meja {$mejaNama} ({$tokoNama}) — {$locked->nama_customer}"
                : "Sewa meja {$mejaNama} — {$locked->nama_customer}";

            CashFlow::query()->firstOrCreate(
                ['id_rental' => $locked->id],
                [
                    'tipe_transaksi' => 'income',
                    'total' => $calc['total_harga'],
                    'keterangan' => $deskripsi,
                    'metode_pembayaran' => null,
                    'waktu_pembayaran' => $now,
                    'idc' => 0,
                    'idm' => 0,
                    'doc' => $now,
                    'dom' => $now,
                ]
            );

            return [
                'nama_customer' => $locked->nama_customer,
                'nama_meja' => $mejaNama,
                'nama_toko' => $tokoNama,
                'keterangan' => $deskripsi,
                'durasi_menit' => $calc['total_minutes'],
                'durasi_menit_formatted' => number_format($calc['total_minutes'], 2, ',', '.'),
                'total_harga' => $calc['total_harga'],
                'total_harga_formatted' => number_format($calc['total_harga'], 0, ',', '.'),
            ];
        });

        return response()->json([
            'message' => 'Sewa selesai. Terima kasih!',
            'summary' => $summary,
        ]);
    }

    private function availableMejasQuery(?Toko $toko)
    {
        $query = Meja::query()
            ->with('toko')
            ->where('status', 'active')
            ->orderBy('id_toko')
            ->orderBy('nama');

        if ($toko) {
            $query->where('id_toko', $toko->id);
        }

        return $query;
    }

    private function guestTokenFromRequest(Request $request): ?string
    {
        $token = $request->header('X-Guest-Token') ?? $request->query('guest_token');

        return is_string($token) && $token !== '' ? $token : null;
    }

    private function authorizeGuestRental(Request $request, Rental $rental): void
    {
        if (! $rental->isActive()) {
            abort(404);
        }

        $token = $this->guestTokenFromRequest($request);
        if (! $token || $rental->guest_token !== $token) {
            abort(403, 'Akses sewa tidak valid.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function rentalPayload(Rental $rental): array
    {
        $rental->loadMissing('meja.toko');

        return [
            'id' => $rental->id,
            'nama_customer' => $rental->nama_customer,
            'nama_meja' => $rental->meja->nama ?? '',
            'nama_toko' => $rental->meja->toko->nama ?? '',
            'harga_per_jam' => (float) $rental->harga,
            'harga_per_jam_formatted' => number_format((float) $rental->harga, 0, ',', '.'),
            'waktu_start' => $rental->waktu_start->format('d/m/Y H:i:s'),
            'start_epoch' => $rental->waktu_start->timestamp,
        ];
    }
}
