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
  .score-overlay {
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
  .score-overlay.show {
    display: flex;
  }
  .score-dialog {
    background: #fff;
    border-radius: 1rem;
    box-shadow: 0 20px 60px rgba(0, 40, 20, 0.25);
    width: 100%;
    max-width: 420px;
    overflow: hidden;
    animation: scorePop 0.18s ease;
  }
  @keyframes scorePop {
    from { transform: translateY(12px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
  }
  .score-dialog-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.5rem;
    padding: 1rem 1.25rem;
    background: rgba(0, 97, 49, 0.06);
    border-bottom: 1px solid rgba(0, 97, 49, 0.1);
  }
  .score-dialog-header h3 {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--brand-dark);
    margin: 0;
  }
  .score-dialog-header p {
    margin: 0.15rem 0 0;
    font-size: 0.85rem;
    color: #6c757d;
  }
  .score-close {
    border: 0;
    background: transparent;
    font-size: 1.25rem;
    line-height: 1;
    color: #6c757d;
    cursor: pointer;
  }
  .score-dialog-body {
    padding: 1.15rem 1.25rem 1.25rem;
    max-height: 70vh;
    overflow-y: auto;
  }
  .score-player-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.45rem 0;
  }
  .score-player-row + .score-player-row {
    border-top: 1px solid rgba(0, 97, 49, 0.08);
  }
  .score-player-name {
    flex: 1;
    font-weight: 600;
    color: #1f2937;
  }
  .score-player-name.js-winner-pick {
    display: inline-flex;
    align-items: center;
    width: 100%;
    text-align: left;
    border: 1px solid rgba(0, 97, 49, 0.18);
    background: #fff;
    border-radius: 0.65rem;
    padding: 0.45rem 0.65rem;
    cursor: pointer;
  }
  .score-player-name.js-winner-pick:hover {
    border-color: var(--brand);
    background: #f4f9f6;
  }
  .score-player-name.is-winner {
    border-color: #f5b544;
    background: #fff8e8;
    color: #5c3d00;
  }
  .score-player-name.is-winner .score-winner-icon {
    color: #e6a117;
  }
  .win-pill {
    display: inline-block;
    min-width: 2.25rem;
    padding: 0.25rem 0.6rem;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 700;
    background: #fff3cd;
    color: #7a5c00;
  }
  .score-player-row input {
    width: 6.5rem;
    text-align: center;
    font-weight: 700;
    font-family: ui-monospace, monospace;
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
    <div class="mt-2 d-flex flex-wrap align-items-center gap-2">
      <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
      @if (! empty($turnamen['mahjong_is_final']))
        <span class="badge text-bg-warning text-dark">Final</span>
      @endif
      @if (! empty($canInputScores))
        <button type="button" class="btn btn-sm btn-primary" id="btnInputPoin">
          <i class="bi bi-pencil-square me-1"></i>Input Poin
        </button>
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
                  <th class="text-center" title="Jumlah menang (ronde)">Menang</th>
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
                      <span class="win-pill">{{ (int) ($row['menang'] ?? 0) }}</span>
                    </td>
                    <td class="text-center">
                      <span class="total-pill">{{ (int) ($row['total_babak'] ?? 0) }}</span>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="{{ 4 + $roundCount }}" class="text-center text-secondary py-4">
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

  @if (! empty($canInputScores))
    <div class="score-overlay" id="scoreOverlay" aria-hidden="true">
      <div class="score-dialog" role="dialog" aria-modal="true" aria-labelledby="scoreModalTitle">
        <div class="score-dialog-header">
          <div>
            <h3 id="scoreModalTitle"><i class="bi bi-pencil-square me-1"></i>Input Poin</h3>
            <p id="scoreModalSubtitle">Klik nama pemain untuk menandai pemenang ronde, lalu isi poin</p>
          </div>
          <button type="button" class="score-close" id="scoreModalClose" aria-label="Tutup">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
        <div class="score-dialog-body">
          <div id="scoreModalAlert"></div>
          <div id="scoreModalContent">
            <p class="text-center text-secondary py-4 mb-0">
              <span class="spinner-border spinner-border-sm me-2"></span>Memuat grup...
            </p>
          </div>
        </div>
      </div>
    </div>
  @endif
@endsection

@if (! empty($canInputScores))
@push('scripts')
<script>
  (function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const routes = @json($scoreRoutes ?? []);
    const openBtn = document.getElementById('btnInputPoin');
    const overlay = document.getElementById('scoreOverlay');
    const content = document.getElementById('scoreModalContent');
    const alertBox = document.getElementById('scoreModalAlert');
    const subtitle = document.getElementById('scoreModalSubtitle');
    const closeBtn = document.getElementById('scoreModalClose');

    if (!openBtn || !overlay) return;

    let groups = [];
    let selectedGroupId = null;
    let selectedWinnerMemberId = null;

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

    const showAlert = (message, type) => {
      if (!alertBox) return;
      if (!message) {
        alertBox.innerHTML = '';
        return;
      }
      alertBox.innerHTML = `<div class="alert alert-${type === 'success' ? 'success' : 'danger'} py-2">${escapeHtml(message)}</div>`;
    };

    const selectedGroup = () =>
      groups.find((grup) => Number(grup.id) === Number(selectedGroupId)) || groups[0] || null;

    const setWinnerSelection = (memberId) => {
      selectedWinnerMemberId = memberId ? String(memberId) : null;
      content.querySelectorAll('.js-winner-pick').forEach((btn) => {
        const isWinner = selectedWinnerMemberId !== null
          && String(btn.dataset.memberId) === selectedWinnerMemberId;
        btn.classList.toggle('is-winner', isWinner);
        btn.setAttribute('aria-pressed', isWinner ? 'true' : 'false');
        const icon = btn.querySelector('.score-winner-icon');
        if (icon) {
          icon.className = isWinner
            ? 'bi bi-trophy-fill score-winner-icon me-1'
            : 'bi bi-trophy score-winner-icon me-1';
        }
      });
    };

    const renderFields = () => {
      const grup = selectedGroup();
      selectedWinnerMemberId = null;
      if (!grup) {
        content.innerHTML = '<p class="text-center text-secondary py-4 mb-0">Belum ada grup aktif.</p>';
        return;
      }

      const members = Array.isArray(grup.members) ? grup.members : [];
      const groupOptions = groups.map((item) => `
        <option value="${escapeHtml(item.id)}" ${Number(item.id) === Number(grup.id) ? 'selected' : ''}>
          ${escapeHtml(item.nama || ('Grup ' + item.id))}${item.babak ? ' — Babak ' + escapeHtml(item.babak) : ''}
        </option>
      `).join('');

      const playerRows = members.map((member) => `
        <div class="score-player-row">
          <button type="button"
                  class="score-player-name js-winner-pick"
                  data-member-id="${escapeHtml(member.id_grup_member)}"
                  aria-pressed="false"
                  title="Tandai sebagai pemenang ronde">
            <i class="bi bi-trophy score-winner-icon me-1"></i>
            ${escapeHtml(member.nama || 'Pemain')}
          </button>
          <input type="number"
                 class="form-control js-score-poin"
                 data-member-id="${escapeHtml(member.id_grup_member)}"
                 step="1"
                 placeholder="0" />
        </div>
      `).join('');

      content.innerHTML = `
        <form id="scoreHandForm" data-no-loading>
          ${groups.length > 1 ? `
            <label class="form-label" for="scoreGroupSelect">Grup</label>
            <select class="form-select mb-3" id="scoreGroupSelect">${groupOptions}</select>
          ` : `
            <p class="small text-secondary mb-3">${escapeHtml(grup.nama || 'Grup')}${grup.babak ? ' — Babak ' + escapeHtml(grup.babak) : ''}</p>
          `}
          ${members.length === 4 ? playerRows : '<p class="text-secondary mb-0">Grup ini belum memiliki 4 pemain.</p>'}
          ${members.length === 4 ? `
            <div class="d-grid mt-3">
              <button type="submit" class="btn btn-primary" id="scoreSaveBtn">
                <i class="bi bi-save me-1"></i>Simpan poin
              </button>
            </div>
          ` : ''}
        </form>
      `;

      subtitle.textContent = grup.nama
        ? `Klik nama pemain untuk menandai pemenang ronde di ${grup.nama}`
        : 'Klik nama pemain untuk menandai pemenang ronde, lalu isi poin';

      const groupSelect = document.getElementById('scoreGroupSelect');
      if (groupSelect) {
        groupSelect.addEventListener('change', () => {
          selectedGroupId = groupSelect.value;
          showAlert('', 'success');
          renderFields();
        });
      }

      const form = document.getElementById('scoreHandForm');
      if (form) {
        form.addEventListener('submit', submitScores);
      }

      const firstInput = content.querySelector('.js-score-poin');
      if (firstInput) {
        setTimeout(() => firstInput.focus(), 80);
      }
    };

    const submitScores = async (event) => {
      event.preventDefault();
      const grup = selectedGroup();
      const inputs = Array.from(content.querySelectorAll('.js-score-poin'));
      const scores = [];

      if (!selectedWinnerMemberId) {
        showAlert('Pilih pemenang ronde dengan mengklik nama pemain.', 'error');
        return;
      }

      for (const input of inputs) {
        if (input.value === '' || input.value === null) {
          showAlert('Isi poin untuk semua pemain.', 'error');
          input.focus();
          return;
        }
        const poin = parseInt(input.value, 10);
        if (Number.isNaN(poin)) {
          showAlert('Poin harus berupa angka.', 'error');
          input.focus();
          return;
        }
        scores.push({
          id_grup_member: parseInt(input.dataset.memberId, 10),
          poin,
        });
      }

      if (!grup || scores.length !== 4) {
        showAlert('Poin harus diisi untuk keempat pemain.', 'error');
        return;
      }

      const saveBtn = document.getElementById('scoreSaveBtn');
      const original = saveBtn ? saveBtn.innerHTML : '';
      if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';
      }

      try {
        const response = await fetch(routes.store, {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: JSON.stringify({
            id_grup: Number(grup.id),
            id_grup_member_pemenang: Number(selectedWinnerMemberId),
            scores,
          }),
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok || payload.success === false) {
          const message = payload.message
            || (payload.errors && Object.values(payload.errors).flat().join(' '))
            || 'Gagal menyimpan poin.';
          throw new Error(message);
        }
        showAlert(payload.message || 'Poin berhasil disimpan.', 'success');
        window.setTimeout(() => window.location.reload(), 600);
      } catch (error) {
        showAlert(error.message, 'error');
        if (saveBtn) {
          saveBtn.disabled = false;
          saveBtn.innerHTML = original;
        }
      }
    };

    const loadGroups = async () => {
      showAlert('', 'success');
      content.innerHTML = '<p class="text-center text-secondary py-4 mb-0"><span class="spinner-border spinner-border-sm me-2"></span>Memuat grup...</p>';
      try {
        const response = await fetch(routes.groups, {
          headers: { 'Accept': 'application/json' },
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok || payload.success === false) {
          throw new Error(payload.message || 'Gagal memuat grup.');
        }
        groups = Array.isArray(payload.data && payload.data.groups) ? payload.data.groups : [];
        selectedGroupId = groups[0] ? groups[0].id : null;
        renderFields();
      } catch (error) {
        content.innerHTML = `<p class="text-center text-danger py-4 mb-0">${escapeHtml(error.message)}</p>`;
      }
    };

    content.addEventListener('click', (event) => {
      const pickBtn = event.target.closest('.js-winner-pick');
      if (!pickBtn || !content.contains(pickBtn)) {
        return;
      }
      event.preventDefault();
      setWinnerSelection(pickBtn.dataset.memberId);
    });

    openBtn.addEventListener('click', () => {
      openOverlay();
      loadGroups();
    });
    closeBtn.addEventListener('click', closeOverlay);
    overlay.addEventListener('click', (event) => {
      if (event.target === overlay) closeOverlay();
    });
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && overlay.classList.contains('show')) closeOverlay();
    });
  })();
</script>
@endpush
@endif

