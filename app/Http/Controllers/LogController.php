<?php

namespace App\Http\Controllers;

use App\Models\AppLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $search = trim((string) ($validated['q'] ?? ''));
        $dateFrom = $validated['date_from'] ?? '';
        $dateTo = $validated['date_to'] ?? '';

        $query = AppLog::query()->orderByDesc('id');

        if ($dateFrom !== '') {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo !== '') {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like, $search) {
                $q->where('user_name', 'like', $like)
                    ->orWhere('action', 'like', $like)
                    ->orWhere('table_name', 'like', $like);

                if (ctype_digit($search)) {
                    $q->orWhere('record_id', (int) $search)
                        ->orWhere('user_id', (int) $search)
                        ->orWhere('id', (int) $search);
                }
            });
        }

        $logs = $query->paginate(25)->withQueryString();

        return view('logs.index', [
            'logs' => $logs,
            'search' => $search,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    public function show(AppLog $log): View
    {
        return view('logs.show', [
            'log' => $log,
            'changed' => $log->changedFields(),
            'original' => $log->originalPayload() ?? [],
            'new' => $log->newPayload() ?? [],
        ]);
    }
}
