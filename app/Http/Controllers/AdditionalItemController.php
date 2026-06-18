<?php

namespace App\Http\Controllers;

use App\Models\AdditionalItem;
use App\Models\Toko;
use App\Support\TokoScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdditionalItemController extends Controller
{
    public function index()
    {
        $items = TokoScope::scopeAdditionalItems(AdditionalItem::query())
            ->with('toko')
            ->orderBy('nama')
            ->get();

        $tokos = TokoScope::scopeTokos(Toko::query())->orderBy('nama')->get(['id', 'nama']);
        $canAssignAnyToko = TokoScope::canSeeAll();

        return view('additional-items.index', compact('items', 'tokos', 'canAssignAnyToko'));
    }

    public function store(Request $request): JsonResponse
    {
        $canAssignAnyToko = TokoScope::canSeeAll();

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'harga' => ['required', 'numeric', 'min:0'],
            'is_discount' => ['sometimes', 'boolean'],
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

        $now = now();
        $uid = auth()->id();

        AdditionalItem::query()->create([
            'id_toko' => $idToko,
            'nama' => $validated['nama'],
            'harga' => $validated['harga'],
            'is_discount' => $request->boolean('is_discount', false),
            'is_active' => $request->boolean('is_active', true),
            'idc' => $uid,
            'idm' => $uid,
            'doc' => $now,
            'dom' => $now,
        ]);

        return response()->json(['message' => 'Item tambahan ditambahkan.']);
    }

    public function update(Request $request, AdditionalItem $additionalItem): JsonResponse
    {
        TokoScope::authorizeAdditionalItem($additionalItem);

        $canAssignAnyToko = TokoScope::canSeeAll();

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'harga' => ['required', 'numeric', 'min:0'],
            'is_discount' => ['sometimes', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'id_toko' => $canAssignAnyToko
                ? ['required', 'integer', 'min:1', Rule::exists('m_toko', 'id')]
                : ['nullable'],
        ]);

        $update = [
            'nama' => $validated['nama'],
            'harga' => $validated['harga'],
            'is_discount' => $request->boolean('is_discount', false),
            'is_active' => $request->boolean('is_active', true),
            'dom' => now(),
            'idm' => auth()->id(),
        ];

        if ($canAssignAnyToko && isset($validated['id_toko'])) {
            $update['id_toko'] = (int) $validated['id_toko'];
        }

        $additionalItem->update($update);

        return response()->json(['message' => 'Item tambahan diperbarui.']);
    }

    public function destroy(AdditionalItem $additionalItem): JsonResponse
    {
        TokoScope::authorizeAdditionalItem($additionalItem);

        $additionalItem->delete();

        return response()->json(['message' => 'Item tambahan dihapus.']);
    }
}
