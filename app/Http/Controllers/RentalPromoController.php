<?php

namespace App\Http\Controllers;

use App\Models\RentalPromo;
use App\Models\Toko;
use App\Support\TokoScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RentalPromoController extends Controller
{
    public function index()
    {
        $promos = TokoScope::scopeRentalPromos(RentalPromo::query())
            ->with('toko')
            ->orderBy('nama')
            ->get();

        $tokos = TokoScope::scopeTokos(Toko::query())->orderBy('nama')->get(['id', 'nama']);
        $canAssignAnyToko = TokoScope::canSeeAll();

        return view('rental-promos.index', compact('promos', 'tokos', 'canAssignAnyToko'));
    }

    public function store(Request $request): JsonResponse
    {
        $canAssignAnyToko = TokoScope::canSeeAll();

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'promo_hourly_rate' => ['required', 'numeric', 'min:0'],
            'promo_duration_limit' => ['required', 'numeric', 'min:0.01', 'max:999'],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i'],
            'tgl_awal' => ['nullable', 'date'],
            'tgl_akhir' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'id_toko' => $canAssignAnyToko
                ? ['required', 'integer', 'min:1', Rule::exists('m_toko', 'id')]
                : ['nullable'],
        ]);

        $idToko = $canAssignAnyToko
            ? (int) $validated['id_toko']
            : TokoScope::userIdToko();

        if ($idToko < 1) {
            abort(422, 'Toko wajib dipilih.');
        }

        if (! $canAssignAnyToko) {
            TokoScope::authorizeToko(Toko::query()->findOrFail($idToko));
        }

        $this->validatePromoDateRange($validated);

        $now = now();
        $uid = auth()->id();

        RentalPromo::query()->create([
            'id_toko' => $idToko,
            'nama' => $validated['nama'],
            'promo_hourly_rate' => $validated['promo_hourly_rate'],
            'promo_duration_limit' => $validated['promo_duration_limit'],
            'jam_mulai' => RentalPromo::normalizeTimeString($validated['jam_mulai'].':00'),
            'jam_selesai' => RentalPromo::normalizeTimeString($validated['jam_selesai'].':00'),
            'tgl_awal' => $validated['tgl_awal'] ?? null,
            'tgl_akhir' => $validated['tgl_akhir'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'idc' => $uid,
            'idm' => $uid,
            'doc' => $now,
            'dom' => $now,
        ]);

        return response()->json(['message' => 'Promo sewa ditambahkan.']);
    }

    public function update(Request $request, RentalPromo $rentalPromo): JsonResponse
    {
        TokoScope::authorizeRentalPromo($rentalPromo);

        $canAssignAnyToko = TokoScope::canSeeAll();

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'promo_hourly_rate' => ['required', 'numeric', 'min:0'],
            'promo_duration_limit' => ['required', 'numeric', 'min:0.01', 'max:999'],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i'],
            'tgl_awal' => ['nullable', 'date'],
            'tgl_akhir' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'id_toko' => $canAssignAnyToko
                ? ['required', 'integer', 'min:1', Rule::exists('m_toko', 'id')]
                : ['nullable'],
        ]);

        $this->validatePromoDateRange($validated);

        $update = [
            'nama' => $validated['nama'],
            'promo_hourly_rate' => $validated['promo_hourly_rate'],
            'promo_duration_limit' => $validated['promo_duration_limit'],
            'jam_mulai' => RentalPromo::normalizeTimeString($validated['jam_mulai'].':00'),
            'jam_selesai' => RentalPromo::normalizeTimeString($validated['jam_selesai'].':00'),
            'tgl_awal' => $validated['tgl_awal'] ?? null,
            'tgl_akhir' => $validated['tgl_akhir'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'dom' => now(),
            'idm' => auth()->id(),
        ];

        if ($canAssignAnyToko && isset($validated['id_toko'])) {
            $update['id_toko'] = (int) $validated['id_toko'];
        }

        $rentalPromo->update($update);

        return response()->json(['message' => 'Promo sewa diperbarui.']);
    }

    public function destroy(RentalPromo $rentalPromo): JsonResponse
    {
        TokoScope::authorizeRentalPromo($rentalPromo);

        $rentalPromo->delete();

        return response()->json(['message' => 'Promo sewa dihapus.']);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function validatePromoDateRange(array $validated): void
    {
        $awal = $validated['tgl_awal'] ?? null;
        $akhir = $validated['tgl_akhir'] ?? null;

        if ($awal && $akhir && $akhir < $awal) {
            throw ValidationException::withMessages([
                'tgl_akhir' => ['Tanggal akhir harus sama atau setelah tanggal mulai.'],
            ]);
        }
    }
}
