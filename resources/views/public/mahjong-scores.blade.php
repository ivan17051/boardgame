@extends('layouts.public')

@section('title', 'Input Poin — ' . ($tournament['nama'] ?? 'Turnamen Mahjong') . ' — Omahjong')

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
    border-radius: 1rem;
    box-shadow: 0 8px 24px rgba(0, 60, 30, 0.06);
    overflow: hidden;
    margin-bottom: 1.25rem;
    background: #fff;
  }
  .group-card-header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    padding: 0.9rem 1.1rem;
    background: rgba(0, 97, 49, 0.06);
    border-bottom: 1px solid rgba(0, 97, 49, 0.1);
  }
  .group-card-header h2 {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--brand-dark);
    margin: 0;
  }
  .group-card .table {
    margin-bottom: 0;
  }
  .group-card thead th {
    background: #f4f7f5;
    font-weight: 700;
    white-space: nowrap;
  }
  .table > :not(caption) > * > * {
    vertical-align: middle;
  }
  .entry-chip {
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.15rem;
    border: 1px solid rgba(0, 97, 49, 0.18);
    background: #fff;
    color: #1f2937;
    border-radius: 999px;
    padding: 0.15rem 0.55rem;
    font-size: 0.8rem;
    font-weight: 700;
    font-family: ui-monospace, monospace;
  }
  .entry-chip:hover {
    background: #eef6f1;
    color: var(--brand-dark);
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
  .akum-pill {
    display: inline-block;
    min-width: 2.25rem;
    padding: 0.25rem 0.6rem;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 700;
    background: #e9ecef;
    color: #495057;
  }
  .hand-form {
    padding: 1rem 1.1rem 1.15rem;
    border-top: 1px solid rgba(0, 97, 49, 0.1);
    background: #fbfdfc;
  }
  .hand-form h3 {
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--brand-dark);
    margin: 0 0 0.75rem;
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
    max-width: 380px;
    overflow: hidden;
  }
  .score-dialog-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.5rem;
    padding: 1rem 1.15rem;
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
    margin: 0.2rem 0 0;
    font-size: 0.85rem;
    color: #6c757d;
  }
  .score-close {
    cursor: pointer;
    border: 0;
    background: transparent;
    font-size: 1.25rem;
    line-height: 1;
    color: #6c757d;
  }
  .score-dialog-body {
    padding: 1.15rem;
  }
  .flash-alert {
    position: sticky;
    top: 0.75rem;
    z-index: 20;
  }
</style>
@endpush

@section('content')
  <header class="page-header mb-4">
    <a href="{{ route('home') }}" class="btn btn-sm btn-outline-secondary mb-3">
      <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
    <h1><i class="bi bi-pencil-square me-2"></i>Input Poin</h1>
    <p>{{ $tournament['nama'] ?? 'Turnamen Mahjong' }}</p>
    <div class="mt-2">
      <span class="badge text-bg-primary">Berlangsung</span>
      @if (! empty($tournament['mahjong_is_final']))
        <span class="badge text-bg-warning text-dark">Final</span>
      @endif
    </div>
  </header>

  <div id="scoreFlash" class="flash-alert"></div>

  @if (! empty($groupsError))
    <div class="alert alert-danger">
      <i class="bi bi-exclamation-triangle me-1"></i>{{ $groupsError }}
    </div>
  @endif

  <div id="groupsRoot"></div>
@endsection

@push('scripts')
<script>
  (function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const routes = @json($scoreRoutes);
    let groups = @json($groups);

    const root = document.getElementById('groupsRoot');
    const flashEl = document.getElementById('scoreFlash');

    const overlayHtml = `
      <div class="score-overlay" id="scoreOverlay" aria-hidden="true">
        <div class="score-dialog" role="dialog" aria-modal="true">
          <div class="score-dialog-header">
            <div>
              <h3 id="scoreModalTitle">Poin pemain</h3>
              <p id="scoreModalSubtitle"></p>
            </div>
            <button type="button" class="score-close" id="scoreModalClose" aria-label="Tutup">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>
          <div class="score-dialog-body">
            <label class="form-label" for="scoreModalPoin">Poin</label>
            <input type="number" class="form-control text-center fs-4" id="scoreModalPoin" step="1" />
            <div class="d-grid mt-3">
              <button type="button" class="btn btn-primary" id="scoreModalSave">Simpan</button>
            </div>
          </div>
        </div>
      </div>
    `;
    document.body.insertAdjacentHTML('beforeend', overlayHtml);

    const overlay = document.getElementById('scoreOverlay');
    const modalTitle = document.getElementById('scoreModalTitle');
    const modalSubtitle = document.getElementById('scoreModalSubtitle');
    const modalPoin = document.getElementById('scoreModalPoin');
    const modalSave = document.getElementById('scoreModalSave');
    const modalClose = document.getElementById('scoreModalClose');

    let modalMode = null;
    let modalMemberId = null;
    let modalEntryId = null;

    const escapeHtml = (value) =>
      String(value ?? '').replace(/[&<>"']/g, (ch) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
      }[ch]));

    const formatPoin = (value) => {
      const n = Number(value) || 0;
      return (n > 0 ? '+' : '') + n;
    };

    const showFlash = (message, type) => {
      if (!flashEl) return;
      flashEl.innerHTML = `
        <div class="alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible">
          ${escapeHtml(message)}
          <button type="button" class="btn-close" aria-label="Tutup"></button>
        </div>`;
      flashEl.querySelector('.btn-close')?.addEventListener('click', () => {
        flashEl.innerHTML = '';
      });
      window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const jsonHeaders = {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrf,
      'X-Requested-With': 'XMLHttpRequest',
    };

    const requestJson = async (url, method, body) => {
      const response = await fetch(url, {
        method,
        headers: jsonHeaders,
        body: body ? JSON.stringify(body) : undefined,
      });
      const payload = await response.json().catch(() => ({}));
      if (!response.ok || payload.success === false) {
        const message = payload.message
          || (payload.errors && Object.values(payload.errors).flat().join(' '))
          || 'Gagal menyimpan poin.';
        throw new Error(message);
      }
      return payload;
    };

    const replaceGroup = (grup) => {
      if (!grup || !grup.id) return;
      const index = groups.findIndex((item) => Number(item.id) === Number(grup.id));
      if (index === -1) {
        groups.push(grup);
        return;
      }
      groups[index] = grup;
    };

    const replaceMember = (member) => {
      if (!member || !member.id_grup_member) return;
      groups = groups.map((grup) => {
        const members = Array.isArray(grup.members) ? grup.members : [];
        const nextMembers = members.map((item) =>
          Number(item.id_grup_member) === Number(member.id_grup_member) ? member : item
        );
        return { ...grup, members: nextMembers };
      });
    };

    const renderGroups = () => {
      if (!Array.isArray(groups) || groups.length === 0) {
        root.innerHTML = `
          <div class="card group-card">
            <div class="card-body text-center text-secondary py-5">
              <i class="bi bi-inbox fs-1 d-block mb-2"></i>
              Belum ada grup aktif untuk turnamen ini.
            </div>
          </div>`;
        return;
      }

      root.innerHTML = groups.map((grup) => {
        const members = Array.isArray(grup.members) ? grup.members : [];
        const canStoreHand = members.length === 4;
        const rows = members.map((member) => {
          const entries = Array.isArray(member.entries) ? member.entries : [];
          const chips = entries.length
            ? entries.map((entry) => `
                <button type="button"
                        class="entry-chip js-edit-entry"
                        data-member-id="${escapeHtml(member.id_grup_member)}"
                        data-member-name="${escapeHtml(member.nama || 'Pemain')}"
                        data-entry-id="${escapeHtml(entry.id)}"
                        data-poin="${escapeHtml(entry.poin)}"
                        title="Ubah entri poin">
                  ${escapeHtml(formatPoin(entry.poin))}
                </button>`).join('')
            : '<span class="text-muted">—</span>';

          return `
            <tr>
              <td class="fw-semibold">${escapeHtml(member.nama || '—')}</td>
              <td class="text-center"><span class="akum-pill">${escapeHtml(member.poin_akumulasi ?? 0)}</span></td>
              <td>
                <div class="d-flex flex-wrap gap-1">${chips}</div>
              </td>
              <td class="text-center"><span class="total-pill">${escapeHtml(member.total_poin ?? 0)}</span></td>
              <td class="text-end">
                <button type="button"
                        class="btn btn-sm btn-outline-primary js-add-member"
                        data-member-id="${escapeHtml(member.id_grup_member)}"
                        data-member-name="${escapeHtml(member.nama || 'Pemain')}">
                  <i class="bi bi-plus-lg me-1"></i>Poin
                </button>
              </td>
            </tr>`;
        }).join('');

        const handFields = canStoreHand
          ? members.map((member) => `
              <div class="col-6 col-md-3">
                <label class="form-label small mb-1">${escapeHtml(member.nama || 'Pemain')}</label>
                <input type="number"
                       class="form-control text-center js-hand-poin"
                       data-member-id="${escapeHtml(member.id_grup_member)}"
                       step="1"
                       placeholder="0" />
              </div>`).join('')
          : '';

        return `
          <article class="group-card" data-group-id="${escapeHtml(grup.id)}">
            <div class="group-card-header">
              <h2>
                <i class="bi bi-diagram-3 me-1"></i>${escapeHtml(grup.nama || 'Grup')}
                ${grup.babak ? `<small class="text-muted fw-normal">— Babak ${escapeHtml(grup.babak)}</small>` : ''}
              </h2>
              <span class="badge text-bg-info">${members.length} pemain</span>
            </div>
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Pemain</th>
                    <th class="text-center">Akumulasi</th>
                    <th>Entri poin</th>
                    <th class="text-center">Total</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  ${rows || '<tr><td colspan="5" class="text-center text-secondary py-4">Belum ada pemain di grup ini.</td></tr>'}
                </tbody>
              </table>
            </div>
            ${canStoreHand ? `
              <form class="hand-form js-hand-form" data-group-id="${escapeHtml(grup.id)}" data-no-loading>
                <h3><i class="bi bi-collection me-1"></i>Simpan 1 ronde (4 pemain)</h3>
                <div class="row g-2">${handFields}</div>
                <div class="d-grid d-md-flex justify-content-md-end mt-3">
                  <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>Simpan ronde
                  </button>
                </div>
              </form>` : `
              <div class="hand-form text-secondary small">
                Input ronde 4 pemain tersedia setelah grup terisi lengkap.
              </div>`}
          </article>`;
      }).join('');
    };

    const openOverlay = () => {
      overlay.classList.add('show');
      overlay.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
      setTimeout(() => modalPoin.focus(), 80);
    };

    const closeOverlay = () => {
      overlay.classList.remove('show');
      overlay.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
      modalMode = null;
      modalMemberId = null;
      modalEntryId = null;
    };

    const setButtonLoading = (btn, loading) => {
      if (!btn) return;
      if (loading) {
        btn.dataset.originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';
        return;
      }
      btn.disabled = false;
      if (btn.dataset.originalHtml) {
        btn.innerHTML = btn.dataset.originalHtml;
      }
    };

    root.addEventListener('submit', async (event) => {
      const form = event.target.closest('.js-hand-form');
      if (!form) return;
      event.preventDefault();

      const inputs = Array.from(form.querySelectorAll('.js-hand-poin'));
      const scores = [];
      for (const input of inputs) {
        if (input.value === '' || input.value === null) {
          showFlash('Isi poin untuk keempat pemain.', 'error');
          input.focus();
          return;
        }
        const poin = parseInt(input.value, 10);
        if (Number.isNaN(poin)) {
          showFlash('Poin harus berupa angka.', 'error');
          input.focus();
          return;
        }
        scores.push({
          id_grup_member: parseInt(input.dataset.memberId, 10),
          poin,
        });
      }

      const btn = form.querySelector('button[type="submit"]');
      setButtonLoading(btn, true);
      try {
        const payload = await requestJson(routes.store, 'POST', {
          id_grup: parseInt(form.dataset.groupId, 10),
          scores,
        });
        if (payload.data && payload.data.grup) {
          replaceGroup(payload.data.grup);
        }
        renderGroups();
        showFlash(payload.message || 'Poin grup berhasil disimpan.', 'success');
      } catch (error) {
        showFlash(error.message, 'error');
      } finally {
        setButtonLoading(btn, false);
      }
    });

    root.addEventListener('click', (event) => {
      const addBtn = event.target.closest('.js-add-member');
      if (addBtn) {
        modalMode = 'store';
        modalMemberId = addBtn.dataset.memberId;
        modalEntryId = null;
        modalTitle.textContent = 'Tambah poin pemain';
        modalSubtitle.textContent = addBtn.dataset.memberName || '';
        modalPoin.value = '';
        openOverlay();
        return;
      }

      const editBtn = event.target.closest('.js-edit-entry');
      if (editBtn) {
        modalMode = 'update';
        modalMemberId = editBtn.dataset.memberId;
        modalEntryId = editBtn.dataset.entryId;
        modalTitle.textContent = 'Ubah entri poin';
        modalSubtitle.textContent = editBtn.dataset.memberName || '';
        modalPoin.value = editBtn.dataset.poin ?? '';
        openOverlay();
      }
    });

    modalSave.addEventListener('click', async () => {
      if (modalPoin.value === '' || modalPoin.value === null) {
        modalPoin.focus();
        return;
      }
      const poin = parseInt(modalPoin.value, 10);
      if (Number.isNaN(poin)) {
        showFlash('Poin harus berupa angka.', 'error');
        return;
      }

      setButtonLoading(modalSave, true);
      try {
        let payload;
        if (modalMode === 'store') {
          const url = routes.storeMember.replace('__MEMBER__', encodeURIComponent(modalMemberId));
          payload = await requestJson(url, 'POST', { poin });
        } else {
          const url = routes.update.replace('__ENTRY__', encodeURIComponent(modalEntryId));
          payload = await requestJson(url, 'PATCH', { poin });
        }
        if (payload.data) {
          replaceMember(payload.data);
        }
        closeOverlay();
        renderGroups();
        showFlash(payload.message || 'Poin berhasil disimpan.', 'success');
      } catch (error) {
        showFlash(error.message, 'error');
      } finally {
        setButtonLoading(modalSave, false);
      }
    });

    modalPoin.addEventListener('keydown', (event) => {
      if (event.key === 'Enter') {
        event.preventDefault();
        modalSave.click();
      }
    });
    modalClose.addEventListener('click', closeOverlay);
    overlay.addEventListener('click', (event) => {
      if (event.target === overlay) closeOverlay();
    });
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && overlay.classList.contains('show')) closeOverlay();
    });

    renderGroups();
  })();
</script>
@endpush
