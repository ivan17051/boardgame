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
  .standings-card {
    border: 1px solid rgba(0, 97, 49, 0.12);
    border-radius: 1rem;
    box-shadow: 0 8px 24px rgba(0, 60, 30, 0.06);
    overflow: hidden;
  }
  .standings-card .card-header {
    background: rgba(0, 97, 49, 0.06);
    font-weight: 700;
    color: var(--brand-dark);
  }
  .babak-section + .babak-section {
    margin-top: 2rem;
    padding-top: 0.25rem;
    border-top: 1px dashed rgba(0, 97, 49, 0.15);
  }
  .babak-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--brand-dark);
    margin-bottom: 1rem;
  }
  .group-card {
    border: 1px solid rgba(0, 97, 49, 0.12);
    border-radius: 0.85rem;
    box-shadow: 0 4px 16px rgba(0, 60, 30, 0.05);
    height: 100%;
    overflow: hidden;
  }
  .group-card .card-header {
    background: #fff;
    font-weight: 700;
    color: #1f2937;
    border-bottom: 1px solid rgba(0, 97, 49, 0.08);
  }
  .rank-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.75rem;
    height: 1.75rem;
    border-radius: 50%;
    font-size: 0.8rem;
    font-weight: 700;
    background: #e9ecef;
    color: #495057;
  }
  .table > :not(caption) > * > * {
    vertical-align: middle;
  }
  .leader-row {
    background: rgba(0, 97, 49, 0.06);
  }
  .recap-section {
    margin-top: 1.25rem;
  }
  .recap-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--brand-dark);
    margin-bottom: 0.75rem;
  }
  .recap-card {
    border: 1px solid rgba(0, 97, 49, 0.12);
    border-radius: 0.85rem;
    box-shadow: 0 4px 16px rgba(0, 60, 30, 0.05);
    overflow: hidden;
  }
</style>
@endpush

@section('content')
  @php
    $turnamen = $standings['turnamen'] ?? [];
    $sections = $standings['sections'] ?? [];
    $recapByBabak = [];

    foreach ($standings['recap'] ?? [] as $recapSection) {
      $babak = (int) ($recapSection['babak'] ?? 0);
      if ($babak > 0) {
        $recapByBabak[$babak] = $recapSection['standings'] ?? [];
      }
    }

    if (empty($sections) && ! empty($standings['groups'])) {
      $groupsByBabak = [];
      foreach ($standings['groups'] as $group) {
        $babak = (int) ($group['babak'] ?? 1);
        if (! isset($groupsByBabak[$babak])) {
          $groupsByBabak[$babak] = [];
        }
        $standingsRows = [];
        $rank = 1;
        foreach ($group['members'] ?? [] as $member) {
          $standingsRows[] = array_merge($member, [
            'rank' => $rank,
            'grup_nama' => $group['nama'] ?? null,
            'poin_didapat' => $member['poin_babak'] ?? $member['poin_didapat'] ?? 0,
          ]);
          $rank++;
        }
        $groupsByBabak[$babak][] = [
          'id' => $group['id'] ?? null,
          'nama' => $group['nama'] ?? 'Grup',
          'standings' => $standingsRows,
        ];
      }
      ksort($groupsByBabak);
      foreach ($groupsByBabak as $babak => $groups) {
        $recapRows = collect($groups)
          ->flatMap(fn ($group) => $group['standings'] ?? [])
          ->sortByDesc(fn ($row) => (int) ($row['poin_babak'] ?? $row['poin_didapat'] ?? 0))
          ->values()
          ->map(fn ($row, $index) => array_merge($row, ['rank' => $index + 1]))
          ->all();

        $sections[] = [
          'babak' => $babak,
          'is_active' => false,
          'groups' => $groups,
          'recap' => $recapRows,
        ];
      }
    }

    foreach ($sections as $index => $section) {
      if (empty($section['recap'])) {
        $babak = (int) ($section['babak'] ?? 0);
        if ($babak > 0 && ! empty($recapByBabak[$babak])) {
          $sections[$index]['recap'] = $recapByBabak[$babak];
        }
      }
    }

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
    <h1><i class="bi bi-bar-chart-line me-2"></i>Klasemen</h1>
    <p>{{ $turnamen['nama'] ?? 'Turnamen Mahjong' }}</p>
    <div class="mt-2">
      <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
      @if (! empty($turnamen['mahjong_is_final']))
        <span class="badge text-bg-warning text-dark">Final</span>
      @endif
    </div>
  </header>

  @if (empty($sections))
    <div class="card standings-card">
      <div class="card-body text-center text-secondary py-5">
        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
        Belum ada data klasemen.
      </div>
    </div>
  @else
    @foreach ($sections as $section)
      <section class="babak-section mb-4">
        <div class="d-flex flex-wrap align-items-center gap-2 babak-title">
          <span><i class="bi bi-layers me-1 text-primary"></i>Babak {{ $section['babak'] ?? '—' }}</span>
          @if (! empty($section['is_active']))
            <span class="badge text-bg-success">Berlangsung</span>
          @endif
        </div>

        @if (empty($section['groups']))
          <div class="alert alert-light border mb-0">Belum ada data pemain pada babak ini.</div>
        @else
          <div class="row g-3">
            @foreach ($section['groups'] as $group)
              <div class="col-lg-6">
                <div class="card group-card">
                  <div class="card-header py-3">
                    <i class="bi bi-diagram-3 me-2 text-primary"></i>{{ $group['nama'] ?? 'Grup' }}
                  </div>
                  <div class="table-responsive">
                    <table class="table table-hover mb-0">
                      <thead class="table-light">
                        <tr>
                          <th style="width: 3.5rem;" class="text-center">#</th>
                          <th>Pemain</th>
                          <th class="text-end">Poin Babak</th>
                          <th class="text-end">Total</th>
                        </tr>
                      </thead>
                      <tbody>
                        @forelse ($group['standings'] ?? [] as $row)
                          @php $rank = $row['rank'] ?? 0; @endphp
                          <tr class="{{ $rank === 1 ? 'leader-row' : '' }}">
                            <td class="text-center">
                              @if ($rank === 1)
                                <i class="bi bi-trophy-fill text-warning"></i>
                              @else
                                <span class="rank-badge">{{ $rank }}</span>
                              @endif
                            </td>
                            <td class="fw-semibold">{{ $row['nama'] ?? '—' }}</td>
                            <td class="text-end">
                              {{ number_format((int) ($row['poin_babak'] ?? $row['poin_didapat'] ?? 0), 0, ',', '.') }}
                            </td>
                            <td class="text-end fw-semibold">
                              {{ number_format((int) ($row['total_poin'] ?? 0), 0, ',', '.') }}
                            </td>
                          </tr>
                        @empty
                          <tr>
                            <td colspan="4" class="text-center text-secondary py-4">Belum ada pemain di grup ini.</td>
                          </tr>
                        @endforelse
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            @endforeach
          </div>

          @if (! empty($section['recap']))
            <div class="recap-section">
              <h3 class="recap-title">
                <i class="bi bi-table me-1 text-primary"></i>Rekap Babak {{ $section['babak'] ?? '—' }}
              </h3>
              <div class="card recap-card">
                <div class="table-responsive">
                  <table class="table table-hover mb-0">
                    <thead class="table-light">
                      <tr>
                        <th style="width: 3.5rem;" class="text-center">#</th>
                        <th>Pemain</th>
                        <th class="text-center d-none d-md-table-cell">Grup</th>
                        <th class="text-center">Poin Babak</th>
                        <th class="text-center">Total</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach ($section['recap'] as $row)
                        @php $rank = $row['rank'] ?? 0; @endphp
                        <tr class="{{ $rank === 1 ? 'leader-row' : '' }}">
                          <td class="text-center">
                            @if ($rank === 1)
                              <i class="bi bi-trophy-fill text-warning"></i>
                            @else
                              <span class="rank-badge">{{ $rank }}</span>
                            @endif
                          </td>
                          <td class="fw-semibold">{{ $row['nama'] ?? '—' }}</td>
                          <td class="text-center text-secondary d-none d-md-table-cell">{{ $row['grup_nama'] ?? '—' }}</td>
                          <td class="text-center">
                            <span class="badge text-bg-secondary">{{ number_format((int) ($row['poin_babak'] ?? $row['poin_didapat'] ?? 0), 0, ',', '.') }}</span>
                          </td>
                          <td class="text-center">
                            <span class="badge text-bg-primary">{{ number_format((int) ($row['total_poin'] ?? 0), 0, ',', '.') }}</span>
                          </td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          @endif
        @endif
      </section>
    @endforeach
  @endif
@endsection
