<?php

namespace App\Http\Controllers;

use App\Models\CashFlow;
use App\Models\Meja;
use App\Models\Rental;
use App\Models\Toko;
use App\Support\TokoScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $tokos = TokoScope::scopeTokos(Toko::query())
            ->orderBy('nama')
            ->get(['id', 'nama']);

        $canSeeAll = TokoScope::canSeeAll();
        $selectedTokoId = $this->resolveSelectedTokoId($request, $tokos, $canSeeAll);
        $selectedToko = $selectedTokoId
            ? $tokos->firstWhere('id', $selectedTokoId)
            : null;

        $today = now()->startOfDay();
        $monthStart = now()->startOfMonth();

        $cashflowBase = $this->scopeCashFlowsForDashboard($selectedTokoId)
            ->where('tipe_transaksi', 'income');
        $rentalBase = $this->scopeRentalsForDashboard($selectedTokoId);
        $mejaBase = $this->scopeMejasForDashboard($selectedTokoId);

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
            'pending_payment' => (clone $rentalBase)
                ->where('status', 'completed')
                ->incompleteKelengkapan()
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
            'recentCashflow',
            'tokos',
            'selectedTokoId',
            'selectedToko',
            'canSeeAll'
        ));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Toko>  $tokos
     */
    private function resolveSelectedTokoId(Request $request, $tokos, bool $canSeeAll): ?int
    {
        if ($request->filled('id_toko')) {
            $id = (int) $request->query('id_toko');
            if ($tokos->contains('id', $id)) {
                return $id;
            }
        }

        if (! $canSeeAll && $tokos->count() === 1) {
            return (int) $tokos->first()->id;
        }

        return null;
    }

    private function scopeRentalsForDashboard(?int $tokoId): Builder
    {
        $query = TokoScope::scopeRentals(Rental::query());

        if ($tokoId) {
            $query->whereHas('meja', function (Builder $q) use ($tokoId) {
                $q->where('id_toko', $tokoId);
            });
        }

        return $query;
    }

    private function scopeMejasForDashboard(?int $tokoId): Builder
    {
        $query = TokoScope::scopeMejas(Meja::query());

        if ($tokoId) {
            $query->where('id_toko', $tokoId);
        }

        return $query;
    }

    private function scopeCashFlowsForDashboard(?int $tokoId): Builder
    {
        $query = TokoScope::scopeCashFlows(CashFlow::query());

        if ($tokoId) {
            $query->whereHas('rental.meja', function (Builder $q) use ($tokoId) {
                $q->where('id_toko', $tokoId);
            });
        }

        return $query;
    }
}
