<?php

namespace App\Http\Controllers;

use App\Models\CashFlow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CashFlowController extends Controller
{
    public function index()
    {
        $entries = CashFlow::query()
            ->with(['rental.meja.toko'])
            ->orderByDesc('waktu_pembayaran')
            ->orderByDesc('id')
            ->get();

        return view('cashflow.index', compact('entries'));
    }

    public function updatePaymentMethod(Request $request, CashFlow $cashFlow): JsonResponse
    {
        if (! $cashFlow->isIncome()) {
            abort(404);
        }

        $validated = $request->validate([
            'metode_pembayaran' => ['required', 'string', 'max:100'],
        ]);

        $now = now();
        $uid = auth()->id();

        $cashFlow->update([
            'metode_pembayaran' => $validated['metode_pembayaran'],
            'dom' => $now,
            'idm' => $uid,
        ]);

        $cashFlow->refresh();

        return response()->json([
            'message' => 'Metode pembayaran disimpan.',
            'metode_pembayaran' => $cashFlow->metode_pembayaran,
            'metode_pembayaran_label' => CashFlow::metodePembayaranLabel($cashFlow->metode_pembayaran),
            'status' => $cashFlow->kelengkapanStatus(),
            'status_label' => $cashFlow->kelengkapanStatusLabel(),
        ]);
    }

    public function showBukti(CashFlow $cashFlow): BinaryFileResponse
    {
        if (empty($cashFlow->bukti_transaksi)) {
            abort(404);
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($cashFlow->bukti_transaksi)) {
            abort(404);
        }

        $path = $disk->path($cashFlow->bukti_transaksi);
        $mime = $disk->mimeType($cashFlow->bukti_transaksi) ?: 'application/octet-stream';

        return response()->file($path, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.basename($cashFlow->bukti_transaksi).'"',
        ]);
    }

    public function uploadBukti(Request $request, CashFlow $cashFlow): JsonResponse
    {
        $validated = $request->validate([
            'bukti' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,pdf'],
        ]);

        $disk = Storage::disk('public');
        if ($cashFlow->bukti_transaksi && $disk->exists($cashFlow->bukti_transaksi)) {
            $disk->delete($cashFlow->bukti_transaksi);
        }

        $path = $validated['bukti']->store('cash-flow-bukti', 'public');
        $now = now();

        $cashFlow->update([
            'bukti_transaksi' => $path,
            'dom' => $now,
            'idm' => auth()->id(),
        ]);

        $cashFlow->refresh();

        return response()->json([
            'message' => 'Bukti transaksi berhasil diunggah.',
            'bukti_url' => $cashFlow->buktiUrl(),
            'status' => $cashFlow->kelengkapanStatus(),
            'status_label' => $cashFlow->kelengkapanStatusLabel(),
        ]);
    }
}
