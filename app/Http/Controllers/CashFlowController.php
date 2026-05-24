<?php

namespace App\Http\Controllers;

use App\Models\CashFlow;
use App\Support\TokoScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CashFlowController extends Controller
{
    public function index(Request $request)
    {
        $filterDate = $request->input('date', now()->toDateString());
        $onlyBelumLengkap = $request->input('belum_lengkap', '1') !== '0';

        $query = TokoScope::scopeCashFlows(CashFlow::query())
            ->with(['rental.meja.toko']);

        if ($filterDate !== '' && $filterDate !== 'all') {
            $query->whereDate('waktu_pembayaran', $filterDate);
        }

        if ($onlyBelumLengkap) {
            $query->where('tipe_transaksi', 'income')
                ->incompleteKelengkapan();
        }

        $entries = $query
            ->orderByDesc('waktu_pembayaran')
            ->orderByDesc('id')
            ->get();

        return view('cashflow.index', compact('entries', 'filterDate', 'onlyBelumLengkap'));
    }

    public function report(Request $request)
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'print' => ['nullable', 'in:1'],
        ]);

        $dateFrom = $validated['date_from'] ?? now()->toDateString();
        $dateTo = $validated['date_to'] ?? $dateFrom;

        $entries = TokoScope::scopeCashFlows(CashFlow::query())
            ->with(['rental.meja.toko'])
            ->whereDate('waktu_pembayaran', '>=', $dateFrom)
            ->whereDate('waktu_pembayaran', '<=', $dateTo)
            ->orderBy('waktu_pembayaran')
            ->orderBy('id')
            ->get();

        $incomeRows = $entries->filter(fn (CashFlow $row) => $row->isIncome());
        $expenseRows = $entries->filter(fn (CashFlow $row) => ! $row->isIncome());

        $summary = [
            'total_income_tagihan' => (float) $incomeRows->sum(fn (CashFlow $r) => (float) $r->total),
            'total_income_bayar' => (float) $incomeRows->sum(fn (CashFlow $r) => $r->amountPaid()),
            'total_sewa_meja' => (float) $incomeRows
                ->filter(fn (CashFlow $r) => $r->kategori_pendapatan === CashFlow::KATEGORI_SEWA_MEJA)
                ->sum(fn (CashFlow $r) => $r->amountPaid()),
            'total_additional_fb' => (float) $incomeRows
                ->filter(fn (CashFlow $r) => $r->kategori_pendapatan === CashFlow::KATEGORI_ADDITIONAL_FB)
                ->sum(fn (CashFlow $r) => $r->amountPaid()),
            'total_expense' => (float) $expenseRows->sum(fn (CashFlow $r) => (float) $r->total),
            'count_income' => $incomeRows->count(),
            'count_lengkap' => $incomeRows->filter(fn (CashFlow $r) => $r->kelengkapanStatus() === 'lengkap')->count(),
            'count_belum_lengkap' => $incomeRows->filter(fn (CashFlow $r) => $r->kelengkapanStatus() !== 'lengkap')->count(),
            'by_metode' => $incomeRows
                ->filter(fn (CashFlow $r) => ! empty($r->metode_pembayaran))
                ->groupBy('metode_pembayaran')
                ->map(fn ($group) => [
                    'count' => $group->count(),
                    'total' => (float) $group->sum(fn (CashFlow $r) => $r->amountPaid()),
                ]),
        ];

        $summary['net'] = $summary['total_income_bayar'] - $summary['total_expense'];

        $data = compact('entries', 'incomeRows', 'expenseRows', 'summary', 'dateFrom', 'dateTo');

        if ($request->input('print') === '1') {
            return view('cashflow.report-print', $data);
        }

        return view('cashflow.report', $data);
    }

    public function invoice(CashFlow $cashFlow)
    {
        if (! $cashFlow->isIncome() || $cashFlow->kelengkapanStatus() !== 'lengkap') {
            abort(404);
        }

        TokoScope::authorizeCashFlow($cashFlow);

        $cashFlow->load(['rental.meja.toko']);

        return view('cashflow.invoice', compact('cashFlow'));
    }

    public function updatePaymentMethod(Request $request, CashFlow $cashFlow): JsonResponse
    {
        if (! $cashFlow->isIncome()) {
            abort(404);
        }

        TokoScope::authorizeCashFlow($cashFlow);

        $validated = $request->validate([
            'metode_pembayaran' => ['required', 'string', 'max:100'],
            'jumlah_bayar' => ['required', 'numeric', 'min:0'],
        ]);

        $now = now();
        $uid = auth()->id();

        $cashFlow->update([
            'metode_pembayaran' => $validated['metode_pembayaran'],
            'jumlah_bayar' => $validated['jumlah_bayar'],
            'dom' => $now,
            'idm' => $uid,
        ]);

        $cashFlow->refresh();

        return response()->json([
            'message' => 'Data pembayaran disimpan.',
            'metode_pembayaran' => $cashFlow->metode_pembayaran,
            'metode_pembayaran_label' => CashFlow::metodePembayaranLabel($cashFlow->metode_pembayaran),
            'jumlah_bayar' => (float) $cashFlow->jumlah_bayar,
            'jumlah_bayar_formatted' => number_format((float) $cashFlow->jumlah_bayar, 0, ',', '.'),
            'status' => $cashFlow->kelengkapanStatus(),
            'status_label' => $cashFlow->kelengkapanStatusLabel(),
        ]);
    }

    public function showBukti(CashFlow $cashFlow): BinaryFileResponse
    {
        if (empty($cashFlow->bukti_transaksi)) {
            abort(404);
        }

        TokoScope::authorizeCashFlow($cashFlow);

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
        TokoScope::authorizeCashFlow($cashFlow);

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
