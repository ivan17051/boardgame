@extends('layouts.public')

@section('title', 'Peserta — ' . ($tournament['nama'] ?? 'Turnamen Mahjong') . ' — Omahjong')

@section('og')
  @include('public.partials.og-meta', [
    'ogTournament' => $tournament,
    'ogUrl' => route('public.mahjong-tournaments.participants', $tournament['id'] ?? 0),
    'ogTitle' => 'Peserta ' . ($tournament['nama'] ?? 'Turnamen Mahjong') . ' — Omahjong',
    'ogDescription' => 'Daftar pemain terdaftar pada turnamen mahjong ' . ($tournament['nama'] ?? '') . ' di Omahjong.',
    'ogImage' => $tournament['share_image_url']
      ?? \App\Support\BornpadelMahjongTournaments::tournamentShareImageUrl($tournament['foto'] ?? null),
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
  .participants-card {
    border: 1px solid rgba(0, 97, 49, 0.12);
    border-radius: 0.85rem;
    box-shadow: 0 4px 16px rgba(0, 60, 30, 0.05);
    overflow: hidden;
  }
  .participants-card .card-header {
    background: rgba(0, 97, 49, 0.06);
    font-weight: 700;
    color: var(--brand-dark);
  }
  .participants-card table {
    margin-bottom: 0;
  }
  .participants-card thead th {
    background: #f4f7f5;
    color: #495057;
    font-weight: 700;
    border-bottom: 1px solid rgba(0, 97, 49, 0.1);
  }
  .status-badge-pending { background: #ffc107; color: #000; }
  .status-badge-unpaid { background: #fd7e14; color: #fff; }
  .status-badge-paid { background: #0dcaf0; color: #000; }
  .status-badge-approved { background: #198754; color: #fff; }
  .status-badge-rejected { background: #dc3545; color: #fff; }
  .kategori-filter {
    max-width: 36rem;
    margin: 0 auto 1.25rem;
  }
</style>
@endpush

@section('content')
  @php
    $status = $tournament['status'] ?? '';
    if ($status === 'open') {
      $statusClass = 'text-bg-success';
      $statusLabel = 'Pendaftaran dibuka';
    } elseif ($status === 'ongoing') {
      $statusClass = 'text-bg-primary';
      $statusLabel = 'Berlangsung';
    } elseif ($status === 'completed') {
      $statusClass = 'text-bg-secondary';
      $statusLabel = 'Selesai';
    } else {
      $statusClass = 'text-bg-light text-dark';
      $statusLabel = ucfirst((string) $status);
    }
    $selectedKategori = collect($tournament['kategori'] ?? [])->first(function ($kat) use ($idKategori) {
      return (string) ($kat['id'] ?? '') === (string) $idKategori;
    });
  @endphp

  <header class="page-header text-center mb-4">
    <a href="{{ route('home') }}" class="btn btn-sm btn-outline-secondary mb-3">
      <i class="bi bi-house me-1"></i>Beranda
    </a>
    <h1><i class="bi bi-people me-2"></i>Daftar Pemain</h1>
    <p>{{ $tournament['nama'] ?? 'Turnamen Mahjong' }}</p>
    @include('public.partials.tournament-syarat', ['tournament' => $tournament])
    <div class="mt-2 d-flex flex-wrap justify-content-center align-items-center gap-2">
      <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
      @if (! empty($selectedKategori['nama']) && ! empty($tournament['has_multiple_kategori']))
        <span class="badge text-bg-primary">{{ $selectedKategori['nama'] }}</span>
      @endif
    </div>
  </header>

  @include('public.partials.tournament-nav', [
    'tournament' => $tournament,
    'idKategori' => $idKategori ?? null,
    'activeTab' => 'participants',
  ])

  @if (! empty($tournament['has_multiple_kategori']))
    <form method="get" class="kategori-filter" data-no-loading>
      <label for="id_kategori" class="form-label fw-semibold">Kategori</label>
      <select name="id_kategori" id="id_kategori" class="form-select" onchange="this.form.submit()">
        @foreach (($tournament['kategori'] ?? []) as $kat)
          <option
            value="{{ $kat['id'] }}"
            {{ (string) ($idKategori ?? '') === (string) $kat['id'] ? 'selected' : '' }}
          >
            {{ $kat['nama'] }}
          </option>
        @endforeach
      </select>
      <div class="form-text">Daftar pemain ditampilkan per kategori kompetisi.</div>
    </form>
  @endif

  @if (! empty($participantsError))
    <div class="alert alert-warning" role="alert">
      <i class="bi bi-exclamation-triangle me-1"></i>{{ $participantsError }}
    </div>
  @endif

  <div class="card participants-card">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
      <span><i class="bi bi-people me-2"></i>Pemain Terdaftar</span>
      <span class="badge text-bg-secondary">{{ count($participants) }}</span>
    </div>
    <div class="card-body p-0">
      @if (empty($participants))
        <div class="text-center text-secondary py-5 px-3">
          <i class="bi bi-person-x fs-1 d-block mb-2"></i>
          Belum ada pemain terdaftar pada turnamen ini.
        </div>
      @else
        <div class="table-responsive">
          <table class="table table-hover mb-0 align-middle">
            <thead>
              <tr>
                <th style="width:3rem">#</th>
                <th>Nama</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($participants as $index => $item)
                <tr>
                  <td>{{ $index + 1 }}</td>
                  <td class="fw-semibold">{{ $item['nama'] ?? '—' }}</td>
                  <td>
                    <span class="badge status-badge-{{ $item['status'] ?? '' }}">
                      {{ \App\Support\BornpadelMahjongTournaments::registrationStatusLabel($item['status'] ?? null) }}
                    </span>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>
  </div>
@endsection
