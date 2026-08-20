@extends('layouts.public')

@section('title', 'Turnamen Mahjong — Omahjong')

@section('og')
  @php
    $featured = collect($tournaments ?? [])->first(function ($item) {
      return ($item['status'] ?? null) === 'open';
    });
    $featuredImage = is_array($featured)
      ? ($featured['share_image_url']
        ?? \App\Support\BornpadelMahjongTournaments::tournamentShareImageUrl($featured['foto'] ?? null))
      : \App\Support\BornpadelMahjongTournaments::defaultShareImageUrl();
  @endphp
  @include('public.partials.og-meta', [
    'ogTournament' => $featured,
    'ogUrl' => route('home'),
    'ogTitle' => 'Omahjong — Turnamen Mahjong',
    'ogDescription' => 'Daftar turnamen mahjong, lihat klasemen, dan pantau juara di Omahjong.',
    'ogImage' => $featuredImage,
  ])
@endsection

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
  .tournament-syarat {
    background: rgba(0, 97, 49, 0.04);
    border: 1px solid rgba(0, 97, 49, 0.1);
    border-radius: 0.65rem;
    padding: 0.75rem 0.9rem;
  }
  .tournament-syarat .syarat-label {
    color: #6c757d;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    margin-bottom: 0.35rem;
  }
  .tournament-syarat .syarat-text {
    color: #495057;
    font-size: 0.9rem;
    margin: 0;
    white-space: pre-line;
  }
  .tournament-card-actions {
    display: grid;
    gap: 0.5rem;
    grid-template-columns: 1fr;
  }
  .tournament-card-actions--2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .tournament-card-actions .btn {
    min-height: 2.25rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }
  .winner-row {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid rgba(0, 97, 49, 0.08);
  }
  .winner-row:last-child {
    border-bottom: 0;
  }
  .winner-rank {
    flex: 0 0 auto;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    background: #e9ecef;
    color: #495057;
  }
  .winner-rank.gold { background: #ffd700; color: #7a5c00; }
  .winner-rank.silver { background: #c0c0c0; color: #555; }
  .winner-rank.bronze { background: #cd7f32; color: #fff; }
  .winner-foto {
    flex: 0 0 auto;
    width: 3rem;
    height: 3rem;
    border-radius: 50%;
    object-fit: cover;
    background: #f0f4f1;
    border: 2px solid #fff;
    box-shadow: 0 2px 8px rgba(0, 60, 30, 0.12);
  }
  .winner-name {
    font-weight: 600;
    color: #1f2937;
  }
  .winner-points {
    margin-left: auto;
    font-weight: 700;
    color: var(--brand);
    font-family: ui-monospace, monospace;
  }
  .juara-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 30, 22, 0.55);
    backdrop-filter: blur(3px);
    display: none;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    z-index: 1080;
  }
  .juara-overlay.show {
    display: flex;
  }
  .juara-dialog {
    background: #fff;
    border-radius: 1rem;
    box-shadow: 0 20px 60px rgba(0, 40, 20, 0.25);
    width: 100%;
    max-width: 460px;
    overflow: hidden;
    animation: juaraPop 0.18s ease;
  }
  @keyframes juaraPop {
    from { transform: translateY(12px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
  }
  .juara-dialog-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    padding: 1rem 1.25rem;
    background: rgba(0, 97, 49, 0.06);
    border-bottom: 1px solid rgba(0, 97, 49, 0.1);
  }
  .juara-dialog-header h3 {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--brand-dark);
    margin: 0;
  }
  .juara-dialog-header p {
    margin: 0.15rem 0 0;
    font-size: 0.85rem;
    color: #6c757d;
  }
  .juara-close {
    border: 0;
    background: transparent;
    font-size: 1.25rem;
    line-height: 1;
    color: #6c757d;
    cursor: pointer;
  }
  .juara-body {
    padding: 0.5rem 1.25rem 1.25rem;
    max-height: 60vh;
    overflow-y: auto;
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
        <div class="col-12 col-md-6">
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

              @if ($status === 'open')
                <div class="price-tag">
                  @if ($harga > 0)
                    Rp {{ number_format($harga, 0, ',', '.') }}
                  @else
                    Gratis
                  @endif
                </div>
              @endif

              @if (! empty($item['syarat']))
                <div class="tournament-syarat">
                  <div class="syarat-label">
                    <i class="bi bi-card-text me-1"></i>Syarat
                  </div>
                  <p class="syarat-text">{{ $item['syarat'] }}</p>
                </div>
              @endif

              @php
                $actionCount = 0;
                if ($status === 'open') {
                  $actionCount = 2;
                } elseif ($status === 'completed') {
                  $actionCount = 2;
                } elseif ($status === 'ongoing') {
                  $actionCount = 1;
                }
              @endphp
              <div class="mt-auto tournament-card-actions {{ $actionCount === 2 ? 'tournament-card-actions--2' : '' }}">
                  @if ($status === 'open')
                    @php
                      $registerUrl = route('public.mahjong-tournaments.register', $item['id']);
                    @endphp
                    <a
                      href="{{ $registerUrl }}"
                      class="btn btn-sm btn-primary"
                    >
                      <i class="bi bi-person-plus me-1"></i>Daftar
                    </a>
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-secondary js-share-link"
                      data-share-url="{{ $registerUrl }}"
                      data-share-title="{{ $item['nama'] ?? 'Turnamen Mahjong' }}"
                      data-share-text="Daftar turnamen {{ $item['nama'] ?? 'Mahjong' }} di Omahjong"
                      data-no-loading
                    >
                      <i class="bi bi-share me-1"></i>Bagikan
                    </button>
                  @endif
                  @if (in_array($status, ['ongoing', 'completed'], true))
                    <a
                      href="{{ route('public.mahjong-tournaments.standings', $item['id']) }}"
                      class="btn btn-sm btn-outline-primary"
                    >
                      <i class="bi bi-bar-chart-line me-1"></i>Klasemen
                    </a>
                  @endif
                  @if ($status === 'completed')
                    <button
                      type="button"
                      class="btn btn-sm btn-warning js-juara-btn"
                      data-url="{{ route('public.mahjong-tournaments.winners', $item['id']) }}"
                      data-nama="{{ $item['nama'] ?? 'Turnamen' }}"
                    >
                      <i class="bi bi-trophy-fill me-1"></i>Juara
                    </button>
                  @endif
              </div>
            </div>
          </article>
        </div>
      @endforeach
    </div>
  @endif

  <div class="juara-overlay" id="juaraOverlay" aria-hidden="true">
    <div class="juara-dialog" role="dialog" aria-modal="true" aria-labelledby="juaraTitle">
      <div class="juara-dialog-header">
        <div>
          <h3 id="juaraTitle"><i class="bi bi-trophy-fill text-warning me-1"></i>Juara Turnamen</h3>
          <p id="juaraSubtitle"></p>
        </div>
        <button type="button" class="juara-close" id="juaraClose" aria-label="Tutup">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>
      <div class="juara-body" id="juaraBody"></div>
    </div>
  </div>
@endsection

@push('scripts')
<script>
  (function () {
    async function copyShareUrl(url) {
      if (navigator.clipboard && window.isSecureContext) {
        await navigator.clipboard.writeText(url);
        return;
      }

      const input = document.createElement('input');
      input.value = url;
      input.setAttribute('readonly', '');
      input.style.position = 'absolute';
      input.style.left = '-9999px';
      document.body.appendChild(input);
      input.select();
      document.execCommand('copy');
      document.body.removeChild(input);
    }

    document.querySelectorAll('.js-share-link').forEach((button) => {
      button.addEventListener('click', async () => {
        const url = button.dataset.shareUrl;
        const title = button.dataset.shareTitle || 'Omahjong';
        const text = button.dataset.shareText || title;
        const originalHtml = button.innerHTML;

        try {
          if (navigator.share) {
            await navigator.share({ title, text, url });
            return;
          }

          await copyShareUrl(url);
          button.innerHTML = '<i class="bi bi-check2 me-1"></i> Link Disalin';
          window.setTimeout(() => { button.innerHTML = originalHtml; }, 2000);
        } catch (error) {
          if (error && error.name === 'AbortError') {
            return;
          }

          try {
            await copyShareUrl(url);
            button.innerHTML = '<i class="bi bi-check2 me-1"></i> Link Disalin';
            window.setTimeout(() => { button.innerHTML = originalHtml; }, 2000);
          } catch (_) {
            window.prompt('Salin link:', url);
          }
        }
      });
    });

    const overlay = document.getElementById('juaraOverlay');
    const body = document.getElementById('juaraBody');
    const subtitle = document.getElementById('juaraSubtitle');
    const closeBtn = document.getElementById('juaraClose');

    if (!overlay) {
      return;
    }

    const rankClass = (peringkat) => {
      if (peringkat === 1) return 'gold';
      if (peringkat === 2) return 'silver';
      if (peringkat === 3) return 'bronze';
      return '';
    };

    const formatPoints = (value) =>
      new Intl.NumberFormat('id-ID').format(Number(value) || 0);

    const escapeHtml = (value) =>
      String(value ?? '').replace(/[&<>"']/g, (ch) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
      }[ch]));

    const openOverlay = () => {
      overlay.classList.add('show');
      overlay.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
    };

    const closeOverlay = () => {
      overlay.classList.remove('show');
      overlay.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    };

    const renderState = (html) => {
      body.innerHTML = html;
    };

    const PLACEHOLDER_FALLBACK =
      "data:image/svg+xml;utf8," + encodeURIComponent(
        '<svg xmlns="http://www.w3.org/2000/svg" width="96" height="96" viewBox="0 0 96 96">' +
        '<rect width="96" height="96" fill="#e8f0eb"/>' +
        '<circle cx="48" cy="38" r="18" fill="#b9ccc0"/>' +
        '<path d="M16 84c0-17 14-28 32-28s32 11 32 28z" fill="#b9ccc0"/>' +
        '</svg>'
      );

    const renderWinners = (winners, placeholder) => {
      if (!Array.isArray(winners) || winners.length === 0) {
        renderState('<p class="text-center text-secondary py-4 mb-0">Data juara belum tersedia.</p>');
        return;
      }

      const fallback = placeholder || PLACEHOLDER_FALLBACK;

      const rows = winners.map((w) => {
        const fotoSrc = w.foto_url ? escapeHtml(w.foto_url) : fallback;
        return `
        <div class="winner-row">
          <span class="winner-rank ${rankClass(w.peringkat)}">${w.peringkat === 1 ? '<i class="bi bi-trophy-fill"></i>' : escapeHtml(w.peringkat)}</span>
          <img class="winner-foto" src="${fotoSrc}" alt="Foto ${escapeHtml(w.nama || 'pemain')}" onerror="this.onerror=null;this.src='${PLACEHOLDER_FALLBACK}';" />
          <div>
            <div class="winner-name">${escapeHtml(w.nama || '—')}</div>
            <div class="small text-secondary">${escapeHtml(w.label || ('Juara ' + w.peringkat))}</div>
          </div>
          <span class="winner-points">${formatPoints(w.total_poin)} poin</span>
        </div>`;
      }).join('');

      renderState(rows);
    };

    document.querySelectorAll('.js-juara-btn').forEach((btn) => {
      btn.addEventListener('click', async () => {
        subtitle.textContent = btn.dataset.nama || '';
        renderState('<p class="text-center text-secondary py-4 mb-0"><span class="spinner-border spinner-border-sm me-2"></span>Memuat data juara...</p>');
        openOverlay();

        try {
          const response = await fetch(btn.dataset.url, {
            headers: { 'Accept': 'application/json' },
          });
          const payload = await response.json();

          if (!response.ok || !payload.success) {
            renderState(`<p class="text-center text-danger py-4 mb-0">${escapeHtml(payload.message || 'Gagal memuat data juara.')}</p>`);
            return;
          }

          renderWinners(
            payload.data && payload.data.winners,
            payload.data && payload.data.placeholder_url
          );
        } catch (e) {
          renderState('<p class="text-center text-danger py-4 mb-0">Tidak dapat terhubung ke server.</p>');
        }
      });
    });

    closeBtn.addEventListener('click', closeOverlay);
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) closeOverlay();
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && overlay.classList.contains('show')) closeOverlay();
    });
  })();
</script>
@endpush
