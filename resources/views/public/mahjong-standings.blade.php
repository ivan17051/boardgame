@extends('layouts.public')

@section('title', 'Klasemen — ' . ($standings['turnamen']['nama'] ?? 'Turnamen Mahjong') . ' — Omahjong')

@section('og')
  @php
    $ogTurnamen = $standings['turnamen'] ?? [];
  @endphp
  @include('public.partials.og-meta', [
    'ogTournament' => $ogTurnamen,
    'ogUrl' => route('public.mahjong-tournaments.standings', $ogTurnamen['id'] ?? 0),
    'ogTitle' => 'Klasemen ' . ($ogTurnamen['nama'] ?? 'Turnamen Mahjong') . ' — Omahjong',
    'ogDescription' => 'Lihat klasemen turnamen mahjong ' . ($ogTurnamen['nama'] ?? '') . ' di Omahjong.',
    'ogImage' => $ogTurnamen['share_image_url']
      ?? \App\Support\BornpadelMahjongTournaments::tournamentShareImageUrl($ogTurnamen['foto'] ?? null),
  ])
@endsection

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
  .standings-heading {
    color: var(--brand-dark);
  }
  .babak-section {
    margin-bottom: 1.75rem;
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
  .standings-note {
    background: #f8faf9;
  }
</style>
@endpush

@section('content')
  @php
    $turnamen = $tournament ?? ($standings['turnamen'] ?? []);
    $idKategori = $idKategori ?? null;
    $sections = collect($standings['sections'] ?? [])
      ->sortByDesc(function ($section) {
        return (int) ($section['babak'] ?? 0);
      })
      ->values()
      ->all();
    $isMahjongTeam = ($turnamen['jenis'] ?? null) === 'mahjong_team';
    $klasemenTitle = $isMahjongTeam ? 'Klasemen Tim' : 'Klasemen Mahjong';

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

  <header class="page-header mb-4 text-center text-md-start">
    <a href="{{ route('home') }}" class="btn btn-sm btn-outline-secondary mb-3">
      <i class="bi bi-house me-1"></i>Beranda
    </a>
    <h1><i class="bi bi-bar-chart-line me-2"></i>{{ $klasemenTitle }}</h1>
    <p>{{ $turnamen['nama'] ?? 'Turnamen Mahjong' }}</p>
    @include('public.partials.tournament-syarat', ['tournament' => $turnamen])
    <div class="mt-2 d-flex flex-wrap justify-content-center justify-content-md-start align-items-center gap-2">
      <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
      @if (! empty($turnamen['mahjong_is_final']))
        <span class="badge text-bg-warning text-dark">Final</span>
      @endif
    </div>
  </header>

  @include('public.partials.tournament-nav', [
    'tournament' => $turnamen,
    'idKategori' => $idKategori,
    'activeTab' => 'standings',
  ])

  @if (! empty($standingsError))
    <div class="alert alert-warning" role="alert">
      <i class="bi bi-exclamation-triangle me-1"></i>{{ $standingsError }}
    </div>
  @endif

  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h5 class="mb-0 standings-heading">
      <i class="bi bi-bar-chart-steps me-2"></i>{{ $klasemenTitle }}
      <small class="text-muted fw-normal">— {{ $turnamen['nama'] ?? 'Turnamen Mahjong' }}</small>
    </h5>
    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.location.reload()">
      <i class="bi bi-arrow-clockwise me-1"></i> Refresh
    </button>
  </div>

  @if (empty($sections))
    <div class="alert alert-light border text-center mb-0">
      <i class="bi bi-trophy text-muted d-block mb-2 fs-4"></i>
      Belum ada data klasemen.
    </div>
  @else
    <div class="alert alert-light border small mb-3">
      <strong>Cara peringkat:</strong> Total babak → Menang (W) → Akumulasi.
      Baris hijau menandai pemain yang lolos ke babak berikutnya.
    </div>

    @foreach ($sections as $section)
      @php
        $rounds = $section['rounds'] ?? [];
        $rows = $section['rows'] ?? [];
        $advanceKind = $section['advance_kind'] ?? 'none';
        $showGrup = collect($rows)->contains(function ($row) {
          return filled($row['grup_nama'] ?? null);
        });
        $colCount = 6 + count($rounds) + ($showGrup ? 1 : 0);
        $rankingNote = $section['ranking_note'] ?? ($standings['ranking_note'] ?? 'Peringkat berdasarkan Total babak, lalu Menang, lalu Akumulasi.');
      @endphp
      <section class="babak-section">
        <div class="d-flex align-items-center flex-wrap gap-2 mb-3">
          <h6 class="mb-0 fw-semibold">
            <i class="bi bi-layers me-1 text-primary"></i>Babak {{ $section['babak'] ?? '—' }}
          </h6>
          @if (! empty($section['is_active']))
            <span class="badge text-bg-success">Berlangsung</span>
          @endif
          @if (! empty($section['is_final']))
            <span class="badge text-bg-warning text-dark">Final</span>
          @elseif ($advanceKind === 'confirmed' && ! empty($section['next_babak']))
            <span class="badge text-bg-primary">Lolos ke Babak {{ $section['next_babak'] }}</span>
          @elseif ($advanceKind === 'preview' && ! empty($section['advance_count']))
            <span class="badge border text-secondary">Pratinjau {{ $section['advance_count'] }} lolos</span>
          @endif
        </div>

        <div class="card standings-table-card border-0 shadow-sm">
          <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
              <thead class="table-light">
                <tr>
                  <th class="text-center" style="width:3rem">#</th>
                  <th>Pemain</th>
                  @if ($showGrup)
                    <th>Grup</th>
                  @endif
                  @foreach ($rounds as $round)
                    <th class="text-center">{{ $round['label'] ?? ('Ronde ' . ($round['round'] ?? '')) }}</th>
                  @endforeach
                  <th class="text-center" title="Kriteria 1">Total Babak</th>
                  <th class="text-center" title="Kriteria 2: jumlah menang">W</th>
                  <th class="text-center" title="Kriteria 3">Akumulasi</th>
                  <th class="text-center" style="width:7rem">Status</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($rows as $row)
                  @php
                    $statusRow = $row['advance_status'] ?? null;
                    $rowClass = '';
                    if (in_array($statusRow, ['lolos', 'pratinjau', 'juara'], true)) {
                      $rowClass = 'table-success';
                    } elseif ($statusRow === 'seri') {
                      $rowClass = 'table-warning';
                    }
                    $cutlineStyle = ! empty($row['is_cutline'])
                      ? 'border-bottom: 2px solid var(--bs-success);'
                      : '';
                    $roundScores = $row['round_scores'] ?? [];
                  @endphp
                  <tr class="{{ $rowClass }}" @if ($cutlineStyle) style="{{ $cutlineStyle }}" @endif>
                    <td class="text-center fw-bold">
                      @if ((int) ($row['rank'] ?? 0) === 1)
                        <i class="bi bi-trophy-fill text-warning"></i>
                      @else
                        {{ $row['rank'] ?? '—' }}
                      @endif
                    </td>
                    <td class="fw-semibold">{{ $row['nama'] ?? '—' }}</td>
                    @if ($showGrup)
                      <td class="text-muted">{{ $row['grup_nama'] ?? '—' }}</td>
                    @endif
                    @foreach ($roundScores as $score)
                      <td class="text-center">
                        <span class="badge text-bg-secondary">{{ $score }}</span>
                      </td>
                    @endforeach
                    @for ($i = count($roundScores); $i < count($rounds); $i++)
                      <td class="text-center text-muted">—</td>
                    @endfor
                    <td class="text-center">
                      <span class="badge text-bg-primary">{{ $row['total_babak'] ?? 0 }}</span>
                    </td>
                    <td class="text-center">{{ $row['menang'] ?? 0 }}</td>
                    <td class="text-center text-muted">{{ $row['poin_akumulasi'] ?? 0 }}</td>
                    <td class="text-center">
                      @if ($statusRow === 'lolos')
                        <span class="badge text-bg-success">Lolos</span>
                      @elseif ($statusRow === 'pratinjau')
                        <span class="badge text-bg-success">Lolos*</span>
                      @elseif ($statusRow === 'seri')
                        <span class="badge text-bg-warning text-dark">Seri</span>
                      @elseif ($statusRow === 'juara')
                        <span class="badge text-bg-warning text-dark">Juara</span>
                      @elseif ($statusRow === 'runner_up')
                        <span class="badge text-bg-light text-dark border">Ke-2</span>
                      @elseif ($statusRow === 'third')
                        <span class="badge text-bg-light text-dark border">Ke-3</span>
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="{{ $colCount }}" class="text-center text-muted py-4">
                      Belum ada data pemain pada babak ini.
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
          <div class="px-3 py-2 border-top standings-note small text-muted">
            {{ $rankingNote }}
            @if (! empty($section['advance_note']))
              <div class="mt-1">
                @if ($advanceKind === 'preview' && collect($rows)->contains(function ($row) { return ($row['advance_status'] ?? null) === 'pratinjau'; }))
                  <span class="badge text-bg-success me-1">Lolos*</span> pratinjau berdasarkan total.
                @endif
                {{ $section['advance_note'] }}
              </div>
            @endif
          </div>
        </div>
      </section>
    @endforeach
  @endif
@endsection
