<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <meta name="theme-color" content="#006131" />
  <title>Sewa Meja — Omahjong</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous" />
  <link rel="stylesheet" href="{{ asset('public/css/adminlte.css') }}" />
  <style>
    :root {
      --guest-brand: #006131;
      --guest-brand-dark: #004d26;
    }
    body.guest-rental-page {
      min-height: 100vh;
      min-height: 100dvh;
      background: linear-gradient(165deg, #f0f7f3 0%, #e8f0eb 45%, #f8faf9 100%);
    }
    .guest-shell {
      max-width: 480px;
      margin: 0 auto;
      padding: 1.25rem 1rem 2rem;
    }
    .guest-brand {
      text-align: center;
      margin-bottom: 1.5rem;
    }
    .guest-brand img {
      height: 48px;
      width: auto;
      margin-bottom: 0.5rem;
    }
    .guest-brand h1 {
      font-size: 1.35rem;
      font-weight: 700;
      color: var(--guest-brand);
      margin: 0;
    }
    .guest-brand p {
      font-size: 0.875rem;
      color: #6c757d;
      margin: 0.25rem 0 0;
    }
    .guest-card {
      background: #fff;
      border-radius: 1rem;
      border: 1px solid rgba(0, 97, 49, 0.12);
      box-shadow: 0 8px 32px rgba(0, 60, 30, 0.08);
      padding: 1.25rem;
    }
    .guest-timer {
      font-family: ui-monospace, 'Cascadia Code', monospace;
      font-size: clamp(2.5rem, 12vw, 3.5rem);
      font-weight: 700;
      letter-spacing: 0.05em;
      color: var(--guest-brand);
      text-align: center;
      line-height: 1.1;
    }
    .meja-pick-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 0.5rem;
      max-height: 220px;
      overflow-y: auto;
    }
    .meja-pick-btn {
      border: 2px solid #dee2e6;
      border-radius: 0.75rem;
      padding: 0.65rem 0.5rem;
      background: #fff;
      text-align: left;
      transition: border-color 0.15s, background 0.15s;
    }
    .meja-pick-btn:hover {
      border-color: var(--guest-brand);
    }
    .meja-pick-btn.active {
      border-color: var(--guest-brand);
      background: rgba(0, 97, 49, 0.08);
    }
    .meja-pick-btn .meja-name {
      font-weight: 600;
      font-size: 0.9rem;
      display: block;
    }
    .meja-pick-btn .meja-meta {
      font-size: 0.7rem;
      color: #6c757d;
    }
    .btn-guest-primary {
      background: var(--guest-brand);
      border-color: var(--guest-brand);
      color: #fff;
      font-weight: 600;
      padding: 0.75rem 1rem;
      border-radius: 0.75rem;
    }
    .btn-guest-primary:hover {
      background: var(--guest-brand-dark);
      border-color: var(--guest-brand-dark);
      color: #fff;
    }
    .btn-guest-stop {
      background: #dc3545;
      border-color: #dc3545;
      color: #fff;
      font-weight: 600;
      padding: 0.75rem 1rem;
      border-radius: 0.75rem;
    }
    .btn-guest-stop:hover {
      background: #bb2d3b;
      border-color: #bb2d3b;
      color: #fff;
    }
    #guestPanelActive.d-none,
    #guestPanelStart.d-none,
    #guestPanelDone.d-none {
      display: none !important;
    }
    .guest-total-amount {
      font-size: clamp(2rem, 10vw, 2.75rem);
      font-weight: 700;
      color: var(--guest-brand);
      line-height: 1.2;
    }
    .guest-payment-note {
      background: rgba(0, 97, 49, 0.08);
      border: 1px solid rgba(0, 97, 49, 0.2);
      border-radius: 0.75rem;
      padding: 1rem;
    }
  </style>
</head>
<body class="guest-rental-page">
  <div class="guest-shell">
    <header class="guest-brand">
      <img src="{{ asset('public/assets/img/logo.png') }}" alt="Omahjong" onerror="this.style.display='none'" />
      <h1>Sewa Meja</h1>
      <p>Isi nama, pilih meja, mulai &amp; hentikan waktu sendiri</p>
    </header>

    <div id="guestAlert" class="alert alert-danger d-none mb-3" role="alert"></div>

    {{-- Panel: mulai sewa --}}
    <div id="guestPanelStart" class="guest-card">
      @if ($tokos->count() > 1)
        <div class="mb-3">
          <label for="guest_toko_filter" class="form-label">Toko</label>
          <select class="form-select" id="guest_toko_filter">
            @foreach ($tokos as $t)
              <option value="{{ $t->id }}" {{ $selectedToko && $selectedToko->id === $t->id ? 'selected' : '' }}>{{ $t->nama }}</option>
            @endforeach
          </select>
        </div>
      @endif

      <div class="mb-3">
        <label for="guest_nama_customer" class="form-label">Nama Anda</label>
        <input type="text" class="form-control form-control-lg" id="guest_nama_customer" maxlength="255" placeholder="Contoh: Budi" autocomplete="name" required />
      </div>

      <div class="mb-3">
        <label class="form-label">Pilih meja <span class="text-danger">*</span></label>
        @if ($mejasAvailable->isEmpty())
          <p class="text-secondary small mb-0">Tidak ada meja tersedia saat ini. Silakan hubungi staf.</p>
        @else
          <input type="hidden" id="guest_id_meja" value="" />
          <div class="meja-pick-grid" id="guestMejaGrid">
            @foreach ($mejasAvailable as $m)
              <button
                type="button"
                class="meja-pick-btn"
                data-id="{{ $m->id }}"
                data-toko-id="{{ $m->id_toko }}"
              >
                <span class="meja-name">{{ $m->nama }}</span>
                <span class="meja-meta">{{ $m->toko->nama ?? 'Toko' }} · Rp {{ number_format((float) $m->harga, 0, ',', '.') }}/jam</span>
              </button>
            @endforeach
          </div>
        @endif
      </div>

      <button type="button" class="btn btn-guest-primary w-100" id="btnGuestStart" {{ $mejasAvailable->isEmpty() ? 'disabled' : '' }}>
        <i class="bi bi-play-fill me-1"></i> Mulai sewa
      </button>
    </div>

    {{-- Panel: sewa aktif + timer --}}
    <div id="guestPanelActive" class="guest-card d-none">
      <div class="text-center mb-3">
        <span class="badge text-bg-success mb-2">Sedang bermain</span>
        <p class="mb-0 small text-secondary" id="guestActiveMeta"></p>
      </div>
      <div class="guest-timer mb-2" id="guestTimer" aria-live="polite">00:00:00</div>
      <p class="text-center text-secondary small mb-3">Mulai: <span id="guestStartTime">—</span></p>
      <p class="text-center small mb-3">
        Tarif: <strong id="guestHargaLabel">—</strong> / jam
      </p>
      <button type="button" class="btn btn-guest-stop w-100" id="btnGuestStop">
        <i class="bi bi-stop-fill me-1"></i> Selesai &amp; hitung tagihan
      </button>
    </div>

    <div id="guestPanelDone" class="guest-card d-none text-center">
      <div class="mb-3">
        <i class="bi bi-check-circle-fill text-success" style="font-size: 2.5rem;"></i>
        <h2 class="h5 fw-bold mt-2 mb-0">Sewa selesai</h2>
        <p class="small text-secondary mb-0 mt-1" id="guestDoneMeta"></p>
      </div>
      <p class="text-secondary small mb-1">Total tagihan</p>
      <p class="guest-total-amount mb-2">Rp <span id="guestDoneTotal">0</span></p>
      <p class="small text-secondary mb-1">Durasi</p>
      <p class="guest-timer mb-1" id="guestDoneDurasiHms" style="font-size:1.75rem;">00:00:00</p>
      <p class="small text-secondary mb-3" id="guestDoneDurasiMenit"></p>
      <div class="guest-payment-note text-start mb-3">
        <p class="mb-0 fw-semibold" style="color: var(--guest-brand);">
          <i class="bi bi-cash-coin me-1"></i>
          Silahkan menuju ke Kasir untuk melakukan Pembayaran
        </p>
        <p class="mb-0 small text-secondary mt-2" id="guestDoneKeterangan"></p>
      </div>
      <button type="button" class="btn btn-guest-primary w-100" id="btnGuestDoneClose">
        Selesai
      </button>
    </div>

    <p class="text-center mt-3 mb-0">
      <a href="{{ route('login') }}" class="small text-secondary">Masuk sebagai staf</a>
    </p>
  </div>

  {{-- Modal ringkasan stop --}}
  <div class="modal fade" id="guestStopModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Ringkasan sewa</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
          <div id="guestStopSummary" class="small">Memuat…</div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="button" class="btn btn-guest-primary" id="btnGuestConfirmStop" disabled>Konfirmasi selesai</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
  <script>
  (function () {
    const STORAGE_KEY = 'omahjong_guest_token';
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const routes = {
      active: @json(route('guest.rental.active')),
      start: @json(route('guest.rental.start')),
      preview: (id) => @json(url('/guest/sewa/rental')) + '/' + id + '/preview',
      stop: (id) => @json(url('/guest/sewa/rental')) + '/' + id + '/stop',
      index: @json(route('home')),
    };

    const panelStart = document.getElementById('guestPanelStart');
    const panelActive = document.getElementById('guestPanelActive');
    const panelDone = document.getElementById('guestPanelDone');
    const btnDoneClose = document.getElementById('btnGuestDoneClose');
    const alertEl = document.getElementById('guestAlert');
    const timerEl = document.getElementById('guestTimer');
    const btnStart = document.getElementById('btnGuestStart');
    const btnStop = document.getElementById('btnGuestStop');
    const idMejaInput = document.getElementById('guest_id_meja');
    const tokoFilter = document.getElementById('guest_toko_filter');
    const stopModalEl = document.getElementById('guestStopModal');
    const stopModal = stopModalEl ? new bootstrap.Modal(stopModalEl) : null;
    const stopSummary = document.getElementById('guestStopSummary');
    const btnConfirmStop = document.getElementById('btnGuestConfirmStop');

    let activeRental = null;
    let timerInterval = null;
    let frozenEndEpoch = null;

    function getToken() {
      try {
        return localStorage.getItem(STORAGE_KEY) || '';
      } catch (_) {
        return '';
      }
    }

    function setToken(token) {
      try {
        if (token) localStorage.setItem(STORAGE_KEY, token);
        else localStorage.removeItem(STORAGE_KEY);
      } catch (_) {}
    }

    function guestHeaders(json) {
      const h = {
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrf,
        'X-Guest-Token': getToken(),
      };
      if (json) h['Content-Type'] = 'application/json';
      return h;
    }

    function showAlert(msg) {
      if (!alertEl) return;
      alertEl.textContent = msg;
      alertEl.classList.remove('d-none');
    }

    function hideAlert() {
      if (!alertEl) return;
      alertEl.classList.add('d-none');
      alertEl.textContent = '';
    }

    function pad2(n) {
      return String(n).padStart(2, '0');
    }

    function formatHMS(totalSeconds) {
      const s = Math.max(0, Math.floor(totalSeconds));
      const h = Math.floor(s / 3600);
      const m = Math.floor((s % 3600) / 60);
      const sec = s % 60;
      return pad2(h) + ':' + pad2(m) + ':' + pad2(sec);
    }

    function tickTimer() {
      if (!activeRental || !timerEl) return;
      const end = frozenEndEpoch != null ? frozenEndEpoch : Math.floor(Date.now() / 1000);
      timerEl.textContent = formatHMS(end - activeRental.start_epoch);
    }

    function freezeTimer() {
      if (!activeRental) return null;
      frozenEndEpoch = Math.floor(Date.now() / 1000);
      if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
      }
      tickTimer();
      return frozenEndEpoch;
    }

    function resumeTimer() {
      if (!activeRental) return;
      frozenEndEpoch = null;
      startTimerInterval();
    }

    function startTimerInterval() {
      if (timerInterval) clearInterval(timerInterval);
      frozenEndEpoch = null;
      tickTimer();
      timerInterval = setInterval(tickTimer, 1000);
    }

    function showActivePanel(rental) {
      activeRental = rental;
      panelStart?.classList.add('d-none');
      panelDone?.classList.add('d-none');
      panelActive?.classList.remove('d-none');
      document.getElementById('guestActiveMeta').textContent =
        (rental.nama_customer || '') + ' · ' + (rental.nama_toko || '') + ' — ' + (rental.nama_meja || '');
      document.getElementById('guestStartTime').textContent = rental.waktu_start || '—';
      document.getElementById('guestHargaLabel').textContent = 'Rp ' + (rental.harga_per_jam_formatted || '0');
      startTimerInterval();
      hideAlert();
    }

    function showStartPanel() {
      activeRental = null;
      frozenEndEpoch = null;
      if (timerInterval) clearInterval(timerInterval);
      panelActive?.classList.add('d-none');
      panelDone?.classList.add('d-none');
      panelStart?.classList.remove('d-none');
    }

    function showDonePanel(summary) {
      activeRental = null;
      frozenEndEpoch = null;
      if (timerInterval) clearInterval(timerInterval);
      panelStart?.classList.add('d-none');
      panelActive?.classList.add('d-none');
      panelDone?.classList.remove('d-none');

      const meta = [];
      if (summary.nama_customer) meta.push(summary.nama_customer);
      if (summary.nama_toko) meta.push(summary.nama_toko);
      if (summary.nama_meja) meta.push(summary.nama_meja);
      const metaEl = document.getElementById('guestDoneMeta');
      if (metaEl) metaEl.textContent = meta.join(' · ');

      const totalEl = document.getElementById('guestDoneTotal');
      if (totalEl) totalEl.textContent = summary.total_harga_formatted || '0';

      const durasiHmsEl = document.getElementById('guestDoneDurasiHms');
      if (durasiHmsEl) durasiHmsEl.textContent = summary.durasi_hms || '00:00:00';

      const durasiMenitEl = document.getElementById('guestDoneDurasiMenit');
      if (durasiMenitEl) {
        const menit = summary.durasi_menit_formatted || '—';
        const selesai = summary.waktu_end ? ' · Selesai ' + summary.waktu_end : '';
        durasiMenitEl.textContent = '(' + menit + ' menit)' + selesai;
      }

      const ketEl = document.getElementById('guestDoneKeterangan');
      if (ketEl) ketEl.textContent = summary.keterangan || '';

      hideAlert();
    }

    async function loadActive() {
      const token = getToken();
      if (!token) {
        showStartPanel();
        return;
      }
      try {
        const res = await fetch(routes.active + '?guest_token=' + encodeURIComponent(token), {
          headers: guestHeaders(false),
        });
        const data = await res.json().catch(() => ({}));
        if (data.active && data.rental) {
          showActivePanel(data.rental);
        } else {
          setToken('');
          showStartPanel();
        }
      } catch (_) {
        showStartPanel();
      }
    }

    document.querySelectorAll('.meja-pick-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        document.querySelectorAll('.meja-pick-btn').forEach(function (b) {
          b.classList.remove('active');
        });
        btn.classList.add('active');
        if (idMejaInput) idMejaInput.value = btn.dataset.id || '';
      });
    });

    if (tokoFilter) {
      tokoFilter.addEventListener('change', function () {
        const v = tokoFilter.value;
        const url = v ? routes.index + '?toko=' + encodeURIComponent(v) : routes.index;
        window.location.href = url;
      });
    }

    btnStart?.addEventListener('click', async function () {
      hideAlert();
      const nama = document.getElementById('guest_nama_customer')?.value.trim() || '';
      const idMeja = idMejaInput?.value || '';
      if (!nama) {
        showAlert('Nama wajib diisi.');
        return;
      }
      if (!idMeja) {
        showAlert('Pilih meja terlebih dahulu.');
        return;
      }
      btnStart.disabled = true;
      try {
        const res = await fetch(routes.start, {
          method: 'POST',
          headers: guestHeaders(true),
          body: JSON.stringify({ nama_customer: nama, id_meja: parseInt(idMeja, 10) }),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
          if (res.status === 422 && data.errors) {
            const first = Object.values(data.errors)[0];
            showAlert(Array.isArray(first) ? first[0] : String(first));
          } else {
            showAlert(data.message || 'Gagal memulai sewa.');
          }
          return;
        }
        if (data.guest_token) setToken(data.guest_token);
        if (data.rental) showActivePanel(data.rental);
      } catch (_) {
        showAlert('Jaringan bermasalah. Coba lagi.');
      } finally {
        btnStart.disabled = false;
      }
    });

    if (stopModalEl) {
      stopModalEl.addEventListener('hidden.bs.modal', function () {
        if (activeRental && frozenEndEpoch != null) {
          resumeTimer();
        }
        btnConfirmStop.disabled = true;
      });
    }

    btnStop?.addEventListener('click', function () {
      if (!activeRental || !stopModal) return;
      hideAlert();
      const endedAt = freezeTimer();
      btnConfirmStop.disabled = true;
      stopSummary.innerHTML = '<p class="text-secondary mb-0">Memuat…</p>';
      stopModal.show();

      const previewUrl = routes.preview(activeRental.id) +
        (endedAt != null ? '?ended_at=' + encodeURIComponent(String(endedAt)) : '');

      fetch(previewUrl, { headers: guestHeaders(false) })
        .then(function (res) {
          return res.json().then(function (body) {
            return { ok: res.ok, body: body };
          });
        })
        .then(function (r) {
          if (!r.ok) {
            stopSummary.innerHTML = '<p class="text-danger mb-0">Gagal memuat rincian.</p>';
            return;
          }
          const d = r.body;
          stopSummary.innerHTML =
            '<p class="mb-2"><strong>' + escapeHtml(d.nama_meja) + '</strong> · ' + escapeHtml(d.nama_customer) + '</p>' +
            '<p class="mb-2 text-secondary small">Mulai: ' + escapeHtml(d.waktu_start) +
            (d.waktu_end ? '<br>Selesai: ' + escapeHtml(d.waktu_end) : '') + '</p>' +
            (d.durasi_hms ? '<p class="mb-2 font-monospace fw-semibold">' + escapeHtml(d.durasi_hms) + '</p>' : '') +
            (d.breakdown_html || '');
          btnConfirmStop.disabled = false;
        })
        .catch(function () {
          stopSummary.innerHTML = '<p class="text-danger mb-0">Jaringan bermasalah.</p>';
        });
    });

    btnConfirmStop?.addEventListener('click', function () {
      if (!activeRental) return;
      const endedAt = frozenEndEpoch != null ? frozenEndEpoch : Math.floor(Date.now() / 1000);
      btnConfirmStop.disabled = true;
      fetch(routes.stop(activeRental.id), {
        method: 'POST',
        headers: guestHeaders(true),
        body: JSON.stringify({ ended_at: endedAt }),
      })
        .then(function (res) {
          return res.json().then(function (body) {
            return { ok: res.ok, body: body };
          });
        })
        .then(function (r) {
          if (r.ok) {
            setToken('');
            stopModal?.hide();
            const summary = (r.body && r.body.summary) ? r.body.summary : {};
            showDonePanel(summary);
            return;
          }
          btnConfirmStop.disabled = false;
          showAlert((r.body && r.body.message) || 'Gagal menyelesaikan sewa.');
          stopModal?.hide();
        })
        .catch(function () {
          btnConfirmStop.disabled = false;
          showAlert('Jaringan bermasalah.');
          stopModal?.hide();
        });
    });

    function escapeHtml(s) {
      if (!s) return '';
      const div = document.createElement('div');
      div.textContent = s;
      return div.innerHTML;
    }

    btnDoneClose?.addEventListener('click', function () {
      showStartPanel();
      document.getElementById('guest_nama_customer').value = '';
      if (idMejaInput) idMejaInput.value = '';
      document.querySelectorAll('.meja-pick-btn').forEach(function (b) {
        b.classList.remove('active');
      });
    });

    loadActive();
  })();
  </script>
</body>
</html>
