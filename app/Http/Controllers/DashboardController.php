<?php

namespace App\Http\Controllers;

use App\Models\CashFlow;
use App\Models\Meja;
use App\Models\Rental;
use App\Support\TokoScope;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today = now()->startOfDay();
        $monthStart = now()->startOfMonth();

        $cashflowBase = TokoScope::scopeCashFlows(CashFlow::query())->where('tipe_transaksi', 'income');
        $rentalBase = TokoScope::scopeRentals(Rental::query());
        $mejaBase = TokoScope::scopeMejas(Meja::query());

        $stats = [
            'active_rentals' => (clone $rentalBase)->where('status', 'active')->count(),
            'completed_today' => (clone $rentalBase)
                ->where('status', 'completed')
                ->where('waktu_end', '>=', $today)
                ->count(),
            'income_today' => (float) (clone $cashflowBase)
                ->where('waktu_pembayaran', '>=', $today)
                ->sum('total'),
            'income_month' => (float) (clone $cashflowBase)
                ->where('waktu_pembayaran', '>=', $monthStart)
                ->sum('total'),
            'pending_payment' => (clone $cashflowBase)
                ->where(function ($q) {
                    $q->whereNull('metode_pembayaran')
                        ->orWhere(function ($q2) {
                            $q2->where('metode_pembayaran', '!=', 'tunai')
                                ->whereNull('bukti_transaksi');
                        });
                })
                ->count(),
            'meja_rented' => (clone $mejaBase)->where('status', 'rented')->count(),
            'meja_available' => (clone $mejaBase)->where('status', 'active')->count(),
            'total_meja' => (clone $mejaBase)->count(),
        ];

        $chartDays = collect();
        for ($i = 6; $i >= 0; $i--) {
            $chartDays->push(now()->subDays($i)->startOfDay());
        }

        $incomeByDay = (clone $cashflowBase)
            ->where('waktu_pembayaran', '>=', $chartDays->first())
            ->select(
                DB::raw('DATE(waktu_pembayaran) as day'),
                DB::raw('SUM(total) as total')
            )
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        $chartLabels = [];
        $chartValues = [];
        foreach ($chartDays as $day) {
            $key = $day->format('Y-m-d');
            $chartLabels[] = $day->format('d/m');
            $chartValues[] = (float) ($incomeByDay[$key] ?? 0);
        }

        $recentCashflow = (clone $cashflowBase)
            ->with(['rental.meja.toko'])
            ->orderByDesc('waktu_pembayaran')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return view('index', compact(
            'stats',
            'chartLabels',
            'chartValues',
            'recentCashflow'
        ));
    }
}
