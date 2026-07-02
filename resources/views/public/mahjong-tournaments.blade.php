<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
  <title>Turnamen Mahjong — Omahjong</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous" />
  <link rel="stylesheet" href="{{ asset('public/css/adminlte.css') }}" />
  <style>
    :root {
      --brand: #006131;
      --brand-dark: #004d26;
    }
    body {
      min-height: 100vh;
      background: linear-gradient(165deg, #f0f7f3 0%, #e8f0eb 45%, #f8faf9 100%);
    }
    .page-shell {
      max-width: 1100px;
      margin: 0 auto;
      padding: 1.5rem 1rem 2.5rem;
    }
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
    .syarat-text {
      color: #6c757d;
      font-size: 0.9rem;
      margin: 0;
      white-space: pre-line;
    }
  </style>
</head>
<body>
  <div class="page-shell">
    <header class="page-header">
      <h1><i class="bi bi-trophy-fill me-2"></i>Turnamen Mahjong</h1>
      <p>Daftar turnamen mahjong terbaru</p>
    </header>

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
          href="{{ $value ? route('public.mahjong-tournaments', ['status' => $value]) : route('public.mahjong-tournaments') }}"
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

                @if (! empty($item['syarat']))
                  <p class="syarat-text">{{ $item['syarat'] }}</p>
                @endif

                <div class="mt-auto small text-secondary">
                  <i class="bi bi-grid-3x3-gap me-1"></i>{{ $item['jenis_label'] ?? 'Mahjong' }}
                </div>
              </div>
            </article>
          </div>
        @endforeach
      </div>
    @endif
  </div>
</body>
</html>
