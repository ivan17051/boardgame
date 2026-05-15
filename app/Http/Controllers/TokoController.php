<?php

namespace App\Http\Controllers;

use App\Models\Meja;
use App\Models\Toko;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TokoController extends Controller
{
    public function index()
    {
        $tokos = Toko::query()
            ->with(['meja' => fn ($q) => $q->orderBy('id')])
            ->orderBy('nama')
            ->get();

        return view('toko.index', compact('tokos'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'alamat' => ['nullable', 'string'],
            'jumlah_meja' => ['required', 'integer', 'min:0'],
            'meja' => ['required', 'array'],
            'meja.*.nama' => ['required', 'string', 'max:255'],
            'meja.*.harga' => ['required', 'numeric', 'min:0'],
        ]);

        $this->assertMejaCountMatches($validated);

        $now = now();
        $uid = auth()->id();

        DB::transaction(function () use ($validated, $now, $uid) {
            $tokoData = [
                'nama' => $validated['nama'],
                'alamat' => $validated['alamat'] ?? null,
                'jumlah_meja' => $validated['jumlah_meja'],
                'doc' => $now,
                'dom' => $now,
                'idm' => $uid,
            ];

            $toko = Toko::query()->create($tokoData);

            foreach ($validated['meja'] as $row) {
                Meja::query()->create([
                    'id_toko' => $toko->id,
                    'nama' => $row['nama'],
                    'harga' => $row['harga'],
                    'status' => 'active',
                    'idc' => $uid,
                    'doc' => $now,
                    'idm' => $uid,
                    'dom' => $now,
                ]);
            }
        });

        return response()->json(['message' => 'Toko ditambahkan.']);
    }

    public function update(Request $request, Toko $toko): JsonResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'alamat' => ['nullable', 'string'],
            'jumlah_meja' => ['required', 'integer', 'min:0'],
            'meja' => ['required', 'array'],
            'meja.*.nama' => ['required', 'string', 'max:255'],
            'meja.*.harga' => ['required', 'numeric', 'min:0'],
        ]);

        $this->assertMejaCountMatches($validated);

        $now = now();
        $uid = auth()->id();

        DB::transaction(function () use ($validated, $toko, $now, $uid) {
            $toko->update([
                'nama' => $validated['nama'],
                'alamat' => $validated['alamat'] ?? null,
                'jumlah_meja' => $validated['jumlah_meja'],
                'dom' => $now,
                // 'idm' => $uid,
                'idm' => 1,
            ]);

            Meja::query()->where('id_toko', $toko->id)->delete();

            foreach ($validated['meja'] as $row) {
                Meja::query()->create([
                    'id_toko' => $toko->id,
                    'nama' => $row['nama'],
                    'harga' => $row['harga'],
                    'status' => 'active',
                    // 'idc' => $uid,
                    'idc' => 1,
                    'doc' => $now,
                    // 'idm' => $uid,
                    'idm' => 1,
                    'dom' => $now,
                ]);
            }
        });

        return response()->json(['message' => 'Toko diperbarui.']);
    }

    public function destroy(Toko $toko): JsonResponse
    {
        $toko->delete();

        return response()->json(['message' => 'Toko dihapus.']);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function assertMejaCountMatches(array $validated): void
    {
        if (count($validated['meja']) !== $validated['jumlah_meja']) {
            throw ValidationException::withMessages([
                'meja' => ['Jumlah data meja harus sama dengan kolom jumlah meja.'],
            ]);
        }
    }
}
