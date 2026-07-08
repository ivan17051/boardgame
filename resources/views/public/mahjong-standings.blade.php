@extends('layouts.public')

@section('title', 'Klasemen — ' . ($standings['turnamen']['nama'] ?? 'Turnamen Mahjong') . ' — Omahjong')

@push('styles')
<style>
  .page-header h1 {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--brand);
    margin: 0;
  }
  .page-header p {
    color: #6c757d;
    margin: 0.35rem 0 0;
  }
  .babak-section {
    margin-bottom: 1.75rem;
  }
  .babak-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--brand-dark);
    margin-bottom: 0.85rem;
  }
  .standings-table-card {
    border: 1px solid rgba(0, 97, 49, 0.12);
    border-radius: 0.85rem;
    box-shadow: 0 4px 16px rgba(0, 60, 30, 0.05);
    overflow: hidden;
  }
  .standings-table-card table {
    margin-bottom: 0;
  }
  .standings-table-card thead th {
    background: #f4f7f5;
    color: #495057;
    font-weight: 700;
    border-bottom: 1px solid rgba(0, 97, 49, 0.1);
    white-space: nowrap;
  }
  .table > :not(caption) > * > * {
    vertical-align: middle;
  }
  .leader-row {
    background: rgba(0, 97, 49, 0.08);
  }
  .rank-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.6rem;
    font-weight: 700;
    color: #495057;
  }
  .score-pill {
    display: inline-block;
    min-width: 2.25rem;
    padding: 0.25rem 0.6rem;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 700;
    background: #2f3b34;
    color: #fff;
  }
  .total-pill {
    display: inline-block;
    min-width: 2.25rem;
    padding: 0.25rem 0.6rem;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 700;
    background: #f5b544;
    color: #5c3d00;
  }
</style>
@endpush

@section('content')
  @php
    $turnamen = $standings['turnamen'] ?? [];
    $sections = collect($standings['sections'] ?? [])
      ->sortByDesc(fn ($section) => (int) ($section['babak'] ?? 0))
      ->values()
      ->all();

    $status = $turnamen['status'] ?? '';
    if ($status === 'ongoing') {
      $statusClass = 'text-bg-primary';
      $statusLabel = 'Berlangsung';
    } elseif ($status === 'completed') {
      $statusClass = 'text-bg-secondary';
      $statusLabel = 'Selesai';
    } else {
      $statusClass = 'text-bg-light text-dark';
      $statusLabel = ucfirst($status);
    }
  @endphp

  <header class="page-header mb-4">
    <a href="{{ route('home') }}" class="btn btn-sm btn-outline-secondary mb-3">
      <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
    <h1><i class="bi bi-bar-chart-line me-2"></i>Klasemen Mahjong</h1>
    <p>{{ $turnamen['nama'] ?? 'Turnamen Mahjong' }}</p>
    <div class="mt-2">
      <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
      @if (! empty($turnamen['mahjong_is_final']))
        <span class="badge text-bg-warning text-dark">Final</span>
      @endif
    </div>
  </header>

  @if (! empty($standingsError))
    <div class="alert alert-warning" role="alert">
      <i class="bi bi-exclamation-triangle me-1"></i>{{ $standingsError }}
    </div>
  @endif

  @if (empty($sections))
    <div class="card standings-table-card">
      <div class="card-body text-center text-secondary py-5">
        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
        Belum ada data klasemen.
      </div>
    </div>
  @else
    @foreach ($sections as $section)
      @php
        $rounds = $section['rounds'] ?? [];
        $roundCount = count($rounds);
        $rows = $section['rows'] ?? [];
      @endphp
      <section class="babak-section">
        <div class="d-flex flex-wrap align-items-center gap-2 babak-title">
          <span><i class="bi bi-layers me-1 text-primary"></i>Babak {{ $section['babak'] ?? '—' }}</span>
          @if (! empty($section['is_active']))
            <span class="badge text-bg-success">Berlangsung</span>
          @endif
        </div>

        <div class="card standings-table-card">
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th style="width: 3.5rem;" class="text-center">#</th>
                  <th>Pemain</th>
                  @foreach ($rounds as $round)
                    <th class="text-center">{{ $round['label'] ?? ('Ronde ' . ($round['round'] ?? '')) }}</th>
                  @endforeach
                  <th class="text-center">Total Babak</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($rows as $row)
                  @php
                    $rank = (int) ($row['rank'] ?? 0);
                    $roundScores = $row['round_scores'] ?? [];
                  @endphp
                  <tr class="{{ $rank === 1 ? 'leader-row' : '' }}">
                    <td class="text-center">
                      @if ($rank === 1)
                        <i class="bi bi-trophy-fill text-warning"></i>
                      @else
                        <span class="rank-num">{{ $rank }}</span>
                      @endif
                    </td>
                    <td class="fw-semibold">{{ $row['nama'] ?? '—' }}</td>
                    @for ($i = 0; $i < $roundCount; $i++)
                      <td class="text-center">
                        @if (array_key_exists($i, $roundScores))
                          <span class="score-pill">{{ (int) $roundScores[$i] }}</span>
                        @else
                          <span class="text-muted">—</span>
                        @endif
                      </td>
                    @endfor
                    <td class="text-center">
                      <span class="total-pill">{{ (int) ($row['total_babak'] ?? 0) }}</span>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="{{ 3 + $roundCount }}" class="text-center text-secondary py-4">
                      Belum ada data pemain pada babak ini.
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </section>
    @endforeach
  @endif
@endsection
