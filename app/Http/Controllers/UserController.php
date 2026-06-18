<?php

namespace App\Http\Controllers;

use App\Models\Toko;
use App\Models\User;
use App\Support\TokoScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $showHidden = $request->boolean('show_hidden');

        $usersQuery = TokoScope::scopeUsers(User::query())
            ->with('toko')
            ->orderBy('username');

        if (! $showHidden) {
            $usersQuery->visible();
        }

        $users = $usersQuery->get();

        $tokos = Toko::query()->orderBy('nama')->get(['id', 'nama']);
        $canAssignAnyToko = TokoScope::canSeeAll();

        return view('users.index', compact('users', 'tokos', 'canAssignAnyToko', 'showHidden'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'nama' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'string', 'max:255'],
            'id_toko' => ['required', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'is_hidden' => ['sometimes', 'boolean'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $idToko = TokoScope::resolveIdTokoForSave($validated['id_toko']);
        $this->assertIdTokoAllowed($idToko);

        $validated['id_toko'] = $idToko;
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['is_hidden'] = $request->boolean('is_hidden', false);
        $validated['password'] = Hash::make($validated['password']);
        unset($validated['password_confirmation']);

        if (empty($validated['nama'])) {
            $validated['nama'] = $validated['username'];
        }

        User::query()->create($validated);

        return response()->json(['message' => 'Pengguna ditambahkan.']);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        TokoScope::authorizeUser($user);

        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'nama' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'string', 'max:255'],
            'id_toko' => ['required', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'is_hidden' => ['sometimes', 'boolean'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $idToko = TokoScope::resolveIdTokoForSave($validated['id_toko']);
        $this->assertIdTokoAllowed($idToko);

        $validated['id_toko'] = $idToko;
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_hidden'] = $request->boolean('is_hidden');

        if ((int) $user->id === (int) auth()->id() && ! $validated['is_active']) {
            return response()->json(['message' => 'Tidak dapat menonaktifkan akun sendiri.'], 422);
        }

        if ((int) $user->id === (int) auth()->id() && $validated['is_hidden']) {
            return response()->json(['message' => 'Tidak dapat menyembunyikan akun sendiri.'], 422);
        }

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }
        unset($validated['password_confirmation']);

        if (empty($validated['nama'])) {
            $validated['nama'] = $validated['username'];
        }

        $user->update($validated);

        return response()->json(['message' => 'Pengguna diperbarui.']);
    }

    public function destroy(User $user): JsonResponse
    {
        TokoScope::authorizeUser($user);

        if ((int) $user->id === (int) auth()->id()) {
            return response()->json(['message' => 'Tidak dapat menghapus akun sendiri.'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'Pengguna dihapus.']);
    }

    private function assertIdTokoAllowed(int $idToko): void
    {
        if ($idToko === 0) {
            if (! TokoScope::canSeeAll()) {
                abort(403);
            }

            return;
        }

        if (! Toko::query()->whereKey($idToko)->exists()) {
            abort(422, 'Toko tidak valid.');
        }
    }
}
