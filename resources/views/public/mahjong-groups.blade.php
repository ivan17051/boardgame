@extends('layouts.public')

@section('title', 'Grup — ' . ($tournament['nama'] ?? 'Turnamen Mahjong') . ' — Omahjong')

@section('og')
  @include('public.partials.og-meta', [
    'ogTournament' => $tournament,
    'ogUrl' => route('public.mahjong-tournaments.groups', $tournament['id'] ?? 0),
    'ogTitle' => 'Grup ' . ($tournament['nama'] ?? 'Turnamen Mahjong') . ' — Omahjong',
    'ogDescription' => 'Lihat grup dan poin pemain turnamen mahjong ' . ($tournament['nama'] ?? '') . ' di Omahjong.',
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
  .group-card {
    border: 1px solid rgba(0, 97, 49, 0.12);
    border-radius: 0.85rem;
    box-shadow: 0 4px 16px rgba(0, 60, 30, 0.05);
    overflow: hidden;
    margin-bottom: 1rem;
  }
  .group-card-header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.65rem;
    padding: 0.85rem 1rem;
    background: rgba(0, 97, 49, 0.06);
    border-bottom: 1px solid rgba(0, 97, 49, 0.1);
  }
  .group-card-title {
    font-weight: 700;
    color: var(--brand-dark);
  }
  .group-card thead th {
    background: #f4f7f5;
    color: #495057;
    font-weight: 700;
    border-bottom: 1px solid rgba(0, 97, 49, 0.1);
    white-space: nowrap;
  }
  .group-card .table {
    margin-bottom: 0;
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
  .score-player-name.js-winner-pick {
    flex: 1;
    display: inline-flex;
    align-items: center;
    width: 100%;
    text-align: left;
    border: 1px solid rgba(0, 97, 49, 0.18);
    background: #fff;
    border-radius: 0.65rem;
    padding: 0.45rem 0.65rem;
    font-weight: 600;
    color: #1f2937;
    cursor: pointer;
  }
  .score-player-name.js-winner-pick:hover {
    border-color: var(--brand);
    background: #f4f9f6;
  }
  .score-player-name.is-winner {
    border-color: #e6a117;
    background: #fff8e8;
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
    $status = $tournament['status'] ?? '';
    if ($status === 'ongoing') {
      $statusClass = 'text-bg-primary';
      $statusLabel = 'Berlangsung';
    } elseif ($status === 'completed') {
      $statusClass = 'text-bg-secondary';
      $statusLabel = 'Selesai';
    } else {
      $statusClass = 'text-bg-light text-dark';
      $statusLabel = ucfirst((string) $status);
    }
  @endphp

  <header class="page-header text-center mb-4">
    <a href="{{ route('home') }}" class="btn btn-sm btn-outline-secondary mb-3">
      <i class="bi bi-house me-1"></i>Beranda
    </a>
    <h1><i class="bi bi-grid-3x3-gap me-2"></i>Grup Mahjong</h1>
    <p>{{ $tournament['nama'] ?? 'Turnamen Mahjong' }}</p>
    @include('public.partials.tournament-syarat', ['tournament' => $tournament])
    <div class="mt-2 d-flex flex-wrap justify-content-center align-items-center gap-2">
      <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
      @if (! empty($tournament['mahjong_is_final']))
        <span class="badge text-bg-warning text-dark">Final</span>
      @endif
    </div>
  </header>

  @include('public.partials.tournament-nav', [
    'tournament' => $tournament,
    'idKategori' => $idKategori ?? null,
    'activeTab' => 'groups',
  ])

  @if (! empty($groupsError))
    <div class="alert alert-warning" role="alert">
      <i class="bi bi-exclamation-triangle me-1"></i>{{ $groupsError }}
    </div>
  @endif

  @if (empty($groups))
    <div class="card group-card">
      <div class="card-body text-center text-secondary py-5">
        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
        Belum ada grup aktif pada turnamen ini.
      </div>
    </div>
  @else
    @foreach ($groups as $group)
      @include('public.partials.mahjong-group-table', [
        'group' => $group,
        'canInputScores' => $canInputScores ?? false,
      ])
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
          <div id="scoreModalContent"></div>
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
    const storeUrl = @json($scoreStoreUrl ?? '');
    const overlay = document.getElementById('scoreOverlay');
    const content = document.getElementById('scoreModalContent');
    const alertBox = document.getElementById('scoreModalAlert');
    const subtitle = document.getElementById('scoreModalSubtitle');
    const closeBtn = document.getElementById('scoreModalClose');

    if (!overlay || !content || !storeUrl) return;

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

    const renderFields = (grupName, babak, members) => {
      selectedWinnerMemberId = null;
      const playerRows = members.map((member) => `
        <div class="score-player-row">
          <button type="button"
                  class="score-player-name js-winner-pick"
                  data-member-id="${escapeHtml(member.id)}"
                  aria-pressed="false"
                  title="Tandai sebagai pemenang ronde">
            <i class="bi bi-trophy score-winner-icon me-1"></i>
            ${escapeHtml(member.nama || 'Pemain')}
          </button>
          <input type="number"
                 class="form-control js-score-poin"
                 data-member-id="${escapeHtml(member.id)}"
                 step="1"
                 placeholder="0" />
        </div>
      `).join('');

      content.innerHTML = `
        <form id="scoreHandForm" data-no-loading>
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

      subtitle.textContent = grupName
        ? `Klik nama pemain untuk menandai pemenang ronde di ${grupName}${babak ? ' — Babak ' + babak : ''}`
        : 'Klik nama pemain untuk menandai pemenang ronde, lalu isi poin';

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

      if (!selectedGroupId || scores.length !== 4) {
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
        const response = await fetch(storeUrl, {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: JSON.stringify({
            id_grup: Number(selectedGroupId),
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

    const openForButton = (btn) => {
      let members = [];
      try {
        members = JSON.parse(btn.getAttribute('data-members') || '[]');
      } catch (error) {
        members = [];
      }

      selectedGroupId = btn.dataset.grupId || null;
      showAlert('', 'success');
      renderFields(btn.dataset.grupName || 'Grup', btn.dataset.babak || '', members);
      openOverlay();
    };

    document.addEventListener('click', (event) => {
      const openBtn = event.target.closest('.js-input-poin');
      if (openBtn) {
        event.preventDefault();
        openForButton(openBtn);
        return;
      }

      const pickBtn = event.target.closest('.js-winner-pick');
      if (pickBtn && content.contains(pickBtn)) {
        event.preventDefault();
        setWinnerSelection(pickBtn.dataset.memberId);
      }
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
