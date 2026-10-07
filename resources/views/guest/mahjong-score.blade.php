<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <meta name="theme-color" content="#006131" />
  <title>Catat Poin Mahjong — Omahjong</title>
  <link rel="icon" type="image/png" href="{{ asset('public/assets/img/logo.png') }}" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous" />
  <link rel="stylesheet" href="{{ asset('public/css/adminlte.css') }}" />
  <style>
    :root {
      --score-brand: #006131;
      --score-brand-dark: #004d26;
    }
    body.guest-score-page {
      min-height: 100vh;
      min-height: 100dvh;
      background: linear-gradient(165deg, #f0f7f3 0%, #e8f0eb 45%, #f8faf9 100%);
    }
    .score-shell {
      max-width: 480px;
      margin: 0 auto;
      padding: 1rem 1rem 2.5rem;
    }
    .score-brand {
      text-align: center;
      margin-bottom: 1rem;
    }
    .score-brand img {
      height: 40px;
      width: auto;
    }
    .score-brand h1 {
      font-size: 1.2rem;
      font-weight: 700;
      color: var(--score-brand);
      margin: 0.35rem 0 0;
    }
    .score-meta {
      text-align: center;
      color: #6c757d;
      font-size: 0.875rem;
      margin-bottom: 1rem;
    }
    .score-card {
      background: #fff;
      border-radius: 1rem;
      border: 1px solid rgba(0, 97, 49, 0.12);
      box-shadow: 0 8px 32px rgba(0, 60, 30, 0.08);
      padding: 1rem;
      margin-bottom: 1rem;
    }
    .score-card h2 {
      font-size: 1rem;
      font-weight: 700;
      margin: 0 0 0.75rem;
    }
    .totals-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0.6rem;
    }
    .total-chip {
      background: #f3f8f5;
      border-radius: 0.75rem;
      padding: 0.65rem 0.75rem;
      border: 1px solid rgba(0, 97, 49, 0.08);
    }
    .total-chip .name {
      font-size: 0.8rem;
      color: #495057;
      margin-bottom: 0.15rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .total-chip .pts {
      font-size: 1.35rem;
      font-weight: 700;
      font-variant-numeric: tabular-nums;
      color: var(--score-brand-dark);
    }
    .seat-row {
      display: grid;
      grid-template-columns: 2.2rem 1fr;
      gap: 0.5rem;
      align-items: center;
      margin-bottom: 0.55rem;
    }
    .seat-row .seat-no {
      width: 2.2rem;
      height: 2.2rem;
      border-radius: 999px;
      background: var(--score-brand);
      color: #fff;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 0.85rem;
    }
    .seat-row input {
      font-size: 1.05rem;
      min-height: 2.6rem;
    }
    .score-player-row {
      display: grid;
      grid-template-columns: 2.2rem 1fr;
      gap: 0.5rem;
      align-items: start;
      margin-bottom: 0.75rem;
    }
    .score-player-name {
      font-weight: 700;
      font-size: 0.95rem;
      color: var(--score-brand-dark);
      margin-bottom: 0.2rem;
    }
    .score-player-poin-label {
      font-size: 0.78rem;
      color: #6c757d;
      margin-bottom: 0.2rem;
    }
    .history-table {
      width: 100%;
      margin: 0;
      font-size: 0.8rem;
    }
    .history-table th,
    .history-table td {
      padding: 0.45rem 0.35rem;
      vertical-align: middle;
      text-align: center;
      font-variant-numeric: tabular-nums;
    }
    .history-table th:first-child,
    .history-table td:first-child {
      text-align: left;
      white-space: nowrap;
    }
    .history-table thead th {
      background: #f3f8f5;
      font-weight: 700;
      color: var(--score-brand-dark);
      border-bottom: 1px solid rgba(0, 97, 49, 0.12);
    }
    .history-table tbody tr.voided {
      opacity: 0.5;
      text-decoration: line-through;
    }
    .history-table .is-winner {
      font-weight: 700;
      color: var(--score-brand-dark);
      background: rgba(255, 193, 7, 0.18);
    }
    .history-table .history-poin {
      width: 4.25rem;
      max-width: 100%;
      margin: 0 auto;
      text-align: center;
      font-variant-numeric: tabular-nums;
      padding: 0.25rem 0.3rem;
      min-height: 2.1rem;
    }
    .history-table .is-winner .history-poin {
      background: #fffdf3;
      border-color: #ffc107;
    }
    .history-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 0.35rem;
    }
    .reset-overlay {
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
    .reset-overlay.show {
      display: flex;
    }
    .reset-dialog {
      background: #fff;
      border-radius: 1rem;
      box-shadow: 0 20px 60px rgba(0, 40, 20, 0.25);
      width: 100%;
      max-width: 380px;
      padding: 1.15rem 1.2rem 1.2rem;
    }
    .reset-dialog h3 {
      font-size: 1.05rem;
      font-weight: 700;
      color: var(--score-brand-dark);
      margin: 0 0 0.5rem;
    }
    .winner-toggle {
      display: flex;
      flex-wrap: wrap;
      gap: 0.4rem;
      margin: 0.5rem 0 0.75rem;
    }
    .winner-toggle .btn {
      flex: 1 1 calc(50% - 0.4rem);
      min-height: 2.4rem;
    }
    .alert-slot:empty {
      display: none;
    }
    .readonly-banner {
      background: #fff3cd;
      color: #664d03;
      border-radius: 0.75rem;
      padding: 0.65rem 0.85rem;
      font-size: 0.875rem;
      margin-bottom: 1rem;
      border: 1px solid #ffecb5;
    }
    .token-missing {
      text-align: center;
      padding: 2rem 1rem;
    }
  </style>
</head>
<body class="guest-score-page">
  <div class="score-shell">
    <div class="score-brand">
      <img src="{{ asset('public/assets/img/logo.png') }}" alt="Omahjong" />
      <h1>Catat Poin Mahjong</h1>
    </div>

    <div id="tokenMissing" class="score-card token-missing d-none">
      <p class="mb-2">Link skor tidak valid.</p>
      <p class="small text-secondary mb-0">Minta kasir membuka ulang <strong>Link skor</strong> untuk meja Anda.</p>
    </div>

    <div id="scoreApp" class="d-none">
      <div class="score-meta" id="scoreMeta"></div>
      <div id="readonlyBanner" class="readonly-banner d-none">
        Sewa sudah selesai. Skor hanya bisa dilihat (tidak bisa diubah).
      </div>
      <div class="alert-slot alert alert-danger py-2" id="errorSlot"></div>
      <div class="alert-slot alert alert-success py-2" id="successSlot"></div>

      <div class="score-card" id="setupCard">
        <h2>Nama pemain</h2>
        <p class="small text-secondary mb-2">Isi 4 nama / julukan di meja ini.</p>
        <div id="playerInputs"></div>
        <button type="button" class="btn btn-success w-100 mt-2" id="savePlayersBtn">
          Simpan nama pemain
        </button>
      </div>

      <div class="score-card d-none" id="totalsCard">
        <h2>Total poin</h2>
        <div class="totals-grid" id="totalsGrid"></div>
      </div>

      <div class="score-card d-none" id="handCard">
        <h2>Ronde baru</h2>
        <div id="scoreInputs"></div>
        <div class="small text-secondary mb-1">Pemenang dipilih otomatis dari skor tertinggi</div>
        <div class="winner-toggle" id="winnerToggle"></div>
        <button type="button" class="btn btn-primary w-100" id="saveHandBtn">
          <i class="bi bi-plus-lg me-1"></i>Simpan ronde
        </button>
      </div>

      <div class="score-card d-none" id="historyCard">
        <div class="d-flex justify-content-between align-items-center mb-2 gap-2">
          <h2 class="mb-0">Riwayat ronde</h2>
          <div class="history-actions">
            <button type="button" class="btn btn-sm btn-outline-danger d-none" id="voidLastBtn">
              Batalkan terakhir
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="resetBtn">
              Reset
            </button>
          </div>
        </div>
        <div class="table-responsive">
          <table class="history-table d-none" id="handTable">
            <thead>
              <tr id="handTableHead"></tr>
            </thead>
            <tbody id="handTableBody"></tbody>
          </table>
        </div>
        <p class="small text-secondary mb-0 d-none" id="handEmpty">Belum ada ronde.</p>
        <p class="small text-secondary mb-0 mt-2 d-none" id="handEditHint">Ketuk skor di tabel untuk mengubah ronde yang sudah tersimpan.</p>
      </div>
    </div>
  </div>

  <div class="reset-overlay" id="resetOverlay" aria-hidden="true">
    <div class="reset-dialog" role="dialog" aria-modal="true" aria-labelledby="resetTitle">
      <h3 id="resetTitle"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset skor</h3>
      <p class="small text-secondary mb-3">Semua ronde akan dihapus dan total kembali ke 0.</p>
      <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" id="resetPlayersCheck" />
        <label class="form-check-label" for="resetPlayersCheck">Reset nama pemain juga</label>
      </div>
      <div class="d-grid gap-2">
        <button type="button" class="btn btn-danger" id="resetConfirmBtn">Reset</button>
        <button type="button" class="btn btn-outline-secondary" id="resetCancelBtn">Batal</button>
      </div>
    </div>
  </div>

  <script>
  (function () {
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const STORAGE_KEY = 'omahjong_score_token';
    const routes = {
      show: @json(route('guest.mahjong-score.show')),
      players: @json(route('guest.mahjong-score.players')),
      storeHand: @json(route('guest.mahjong-score.hands.store')),
      voidLast: @json(route('guest.mahjong-score.hands.void-last')),
      updateHand: @json(route('guest.mahjong-score.hands.update', ['hand' => 0])),
      reset: @json(route('guest.mahjong-score.reset')),
    };
    const initialToken = @json($initialToken);

    let token = initialToken || localStorage.getItem(STORAGE_KEY) || '';
    if (initialToken) {
      localStorage.setItem(STORAGE_KEY, initialToken);
      token = initialToken;
    }

    let state = null;
    let winnerSeat = null;

    const els = {
      tokenMissing: document.getElementById('tokenMissing'),
      app: document.getElementById('scoreApp'),
      meta: document.getElementById('scoreMeta'),
      readonly: document.getElementById('readonlyBanner'),
      error: document.getElementById('errorSlot'),
      success: document.getElementById('successSlot'),
      setupCard: document.getElementById('setupCard'),
      playerInputs: document.getElementById('playerInputs'),
      savePlayersBtn: document.getElementById('savePlayersBtn'),
      totalsCard: document.getElementById('totalsCard'),
      totalsGrid: document.getElementById('totalsGrid'),
      handCard: document.getElementById('handCard'),
      scoreInputs: document.getElementById('scoreInputs'),
      winnerToggle: document.getElementById('winnerToggle'),
      saveHandBtn: document.getElementById('saveHandBtn'),
      historyCard: document.getElementById('historyCard'),
      handTable: document.getElementById('handTable'),
      handTableHead: document.getElementById('handTableHead'),
      handTableBody: document.getElementById('handTableBody'),
      handEmpty: document.getElementById('handEmpty'),
      handEditHint: document.getElementById('handEditHint'),
      voidLastBtn: document.getElementById('voidLastBtn'),
      resetBtn: document.getElementById('resetBtn'),
      resetOverlay: document.getElementById('resetOverlay'),
      resetPlayersCheck: document.getElementById('resetPlayersCheck'),
      resetConfirmBtn: document.getElementById('resetConfirmBtn'),
      resetCancelBtn: document.getElementById('resetCancelBtn'),
    };

    function clearAlerts() {
      els.error.textContent = '';
      els.success.textContent = '';
    }

    function showError(msg) {
      els.success.textContent = '';
      els.error.textContent = msg || 'Terjadi kesalahan.';
    }

    function showSuccess(msg) {
      els.error.textContent = '';
      els.success.textContent = msg || '';
    }

    function api(url, options) {
      options = options || {};
      const headers = Object.assign({
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrf,
        'X-Score-Token': token,
      }, options.headers || {});
      if (options.body && !(options.body instanceof FormData)) {
        headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(options.body);
      }
      const sep = url.indexOf('?') >= 0 ? '&' : '?';
      return fetch(url + sep + 'token=' + encodeURIComponent(token), Object.assign({}, options, { headers: headers }))
        .then(function (res) {
          return res.json().then(function (body) {
            return { ok: res.ok, status: res.status, body: body };
          }).catch(function () {
            return { ok: res.ok, status: res.status, body: null };
          });
        });
    }

    function firstError(body) {
      if (!body) return 'Gagal.';
      if (body.message && !body.errors) return body.message;
      if (body.errors) {
        const keys = Object.keys(body.errors);
        if (keys.length) return body.errors[keys[0]][0];
      }
      return body.message || 'Gagal.';
    }

    function renderSetup(playersReady, playersLocked, canWrite) {
      if (playersReady) {
        els.setupCard.classList.add('d-none');
        return;
      }

      const count = (state && state.player_count) || 4;
      const existing = (state && state.players) || [];
      els.playerInputs.innerHTML = '';
      const editable = canWrite && !playersLocked;
      for (let seat = 1; seat <= count; seat++) {
        const found = existing.find(function (p) { return p.seat === seat; });
        const row = document.createElement('div');
        row.className = 'seat-row';
        row.innerHTML =
          '<span class="seat-no">' + seat + '</span>' +
          '<input type="text" class="form-control player-name" data-seat="' + seat + '" maxlength="255" ' +
          'placeholder="Nama pemain ' + seat + '" value="' + (found ? String(found.nama).replace(/"/g, '&quot;') : '') + '" ' +
          (editable ? '' : 'readonly') + ' />';
        els.playerInputs.appendChild(row);
      }
      els.savePlayersBtn.classList.toggle('d-none', !editable);
      els.setupCard.classList.remove('d-none');
      const hint = els.setupCard.querySelector('p');
      if (hint) {
        hint.textContent = 'Isi 4 nama / julukan di meja ini.';
      }
    }

    function renderTotals() {
      const players = (state && state.players) || [];
      if (!players.length) {
        els.totalsCard.classList.add('d-none');
        return;
      }
      els.totalsCard.classList.remove('d-none');
      els.totalsGrid.innerHTML = players.map(function (p) {
        return '<div class="total-chip"><div class="name">' + escapeHtml(p.nama) + '</div><div class="pts">' + p.total + '</div></div>';
      }).join('');
    }

    function renderHandForm(canWrite, playersReady) {
      if (!canWrite || !playersReady) {
        els.handCard.classList.add('d-none');
        return;
      }
      els.handCard.classList.remove('d-none');
      const players = state.players || [];
      els.scoreInputs.innerHTML = '';
      players.forEach(function (p) {
        const row = document.createElement('div');
        row.className = 'score-player-row';
        row.innerHTML =
          '<span class="seat-no">' + p.seat + '</span>' +
          '<div>' +
            '<div class="score-player-name">' + escapeHtml(p.nama) + '</div>' +
            '<input type="number" inputmode="numeric" class="form-control hand-poin" data-seat="' + p.seat + '" ' +
            'placeholder="Isi Poin" value="" />' +
          '</div>';
        els.scoreInputs.appendChild(row);
      });
      winnerSeat = null;
      els.winnerToggle.innerHTML = players.map(function (p) {
        return '<button type="button" class="btn btn-outline-secondary btn-sm js-winner" data-seat="' + p.seat + '">' +
          escapeHtml(p.nama) + '</button>';
      }).join('');
      syncWinnerFromHandInputs();
    }

    function renderHistory(canWrite) {
      const hands = (state && state.hands) || [];
      const players = (state && state.players) || [];
      const seats = players.map(function (p) { return p.seat; });

      if (!players.length) {
        els.historyCard.classList.add('d-none');
        return;
      }

      if (document.activeElement && document.activeElement.classList.contains('history-poin')) {
        return;
      }

      els.historyCard.classList.remove('d-none');
      const activeHands = hands.filter(function (h) { return !h.voided; });
      els.voidLastBtn.classList.toggle('d-none', !(canWrite && activeHands.length));
      els.resetBtn.classList.toggle('d-none', !(canWrite && (hands.length || players.length)));
      els.handEmpty.classList.toggle('d-none', hands.length > 0);
      els.handTable.classList.toggle('d-none', hands.length === 0);
      if (els.handEditHint) {
        els.handEditHint.classList.toggle('d-none', !(canWrite && hands.length));
      }

      els.handTableHead.innerHTML = '<th>Ronde</th>' + players.map(function (p) {
        return '<th>' + escapeHtml(p.nama) + '</th>';
      }).join('');

      const orderedHands = hands.slice().sort(function (a, b) {
        return (a.hand_no || 0) - (b.hand_no || 0);
      });

      els.handTableBody.innerHTML = orderedHands.map(function (h) {
        const poinBySeat = {};
        (h.scores || []).forEach(function (s) {
          poinBySeat[s.seat] = s.poin;
        });
        const editable = canWrite && !h.voided;
        const cells = seats.map(function (seat) {
          const poin = poinBySeat[seat];
          const isWinner = h.winner_seat && Number(h.winner_seat) === Number(seat);
          const display = (poin === null || poin === undefined) ? '' : poin;
          if (editable) {
            return '<td class="' + (isWinner ? 'is-winner' : '') + '" data-seat="' + seat + '">' +
              '<input type="number" inputmode="numeric" class="form-control form-control-sm history-poin" ' +
              'data-hand-id="' + h.id + '" data-seat="' + seat + '" value="' + display + '" />' +
              '</td>';
          }
          return '<td class="' + (isWinner ? 'is-winner' : '') + '">' +
            (display === '' ? '-' : display) +
            '</td>';
        }).join('');
        const rondeLabel = h.voided ? (h.hand_no + ' (batal)') : String(h.hand_no);
        const signature = seats.map(function (seat) {
          const poin = poinBySeat[seat];
          return seat + ':' + (poin === null || poin === undefined ? '' : poin);
        }).join('|');
        return '<tr class="' + (h.voided ? 'voided' : '') + '" data-hand-id="' + h.id + '" data-signature="' + signature + '"><td>' +
          escapeHtml(rondeLabel) + '</td>' + cells + '</tr>';
      }).join('');
    }

    function escapeHtml(str) {
      return String(str == null ? '' : str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
    }

    function parsePoinValue(raw) {
      const text = String(raw == null ? '' : raw).trim();
      if (text === '') return 0;
      const poin = parseInt(text, 10);
      return Number.isNaN(poin) ? null : poin;
    }

    function winnerSeatFromScoreMap(bySeat, preferredSeat) {
      const seats = Object.keys(bySeat);
      if (!seats.length) return null;
      let max = null;
      seats.forEach(function (seat) {
        const poin = bySeat[seat];
        if (max === null || poin > max) max = poin;
      });
      const leaders = seats.filter(function (seat) {
        return bySeat[seat] === max;
      }).map(function (seat) {
        return parseInt(seat, 10);
      });
      if (leaders.length === 1) return leaders[0];
      if (preferredSeat != null && leaders.indexOf(preferredSeat) !== -1) return preferredSeat;
      return null;
    }

    function paintWinnerButtons(selectedSeat) {
      Array.prototype.forEach.call(els.winnerToggle.querySelectorAll('.js-winner'), function (b) {
        const isOn = selectedSeat != null && String(b.getAttribute('data-seat')) === String(selectedSeat);
        b.classList.toggle('btn-success', isOn);
        b.classList.toggle('btn-outline-secondary', !isOn);
      });
    }

    function syncWinnerFromHandInputs() {
      const inputs = Array.prototype.slice.call(document.querySelectorAll('.hand-poin'));
      if (!inputs.length) return;
      const bySeat = {};
      let invalid = false;
      inputs.forEach(function (inp) {
        const poin = parsePoinValue(inp.value);
        if (poin === null) {
          invalid = true;
          return;
        }
        bySeat[inp.getAttribute('data-seat')] = poin;
      });
      if (invalid) return;
      winnerSeat = winnerSeatFromScoreMap(bySeat, winnerSeat);
      paintWinnerButtons(winnerSeat);
    }

    function collectRowScores(row) {
      const inputs = Array.prototype.slice.call(row.querySelectorAll('.history-poin'));
      const scores = [];
      for (let i = 0; i < inputs.length; i++) {
        const poin = parsePoinValue(inputs[i].value);
        if (poin === null) {
          return { error: 'Poin harus berupa angka.', input: inputs[i] };
        }
        scores.push({
          seat: parseInt(inputs[i].getAttribute('data-seat'), 10),
          poin: poin,
        });
      }
      return { scores: scores };
    }

    function applyRowWinner(row, selectedSeat) {
      Array.prototype.forEach.call(row.querySelectorAll('td[data-seat]'), function (td) {
        td.classList.toggle('is-winner', selectedSeat != null && String(td.getAttribute('data-seat')) === String(selectedSeat));
      });
    }

    function rowSignature(row) {
      return Array.prototype.map.call(row.querySelectorAll('.history-poin'), function (inp) {
        return inp.getAttribute('data-seat') + ':' + String(inp.value).trim();
      }).join('|');
    }

    function saveHistoryRow(row) {
      if (!row || row.getAttribute('data-saving') === '1') return;
      const handId = row.getAttribute('data-hand-id');
      if (!handId) return;
      const signature = rowSignature(row);
      if (signature === (row.getAttribute('data-signature') || '')) return;
      const collected = collectRowScores(row);
      if (collected.error) {
        showError(collected.error);
        if (collected.input) collected.input.focus();
        return;
      }
      const bySeat = {};
      collected.scores.forEach(function (s) { bySeat[s.seat] = s.poin; });
      const autoWinner = winnerSeatFromScoreMap(bySeat, null);
      applyRowWinner(row, autoWinner);
      row.setAttribute('data-saving', '1');
      const body = { scores: collected.scores };
      if (autoWinner != null) body.winner_seat = autoWinner;
      api(String(routes.updateHand).replace(/\/0$/, '/' + handId), { method: 'PUT', body: body })
        .then(function (r) {
          row.removeAttribute('data-saving');
          if (!r.ok) {
            showError(firstError(r.body));
            return;
          }
          state = r.body.data;
          row.setAttribute('data-signature', signature);
          showSuccess(r.body.message || 'Ronde diperbarui.');
          renderTotals();
          const updated = ((state && state.hands) || []).find(function (h) {
            return String(h.id) === String(handId);
          });
          if (updated) applyRowWinner(row, updated.winner_seat);
        })
        .catch(function () {
          row.removeAttribute('data-saving');
          showError('Jaringan bermasalah.');
        });
    }

    function openResetDialog() {
      els.resetPlayersCheck.checked = false;
      els.resetOverlay.classList.add('show');
      els.resetOverlay.setAttribute('aria-hidden', 'false');
    }

    function closeResetDialog() {
      els.resetOverlay.classList.remove('show');
      els.resetOverlay.setAttribute('aria-hidden', 'true');
    }

    function render() {
      if (!state) return;
      const canWrite = !!(state.session && state.session.can_write);
      const playersReady = !!(state.session && state.session.players_ready);
      const playersLocked = !!(state.session && state.session.players_locked);
      const rental = state.rental || {};
      els.meta.textContent = [rental.nama_toko, rental.nama_meja, rental.nama_customer]
        .filter(Boolean).join(' · ');
      els.readonly.classList.toggle('d-none', canWrite);
      renderSetup(playersReady, playersLocked, canWrite);
      renderTotals();
      renderHandForm(canWrite, playersReady);
      renderHistory(canWrite);
    }

    function load() {
      if (!token) {
        els.tokenMissing.classList.remove('d-none');
        els.app.classList.add('d-none');
        return;
      }
      api(routes.show, { method: 'GET' }).then(function (r) {
        if (!r.ok) {
          els.tokenMissing.classList.remove('d-none');
          els.app.classList.add('d-none');
          return;
        }
        els.tokenMissing.classList.add('d-none');
        els.app.classList.remove('d-none');
        state = r.body.data;
        clearAlerts();
        render();
      }).catch(function () {
        showError('Jaringan bermasalah.');
      });
    }

    els.savePlayersBtn.addEventListener('click', function () {
      clearAlerts();
      const inputs = Array.prototype.slice.call(document.querySelectorAll('.player-name'));
      const players = inputs.map(function (inp) {
        return { seat: parseInt(inp.getAttribute('data-seat'), 10), nama: inp.value.trim() };
      });
      els.savePlayersBtn.disabled = true;
      api(routes.players, { method: 'PUT', body: { players: players } })
        .then(function (r) {
          els.savePlayersBtn.disabled = false;
          if (!r.ok) {
            showError(firstError(r.body));
            return;
          }
          state = r.body.data;
          showSuccess(r.body.message || 'Tersimpan.');
          render();
        })
        .catch(function () {
          els.savePlayersBtn.disabled = false;
          showError('Jaringan bermasalah.');
        });
    });

    els.scoreInputs.addEventListener('input', function () {
      syncWinnerFromHandInputs();
    });

    els.winnerToggle.addEventListener('click', function (e) {
      const btn = e.target.closest('.js-winner');
      if (!btn) return;
      const seatAttr = btn.getAttribute('data-seat');
      const seat = seatAttr === '' || seatAttr == null ? null : parseInt(seatAttr, 10);
      winnerSeat = winnerSeat === seat ? null : seat;
      paintWinnerButtons(winnerSeat);
    });

    els.saveHandBtn.addEventListener('click', function () {
      clearAlerts();
      const inputs = Array.prototype.slice.call(document.querySelectorAll('.hand-poin'));
      const scores = [];
      for (let i = 0; i < inputs.length; i++) {
        const inp = inputs[i];
        const raw = inp.value.trim();
        let poin = 0;
        if (raw !== '') {
          poin = parseInt(raw, 10);
          if (Number.isNaN(poin)) {
            showError('Poin harus berupa angka.');
            inp.focus();
            return;
          }
        }
        scores.push({
          seat: parseInt(inp.getAttribute('data-seat'), 10),
          poin: poin,
        });
      }
      const bySeat = {};
      scores.forEach(function (s) { bySeat[s.seat] = s.poin; });
      winnerSeat = winnerSeatFromScoreMap(bySeat, winnerSeat);
      paintWinnerButtons(winnerSeat);
      const body = { scores: scores };
      if (winnerSeat != null) body.winner_seat = winnerSeat;
      els.saveHandBtn.disabled = true;
      api(routes.storeHand, { method: 'POST', body: body })
        .then(function (r) {
          els.saveHandBtn.disabled = false;
          if (!r.ok) {
            showError(firstError(r.body));
            return;
          }
          state = r.body.data;
          showSuccess(r.body.message || 'Ronde tersimpan.');
          render();
        })
        .catch(function () {
          els.saveHandBtn.disabled = false;
          showError('Jaringan bermasalah.');
        });
    });

    els.handTableBody.addEventListener('change', function (e) {
      const input = e.target.closest('.history-poin');
      if (!input) return;
      saveHistoryRow(input.closest('tr'));
    });

    els.handTableBody.addEventListener('focusout', function (e) {
      const input = e.target.closest('.history-poin');
      if (!input) return;
      const row = input.closest('tr');
      const next = e.relatedTarget;
      if (next && row && row.contains(next)) return;
      saveHistoryRow(row);
    });

    els.handTableBody.addEventListener('keydown', function (e) {
      if (e.key !== 'Enter') return;
      const input = e.target.closest('.history-poin');
      if (!input) return;
      e.preventDefault();
      input.blur();
    });

    els.resetBtn.addEventListener('click', function () {
      clearAlerts();
      openResetDialog();
    });

    els.resetCancelBtn.addEventListener('click', closeResetDialog);
    els.resetOverlay.addEventListener('click', function (e) {
      if (e.target === els.resetOverlay) closeResetDialog();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && els.resetOverlay.classList.contains('show')) {
        closeResetDialog();
      }
    });

    els.resetConfirmBtn.addEventListener('click', function () {
      clearAlerts();
      els.resetConfirmBtn.disabled = true;
      api(routes.reset, {
        method: 'POST',
        body: { reset_players: !!els.resetPlayersCheck.checked },
      }).then(function (r) {
        els.resetConfirmBtn.disabled = false;
        if (!r.ok) {
          showError(firstError(r.body));
          return;
        }
        state = r.body.data;
        closeResetDialog();
        showSuccess(r.body.message || 'Skor direset.');
        render();
      }).catch(function () {
        els.resetConfirmBtn.disabled = false;
        showError('Jaringan bermasalah.');
      });
    });

    els.voidLastBtn.addEventListener('click', function () {
      if (!window.confirm('Batalkan ronde terakhir?')) return;
      clearAlerts();
      els.voidLastBtn.disabled = true;
      api(routes.voidLast, { method: 'POST', body: {} })
        .then(function (r) {
          els.voidLastBtn.disabled = false;
          if (!r.ok) {
            showError(firstError(r.body));
            return;
          }
          state = r.body.data;
          showSuccess(r.body.message || 'Ronde dibatalkan.');
          render();
        })
        .catch(function () {
          els.voidLastBtn.disabled = false;
          showError('Jaringan bermasalah.');
        });
    });

    load();
  })();
  </script>
</body>
</html>
