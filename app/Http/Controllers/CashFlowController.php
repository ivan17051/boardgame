<?php

namespace App\Http\Controllers;

use App\Models\CashFlow;
use App\Models\Rental;
use App\Support\RentalInvoice;
use App\Support\RentalPayment;
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

        $countedRentalPayments = [];
        $totalIncomeBayar = 0.0;
        foreach ($incomeRows as $row) {
            if ($row->id_rental) {
                if (isset($countedRentalPayments[$row->id_rental])) {
                    continue;
                }
                $countedRentalPayments[$row->id_rental] = true;
                $rental = $row->rental;
                $totalIncomeBayar += $rental ? $rental->amountPaid() : (float) $row->total;
            } else {
                $totalIncomeBayar += (float) ($row->jumlah_bayar ?? $row->total);
            }
        }

        $summary = [
            'total_income_tagihan' => (float) $incomeRows->sum(fn (CashFlow $r) => (float) $r->total),
            'total_income_bayar' => $totalIncomeBayar,
            'total_sewa_meja' => (float) $incomeRows
                ->filter(fn (CashFlow $r) => $r->kategori_pendapatan === CashFlow::KATEGORI_SEWA_MEJA)
                ->sum(fn (CashFlow $r) => (float) $r->total),
            'total_additional_fb' => (float) $incomeRows
                ->filter(fn (CashFlow $r) => $r->kategori_pendapatan === CashFlow::KATEGORI_ADDITIONAL_FB)
                ->sum(fn (CashFlow $r) => (float) $r->total),
            'total_expense' => (float) $expenseRows->sum(fn (CashFlow $r) => (float) $r->total),
            'count_income' => $incomeRows->count(),
            'count_lengkap' => $incomeRows->filter(fn (CashFlow $r) => $r->kelengkapanStatus() === 'lengkap')->count(),
            'count_belum_lengkap' => $incomeRows->filter(fn (CashFlow $r) => $r->kelengkapanStatus() !== 'lengkap')->count(),
            'by_metode' => $this->incomeByMetode($incomeRows),
        ];

        $summary['net'] = $summary['total_income_bayar'] - $summary['total_expense'];

        $data = compact('entries', 'incomeRows', 'expenseRows', 'summary', 'dateFrom', 'dateTo');

        if ($request->input('print') === '1') {
            return view('cashflow.report-print', $data);
        }

        return view('cashflow.report', $data);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, CashFlow>  $incomeRows
     * @return \Illuminate\Support\Collection<string, array{count: int, total: float}>
     */
    private function incomeByMetode($incomeRows)
    {
        $counted = [];
        $groups = [];

        foreach ($incomeRows as $row) {
            if ($row->id_rental) {
                if (isset($counted[$row->id_rental])) {
                    continue;
                }
                $counted[$row->id_rental] = true;
                $metode = $row->rental ? $row->rental->metode_pembayaran : null;
                $paid = $row->rental ? $row->rental->amountPaid() : (float) $row->total;
            } else {
                $metode = $row->metode_pembayaran;
                $paid = (float) ($row->jumlah_bayar ?? $row->total);
            }

            if (empty($metode)) {
                continue;
            }

            if (! isset($groups[$metode])) {
                $groups[$metode] = ['count' => 0, 'total' => 0.0];
            }
            $groups[$metode]['count']++;
            $groups[$metode]['total'] += $paid;
        }

        return collect($groups);
    }

    public function invoice(CashFlow $cashFlow)
    {
        if (! $cashFlow->isIncome() || $cashFlow->kelengkapanStatus() !== 'lengkap') {
            abort(404);
        }

        TokoScope::authorizeCashFlow($cashFlow);

        if (! $cashFlow->id_rental) {
            abort(404);
        }

        $rental = Rental::query()->findOrFail($cashFlow->id_rental);
        TokoScope::authorizeRental($rental);

        if (! RentalInvoice::canIssue($rental)) {
            abort(404);
        }

        return view('cashflow.invoice', RentalInvoice::build($rental));
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

        if ($cashFlow->id_rental) {
            $rental = Rental::query()->findOrFail($cashFlow->id_rental);
            TokoScope::authorizeRental($rental);

            RentalPayment::saveOnRental(
                $rental,
                $validated['metode_pembayaran'],
                (float) $validated['jumlah_bayar'],
                null,
                $rental->waktu_pembayaran ?? $cashFlow->waktu_pembayaran
            );

            $rental->refresh();

            return response()->json($this->paymentJsonFromRental($rental, 'Data pembayaran disimpan.'));
        }

        $now = now();
        $uid = auth()->id();

        $cashFlow->update([
            'metode_pembayaran' => $validated['metode_pembayaran'],
            'jumlah_bayar' => $validated['jumlah_bayar'],
            'dom' => $now,
            'idm' => $uid,
        ]);

        $cashFlow->refresh();

        return response()->json($this->paymentJsonFromCashFlow($cashFlow, 'Data pembayaran disimpan.'));
    }

    public function showBukti(CashFlow $cashFlow): BinaryFileResponse
    {
        TokoScope::authorizeCashFlow($cashFlow);

        if ($cashFlow->id_rental) {
            $rental = Rental::query()->findOrFail($cashFlow->id_rental);
            TokoScope::authorizeRental($rental);

            return app(RentalController::class)->showBukti($rental);
        }

        if (empty($cashFlow->bukti_transaksi)) {
            abort(404);
        }

        return $this->serveBuktiFile($cashFlow->bukti_transaksi);
    }

    public function uploadBukti(Request $request, CashFlow $cashFlow): JsonResponse
    {
        TokoScope::authorizeCashFlow($cashFlow);

        $validated = $request->validate([
            'bukti' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,pdf'],
        ]);

        if ($cashFlow->id_rental) {
            $rental = Rental::query()->findOrFail($cashFlow->id_rental);
            TokoScope::authorizeRental($rental);

            $path = RentalPayment::storeBukti($validated['bukti'], $rental->bukti_transaksi);
            $rental->update([
                'bukti_transaksi' => $path,
            ]);
            $rental->refresh();

            return response()->json($this->paymentJsonFromRental($rental, 'Bukti transaksi berhasil diunggah.'));
        }

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

        return response()->json($this->paymentJsonFromCashFlow($cashFlow, 'Bukti transaksi berhasil diunggah.'));
    }

    private function serveBuktiFile(string $relativePath): BinaryFileResponse
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($relativePath)) {
            abort(404);
        }

        $path = $disk->path($relativePath);
        $mime = $disk->mimeType($relativePath) ?: 'application/octet-stream';

        return response()->file($path, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.basename($relativePath).'"',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentJsonFromRental(Rental $rental, string $message): array
    {
        return [
            'message' => $message,
            'metode_pembayaran' => $rental->metode_pembayaran,
            'metode_pembayaran_label' => CashFlow::metodePembayaranLabel($rental->metode_pembayaran),
            'jumlah_bayar' => (float) $rental->jumlah_bayar,
            'jumlah_bayar_formatted' => number_format((float) $rental->jumlah_bayar, 0, ',', '.'),
            'bukti_url' => $rental->buktiUrl(),
            'status' => $rental->kelengkapanStatus(),
            'status_label' => $rental->kelengkapanStatusLabel(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentJsonFromCashFlow(CashFlow $cashFlow, string $message): array
    {
        return [
            'message' => $message,
            'metode_pembayaran' => $cashFlow->metode_pembayaran,
            'metode_pembayaran_label' => CashFlow::metodePembayaranLabel($cashFlow->metode_pembayaran),
            'jumlah_bayar' => (float) $cashFlow->jumlah_bayar,
            'jumlah_bayar_formatted' => number_format((float) $cashFlow->jumlah_bayar, 0, ',', '.'),
            'bukti_url' => $cashFlow->buktiUrl(),
            'status' => $cashFlow->kelengkapanStatus(),
            'status_label' => $cashFlow->kelengkapanStatusLabel(),
        ];
    }
}
