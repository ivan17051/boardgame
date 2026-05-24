<?php

namespace App\Http\Controllers;

use App\Models\AdditionalItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdditionalItemController extends Controller
{
    public function index()
    {
        $items = AdditionalItem::query()->orderBy('nama')->get();

        return view('additional-items.index', compact('items'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'harga' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $now = now();
        $uid = auth()->id();

        AdditionalItem::query()->create([
            'nama' => $validated['nama'],
            'harga' => $validated['harga'],
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
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'harga' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $additionalItem->update([
            'nama' => $validated['nama'],
            'harga' => $validated['harga'],
            'is_active' => $request->boolean('is_active', true),
            'dom' => now(),
            'idm' => auth()->id(),
        ]);

        return response()->json(['message' => 'Item tambahan diperbarui.']);
    }

    public function destroy(AdditionalItem $additionalItem): JsonResponse
    {
        $additionalItem->delete();

        return response()->json(['message' => 'Item tambahan dihapus.']);
    }
}
