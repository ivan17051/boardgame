@extends('layouts.public')

@section('title', 'Turnamen Mahjong — Omahjong')

@push('styles')
<style>
  .page-header {
    text-align: center;
    margin-bottom: 1.75rem;
  }
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
  .filter-bar .btn.active {
    background: var(--brand);
    border-color: var(--brand);
    color: #fff;
  }
  .tournament-card {
    border: 1px solid rgba(0, 97, 49, 0.12);
    border-radius: 1rem;
    box-shadow: 0 8px 24px rgba(0, 60, 30, 0.06);
    height: 100%;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
  }
  .tournament-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 28px rgba(0, 60, 30, 0.1);
  }
  .tournament-card .card-body {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }
  .tournament-card .card-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
  }
  .meta-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
  }
  .price-tag {
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--brand);
    font-family: ui-monospace, monospace;
  }
</style>
@endpush

@section('content')
  <header class="page-header">
    <h1><i class="bi bi-trophy-fill me-2"></i>Turnamen Mahjong</h1>
    <p>Daftar turnamen mahjong terbaru</p>
  </header>

  @if (session('success'))
    <div class="alert alert-success text-center">{{ session('success') }}</div>
  @endif

  <div class="filter-bar d-flex flex-wrap justify-content-center gap-2 mb-4">
    @php
      $filters = [
        null => 'Semua',
        'open' => 'Pendaftaran dibuka',
        'ongoing' => 'Berlangsung',
        'completed' => 'Selesai',
      ];
    @endphp
    @foreach ($filters as $value => $label)
      <a
        href="{{ $value ? route('home', ['status' => $value]) : route('home') }}"
        class="btn btn-sm {{ ($statusFilter ?? null) === $value ? 'btn-primary active' : 'btn-outline-secondary' }}"
      >{{ $label }}</a>
    @endforeach
  </div>

  @if ($error)
    <div class="alert alert-danger text-center">{{ $error }}</div>
  @elseif (empty($tournaments))
    <div class="card tournament-card">
      <div class="card-body text-center text-secondary py-5">
        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
        Belum ada turnamen mahjong.
      </div>
    </div>
  @else
    <div class="row g-3">
      @foreach ($tournaments as $item)
        @php
          $status = $item['status'] ?? '';
          if ($status === 'open') {
            $statusClass = 'text-bg-success';
            $statusLabel = 'Pendaftaran dibuka';
          } elseif ($status === 'ongoing') {
            $statusClass = 'text-bg-primary';
            $statusLabel = 'Berlangsung';
          } elseif ($status === 'completed') {
            $statusClass = 'text-bg-secondary';
            $statusLabel = 'Selesai';
          } elseif ($status === 'draft') {
            $statusClass = 'text-bg-light text-dark';
            $statusLabel = 'Draft';
          } else {
            $statusClass = 'text-bg-light text-dark';
            $statusLabel = ucfirst($status);
          }
          $tanggal = ! empty($item['tanggal'])
            ? \Carbon\Carbon::parse($item['tanggal'])->locale('id')->translatedFormat('d F Y')
            : '—';
          $harga = isset($item['harga']) ? (float) $item['harga'] : 0;
        @endphp
        <div class="col-md-6 col-lg-4">
          <article class="card tournament-card">
            <div class="card-body">
              <div class="meta-row">
                <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                @if (! empty($item['registration_open']))
                  <span class="badge text-bg-info text-dark">Registrasi aktif</span>
                @endif
                @if (! empty($item['mahjong_is_final']))
                  <span class="badge text-bg-warning text-dark">Final</span>
                @endif
              </div>

              <h2 class="card-title">{{ $item['nama'] ?? 'Turnamen' }}</h2>

              <div class="small text-secondary">
                <i class="bi bi-calendar3 me-1"></i>{{ $tanggal }}
              </div>

              <div class="price-tag">
                @if ($harga > 0)
                  Rp {{ number_format($harga, 0, ',', '.') }}
                @else
                  Gratis
                @endif
              </div>

              <div class="mt-auto d-flex flex-column gap-2 w-100">
                  @if ($status === 'open')
                    <a
                      href="{{ route('public.mahjong-tournaments.register', $item['id']) }}"
                      class="btn btn-sm btn-primary w-100"
                    >
                      <i class="bi bi-person-plus me-1"></i>Daftar
                    </a>
                  @endif
                  @if (in_array($status, ['ongoing', 'completed'], true))
                    <a
                      href="{{ route('public.mahjong-tournaments.standings', $item['id']) }}"
                      class="btn btn-sm btn-outline-primary w-100"
                    >
                      <i class="bi bi-bar-chart-line me-1"></i>Klasemen
                    </a>
                  @endif
              </div>
            </div>
          </article>
        </div>
      @endforeach
    </div>
  @endif
@endsection
