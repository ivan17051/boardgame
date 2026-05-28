@extends('layouts.layout')

@section('content')
@php
  $fmtRp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
@endphp

<div class="app-content-header">
  <div class="container-fluid">
    <div class="row">
      <div class="col-sm-6">
        <h3 class="mb-0">Sewa Meja</h3>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-end">
          <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
          <li class="breadcrumb-item active" aria-current="page">Sewa Meja</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<div class="app-content">
  <div class="container-fluid">
    
    @php $totalMeja = $tokos->sum(fn ($t) => $t->meja->count()); @endphp

    @if ($totalMeja === 0)
      <div class="alert alert-secondary">Belum ada meja. Tambah data di menu <strong>Toko</strong>.</div>
    @else
      @foreach ($tokos as $toko)
        @if ($toko->meja->isNotEmpty())
          <h5 class="text-secondary mb-2">{{ $toko->nama }}</h5>
          <div class="row g-3 mb-4">
            @foreach ($toko->meja as $meja)
              @php
                $rental = $meja->activeRental;
                $occupied = $meja->status === 'rented' && $rental;
              @endphp
              <div class="col-6 col-md-3">
                <button
                  type="button"
                  class="btn w-100 h-100 p-0 border-0 text-start meja-card {{ $occupied ? 'meja-card--occupied' : 'meja-card--available' }}"
                  data-meja-id="{{ $meja->id }}"
                  data-meja-nama="{{ $meja->nama }}"
                  data-toko-nama="{{ $toko->nama }}"
                  data-toko-id="{{ $toko->id }}"
                  data-harga-non-member="{{ (float) $meja->harga }}"
                  data-harga-member="{{ (float) ($meja->harga_member ?? $meja->harga) }}"
                  @if ($occupied)
                    data-rental-id="{{ $rental->id }}"
                    data-start-epoch="{{ $rental->waktu_start->timestamp }}"
                    data-customer="{{ $rental->nama_customer }}"
                    data-tipe="{{ $rental->tipe_customer ?? 'non_member' }}"
                  @endif
                >
                  <div class="card h-100 shadow-sm mb-0">
                    <div class="card-body p-3">
                      <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="fw-bold fs-5">{{ $meja->nama }}</span>
                        <span class="badge {{ $occupied ? 'text-bg-warning text-dark' : 'text-bg-success' }}">
                          {{ $occupied ? 'Disewa' : 'Tersedia' }}
                        </span>
                      </div>
                      @if ($occupied)
                        <div class="font-monospace fs-4 fw-semibold text-dark mb-1 meja-timer">00:00:00</div>
                        <div class="small text-truncate" title="{{ $rental->nama_customer }}">{{ $rental->nama_customer }}</div>
                        <div class="small text-secondary">{{ $rental->isMember() ? 'Member' : 'Non-Member' }}</div>
                        <div class="small text-secondary">Mulai: {{ $rental->waktu_start->format('H:i') }}</div>
                      @else
                        <div class="small text-secondary mt-2">Non-Mbr: {{ $fmtRp($meja->harga) }}/jam</div>
                        <div class="small text-secondary">Member: {{ $fmtRp($meja->harga_member ?? $meja->harga) }}/jam</div>
                        <div class="small text-success mt-2">Ketuk untuk check-in</div>
                      @endif
                    </div>
                  </div>
                </button>
              </div>
            @endforeach
          </div>
        @endif
      @endforeach
    @endif
  </div>
</div>

{{-- Check-in --}}
<div class="modal fade" id="checkinModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Check-in — <span id="checkinMejaLabel">Meja</span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <form id="checkinForm" novalidate data-no-page-loader>
        <div class="modal-body">
          <div id="checkinAlert" class="alert alert-danger d-none"></div>
          <input type="hidden" id="checkin_id_meja" name="id_meja" />
          <div class="mb-3">
            <label class="form-label d-block">Tipe customer</label>
            <div class="btn-group w-100" role="group">
              <input type="radio" class="btn-check" name="tipe_customer" id="tipe_non_member" value="non_member" checked />
              <label class="btn btn-outline-primary" for="tipe_non_member">Non-Member</label>
              <input type="radio" class="btn-check" name="tipe_customer" id="tipe_member" value="member" />
              <label class="btn btn-outline-primary" for="tipe_member">Member</label>
            </div>
            <div class="form-text mt-2" id="checkinRateHint">Tarif: —</div>
          </div>
          <div class="mb-0">
            <label for="checkin_nama_customer" class="form-label">Nama customer</label>
            <input type="text" class="form-control" id="checkin_nama_customer" name="nama_customer" required maxlength="255" autocomplete="name" />
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-success">Mulai sewa</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Checkout --}}
<div class="modal fade" id="checkoutModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Checkout — <span id="checkoutMejaLabel">Meja</span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div id="checkoutSummary" class="border rounded p-3 bg-body-secondary small mb-3">Memuat…</div>

        <h6 class="fw-semibold">Item tambahan (F&amp;B)</h6>
        <div id="additionalItemsEmpty" class="text-secondary small mb-2 d-none">Belum ada item di master. Tambah di menu <strong>Item tambahan</strong>.</div>
        <div class="table-responsive mb-2">
          <table class="table table-sm align-middle mb-0" id="additionalItemsTable">
            <thead class="table-light">
              <tr>
                <th>Item</th>
                <th class="text-end" style="width:100px">Harga</th>
                <th style="width:90px">Qty</th>
                <th class="text-end" style="width:110px">Subtotal</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($additionalItems as $item)
                <tr data-item-id="{{ $item->id }}" data-item-harga="{{ (float) $item->harga }}" data-item-toko="{{ (int) ($item->id_toko ?? 0) }}">
                  <td>{{ $item->nama }}</td>
                  <td class="text-end font-monospace small">{{ $fmtRp($item->harga) }}</td>
                  <td>
                    <input type="number" class="form-control form-control-sm additional-qty" min="0" max="999" value="0" data-item-id="{{ $item->id }}" />
                  </td>
                  <td class="text-end font-monospace small additional-line-total">Rp 0</td>
                </tr>
              @empty
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="border rounded p-3 bg-light mb-3">
          <div class="d-flex justify-content-between"><span>Biaya sewa meja</span><span class="font-monospace" id="checkoutSewaTotal">Rp 0</span></div>
          <div class="d-flex justify-content-between"><span>Item tambahan</span><span class="font-monospace" id="checkoutAdditionalTotal">Rp 0</span></div>
          <hr class="my-2" />
          <div class="d-flex justify-content-between fw-bold fs-5"><span>Total tagihan</span><span class="font-monospace text-primary" id="checkoutGrandTotal">Rp 0</span></div>
        </div>

        <h6 class="fw-semibold mb-2">Pembayaran</h6>
        <div id="checkoutPaymentAlert" class="alert alert-danger d-none small"></div>

        <div class="mb-3">
          <label for="checkout_jumlah_bayar" class="form-label">Jumlah bayar</label>
          <div class="input-group">
            <span class="input-group-text">Rp</span>
            <input type="number" class="form-control" id="checkout_jumlah_bayar" min="0" step="1" />
          </div>
          <div class="form-text">Opsional. Jika metode pembayaran dipilih, default mengikuti total tagihan.</div>
        </div>

        <div class="mb-3">
          <label for="checkout_metode" class="form-label">Metode pembayaran</label>
          <select class="form-select" id="checkout_metode">
            <option value="">— Bayar nanti (isi di Data Sewa) —</option>
            <option value="tunai">Tunai</option>
            <option value="transfer">Transfer bank</option>
            <option value="qris">QRIS / e-wallet</option>
            <option value="kartu">Kartu debit/kredit</option>
            <option value="lainnya">Lainnya</option>
          </select>
          <div class="form-text">Jika belum dibayar sekarang, biarkan kosong. Bisa diisi belakangan di menu <strong>Data Sewa</strong>.</div>
        </div>

        <div class="mb-0">
          <label for="checkout_bukti" class="form-label">Bukti bayar <span id="checkout_bukti_required" class="text-danger">*</span></label>
          <input
            type="file"
            class="form-control"
            id="checkout_bukti"
            accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf"
          />
          <div class="form-text" id="checkout_bukti_help">Opsional. Unggah jika diperlukan arsip.</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="checkoutConfirmBtn" disabled>Checkout &amp; simpan pembayaran</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
  .meja-card { cursor: pointer; transition: transform 0.12s ease, box-shadow 0.12s ease; }
  .meja-card--available .card { border: 3px solid #198754; background: linear-gradient(180deg, #ecfdf3 0%, #f8fff9 100%); }
  .meja-card--occupied .card { border: 3px solid #fd7e14; background: linear-gradient(180deg, #fff4e8 0%, #fffaf5 100%); }
  .meja-card--available:hover .card { transform: translateY(-2px); box-shadow: 0 0.5rem 1rem rgba(25, 135, 84, 0.2); }
  .meja-card--occupied:hover .card { transform: translateY(-2px); box-shadow: 0 0.5rem 1rem rgba(253, 126, 20, 0.2); }
  .meja-card--available:focus-visible .card,
  .meja-card--occupied:focus-visible .card { box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.4); outline: none; }
</style>
@endpush

@push('scripts')
@php
  $masterItemsForJs = $additionalItems->map(function ($i) {
    return [
      'id' => $i->id,
      'id_toko' => (int) ($i->id_toko ?? 0),
      'nama' => $i->nama,
      'harga' => (float) $i->harga,
    ];
  })->values();
  $canSeeAllToko = \App\Support\TokoScope::canSeeAll();
@endphp
<script>
(function () {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  const masterItems = @json($masterItemsForJs);
  const routes = {
    store: @json(route('rental.store')),
    checkoutPreview: (id) => @json(url('/sewa')) + '/' + id + '/checkout-preview',
    checkout: (id) => @json(url('/sewa')) + '/' + id + '/checkout',
  };

  const checkinModalEl = document.getElementById('checkinModal');
  const checkinModal = checkinModalEl ? new bootstrap.Modal(checkinModalEl) : null;
  const checkoutModalEl = document.getElementById('checkoutModal');
  const checkoutModal = checkoutModalEl ? new bootstrap.Modal(checkoutModalEl) : null;

  let checkinMeja = null;
  let checkoutRentalId = null;
  let checkoutEndedAt = null;
  let checkoutGrandTotal = 0;
  let previewTimer = null;

  const checkoutJumlahBayarEl = document.getElementById('checkout_jumlah_bayar');
  const checkoutMetodeEl = document.getElementById('checkout_metode');
  const checkoutBuktiEl = document.getElementById('checkout_bukti');
  const checkoutBuktiRequiredMark = document.getElementById('checkout_bukti_required');
  const checkoutBuktiHelpEl = document.getElementById('checkout_bukti_help');
  const checkoutPaymentAlert = document.getElementById('checkoutPaymentAlert');

  function pad2(n) { return String(n).padStart(2, '0'); }
  function formatHMS(totalSeconds) {
    const s = Math.max(0, Math.floor(totalSeconds));
    const h = Math.floor(s / 3600);
    const m = Math.floor((s % 3600) / 60);
    const sec = s % 60;
    return pad2(h) + ':' + pad2(m) + ':' + pad2(sec);
  }
  function fmtRp(n) {
    return 'Rp ' + Number(n || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });
  }
  function escapeHtml(s) {
    const d = document.createElement('div');
    d.textContent = s || '';
    return d.innerHTML;
  }

  function tickTimers() {
    const nowSec = Math.floor(Date.now() / 1000);
    document.querySelectorAll('.meja-card--occupied[data-start-epoch]').forEach(function (btn) {
      const start = parseInt(btn.getAttribute('data-start-epoch'), 10);
      const el = btn.querySelector('.meja-timer');
      if (el && !Number.isNaN(start)) el.textContent = formatHMS(nowSec - start);
    });
  }
  tickTimers();
  setInterval(tickTimers, 1000);

  function updateCheckinRateHint() {
    const hint = document.getElementById('checkinRateHint');
    if (!hint || !checkinMeja) return;
    const member = document.getElementById('tipe_member')?.checked;
    const rate = member ? checkinMeja.hargaMember : checkinMeja.hargaNonMember;
    hint.textContent = 'Tarif: ' + fmtRp(rate) + ' / jam (' + (member ? 'Member' : 'Non-Member') + ')';
  }

  document.querySelectorAll('input[name="tipe_customer"]').forEach(function (inp) {
    inp.addEventListener('change', updateCheckinRateHint);
  });

  document.querySelectorAll('.meja-card--available').forEach(function (btn) {
    btn.addEventListener('click', function () { openCheckin(btn); });
  });
  document.querySelectorAll('.meja-card--occupied').forEach(function (btn) {
    btn.addEventListener('click', function () { openCheckout(btn); });
  });

  function openCheckin(btn) {
    checkinMeja = {
      id: btn.getAttribute('data-meja-id'),
      nama: btn.getAttribute('data-meja-nama'),
      hargaNonMember: parseFloat(btn.getAttribute('data-harga-non-member')) || 0,
      hargaMember: parseFloat(btn.getAttribute('data-harga-member')) || 0,
    };
    document.getElementById('checkinMejaLabel').textContent = checkinMeja.nama;
    document.getElementById('checkin_id_meja').value = checkinMeja.id;
    document.getElementById('checkin_nama_customer').value = '';
    document.getElementById('tipe_non_member').checked = true;
    document.getElementById('checkinAlert')?.classList.add('d-none');
    updateCheckinRateHint();
    checkinModal?.show();
  }

  function hideCheckoutPaymentAlert() {
    if (!checkoutPaymentAlert) return;
    checkoutPaymentAlert.classList.add('d-none');
    checkoutPaymentAlert.textContent = '';
  }

  function showCheckoutPaymentAlert(msg) {
    if (!checkoutPaymentAlert) return;
    checkoutPaymentAlert.textContent = msg || 'Terjadi kesalahan.';
    checkoutPaymentAlert.classList.remove('d-none');
  }

  function syncCheckoutBuktiField() {
    const metode = checkoutMetodeEl ? checkoutMetodeEl.value : '';
    const isEmpty = metode === '';
    const isTunai = checkoutMetodeEl && checkoutMetodeEl.value === 'tunai';
    if (checkoutBuktiRequiredMark) {
      checkoutBuktiRequiredMark.classList.add('d-none');
    }
    if (checkoutBuktiHelpEl) {
      checkoutBuktiHelpEl.textContent = isEmpty
        ? 'Kosongkan jika pembayaran akan dilengkapi nanti di Data Sewa.'
        : (isTunai
          ? 'Opsional untuk tunai. Wajib untuk transfer, QRIS, kartu, dan lainnya.'
          : 'Opsional. JPG, PNG, WEBP, atau PDF. Maks. 5 MB.');
    }
  }

  checkoutMetodeEl?.addEventListener('change', syncCheckoutBuktiField);
  syncCheckoutBuktiField();

  function resetCheckoutPaymentFields() {
    if (checkoutMetodeEl) checkoutMetodeEl.value = '';
    if (checkoutJumlahBayarEl) checkoutJumlahBayarEl.value = '';
    if (checkoutBuktiEl) checkoutBuktiEl.value = '';
    syncCheckoutBuktiField();
    hideCheckoutPaymentAlert();
  }

  function openCheckout(btn) {
    checkoutRentalId = btn.getAttribute('data-rental-id');
    checkoutEndedAt = Math.floor(Date.now() / 1000);
    checkoutGrandTotal = 0;
    document.getElementById('checkoutMejaLabel').textContent = btn.getAttribute('data-meja-nama') || 'Meja';
    const tokoId = parseInt(btn.getAttribute('data-toko-id') || '0', 10) || 0;
    const canSeeAll = @json($canSeeAllToko);
    if (canSeeAll && tokoId) {
      document.querySelectorAll('#additionalItemsTable tbody tr[data-item-id]').forEach(function (row) {
        const itemToko = parseInt(row.getAttribute('data-item-toko') || '0', 10) || 0;
        row.classList.toggle('d-none', itemToko !== tokoId);
      });
    } else {
      document.querySelectorAll('#additionalItemsTable tbody tr[data-item-id]').forEach(function (row) {
        row.classList.remove('d-none');
      });
    }
    resetAdditionalQty();
    resetCheckoutPaymentFields();
    document.getElementById('checkoutSummary').innerHTML = '<p class="mb-0 text-secondary">Memuat…</p>';
    document.getElementById('checkoutConfirmBtn').disabled = true;
    document.getElementById('additionalItemsEmpty')?.classList.toggle('d-none', masterItems.length > 0);
    checkoutModal?.show();
    refreshCheckoutPreview();
  }

  function resetAdditionalQty() {
    document.querySelectorAll('.additional-qty').forEach(function (inp) {
      inp.value = '0';
      const row = inp.closest('tr');
      const cell = row?.querySelector('.additional-line-total');
      if (cell) cell.textContent = fmtRp(0);
    });
  }

  function collectAdditionalItems() {
    const items = [];
    document.querySelectorAll('.additional-qty').forEach(function (inp) {
      const qty = parseInt(inp.value, 10) || 0;
      if (qty > 0) items.push({ id: parseInt(inp.getAttribute('data-item-id'), 10), qty: qty });
    });
    return items;
  }

  function refreshCheckoutPreview() {
    if (!checkoutRentalId) return;
    if (previewTimer) clearTimeout(previewTimer);

    const additional_items = collectAdditionalItems();

    fetch(routes.checkoutPreview(checkoutRentalId), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrf,
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ ended_at: checkoutEndedAt, additional_items: additional_items }),
    })
      .then(function (res) { return res.json().then(function (body) { return { ok: res.ok, body: body }; }); })
      .then(function (r) {
        if (!r.ok) {
          document.getElementById('checkoutSummary').innerHTML = '<p class="text-danger mb-0">Gagal memuat.</p>';
          return;
        }
        const d = r.body;
        document.getElementById('checkoutSummary').innerHTML =
          '<p class="mb-1"><strong>' + escapeHtml(d.nama_customer) + '</strong> · ' + escapeHtml(d.tipe_customer_label) + '</p>' +
          '<p class="mb-2 font-monospace fs-5 fw-semibold">' + escapeHtml(d.durasi_hms) + '</p>' +
          (d.breakdown_html || '');
        checkoutGrandTotal = Number(d.total_harga) || 0;
        document.getElementById('checkoutSewaTotal').textContent = fmtRp(d.total_harga_sewa);
        document.getElementById('checkoutAdditionalTotal').textContent = fmtRp(d.total_harga_additional);
        document.getElementById('checkoutGrandTotal').textContent = fmtRp(checkoutGrandTotal);
        if (checkoutJumlahBayarEl && (checkoutJumlahBayarEl.value === '' || checkoutJumlahBayarEl.dataset.auto === '1')) {
          checkoutJumlahBayarEl.value = String(Math.round(checkoutGrandTotal));
          checkoutJumlahBayarEl.dataset.auto = '1';
        }
        document.getElementById('checkoutConfirmBtn').disabled = false;

        (d.additional_lines || []).forEach(function (line) {
          const inp = document.querySelector('.additional-qty[data-item-id="' + line.id + '"]');
          if (inp) {
            inp.value = String(line.qty);
            const row = inp.closest('tr');
            const cell = row?.querySelector('.additional-line-total');
            if (cell) cell.textContent = fmtRp(line.subtotal);
          }
        });
      })
      .catch(function () {
        document.getElementById('checkoutSummary').innerHTML = '<p class="text-danger mb-0">Jaringan bermasalah.</p>';
      });
  }

  document.querySelectorAll('.additional-qty').forEach(function (inp) {
    inp.addEventListener('input', function () {
      const row = inp.closest('tr');
      const harga = parseFloat(row?.getAttribute('data-item-harga') || '0');
      const qty = parseInt(inp.value, 10) || 0;
      const cell = row?.querySelector('.additional-line-total');
      if (cell) cell.textContent = fmtRp(harga * qty);
      clearTimeout(previewTimer);
      previewTimer = setTimeout(refreshCheckoutPreview, 400);
    });
  });

  document.getElementById('checkinForm')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const tipe = document.querySelector('input[name="tipe_customer"]:checked')?.value || 'non_member';
    const payload = {
      id_meja: document.getElementById('checkin_id_meja').value,
      nama_customer: document.getElementById('checkin_nama_customer').value.trim(),
      tipe_customer: tipe,
    };
    fetch(routes.store, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json', 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })
      .then(function (res) { return res.json().then(function (body) { return { ok: res.ok, status: res.status, body: body }; }); })
      .then(function (r) {
        if (r.ok) {
          AppToast.saveForReload(r.body?.message || 'Check-in berhasil.');
          window.location.reload();
          return;
        }
        const msg = r.status === 422 && r.body?.errors
          ? (Object.values(r.body.errors)[0]?.[0] || 'Validasi gagal.')
          : (r.body?.message || 'Gagal.');
        const alert = document.getElementById('checkinAlert');
        if (alert) { alert.textContent = msg; alert.classList.remove('d-none'); }
        AppToast.show(msg, 'danger');
      })
      .catch(function () { AppToast.show('Jaringan bermasalah.', 'danger'); });
  });

  checkoutJumlahBayarEl?.addEventListener('input', function () {
    checkoutJumlahBayarEl.dataset.auto = '0';
  });

  function confirmProceedWithoutBukti(onConfirm) {
    const message = 'Anda belum mengunggah bukti pembayaran. Lanjutkan tanpa bukti? Bukti dapat dilengkapi nanti di menu Data Sewa.';
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        title: 'Bukti belum diunggah',
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, lanjutkan',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#0d6efd',
      }).then(function (result) {
        if (result.isConfirmed) onConfirm();
      });
      return;
    }
    if (window.confirm(message)) onConfirm();
  }

  document.getElementById('checkoutConfirmBtn')?.addEventListener('click', function () {
    if (!checkoutRentalId) return;
    hideCheckoutPaymentAlert();

    const metode = checkoutMetodeEl?.value || '';
    const jumlahBayarRaw = checkoutJumlahBayarEl?.value ?? '';
    const jumlahBayar = jumlahBayarRaw === '' ? NaN : parseFloat(jumlahBayarRaw);
    const hasBukti = checkoutBuktiEl?.files?.length > 0;

    if (metode) {
      if (!Number.isFinite(jumlahBayar) || jumlahBayar < 0) {
        showCheckoutPaymentAlert('Jumlah bayar wajib diisi (min. 0) jika metode pembayaran dipilih.');
        checkoutJumlahBayarEl?.focus();
        return;
      }
    }

    const btn = this;

    function doCheckout() {
      btn.disabled = true;

      const fd = new FormData();
      fd.append('ended_at', String(checkoutEndedAt));
      fd.append('additional_items', JSON.stringify(collectAdditionalItems()));
      if (metode) {
        fd.append('metode_pembayaran', metode);
        fd.append('jumlah_bayar', String(jumlahBayar));
        if (hasBukti) {
          fd.append('bukti', checkoutBuktiEl.files[0]);
        }
      }

      fetch(routes.checkout(checkoutRentalId), {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
        body: fd,
      })
        .then(function (res) { return res.json().then(function (body) { return { ok: res.ok, status: res.status, body: body }; }); })
        .then(function (r) {
          if (r.ok) {
            checkoutModal?.hide();
            const msg = r.body?.message || 'Checkout selesai.';
            if (r.body?.invoice_url) {
              window.open(r.body.invoice_url, '_blank', 'noopener,noreferrer');
            }
            AppToast.saveForReload(msg);
            window.location.reload();
            return;
          }
          btn.disabled = false;
          let errMsg = r.body?.message || 'Checkout gagal.';
          if (r.status === 422 && r.body?.errors) {
            const first = Object.values(r.body.errors)[0];
            errMsg = Array.isArray(first) ? first[0] : String(first);
          }
          showCheckoutPaymentAlert(errMsg);
          AppToast.show(errMsg, 'danger');
        })
        .catch(function () {
          btn.disabled = false;
          showCheckoutPaymentAlert('Jaringan bermasalah.');
          AppToast.show('Jaringan bermasalah.', 'danger');
        });
    }

    if (!hasBukti) {
      confirmProceedWithoutBukti(doCheckout);
      return;
    }

    doCheckout();
  });

  checkoutModalEl?.addEventListener('hidden.bs.modal', function () {
    checkoutRentalId = null;
    checkoutEndedAt = null;
    checkoutGrandTotal = 0;
    resetAdditionalQty();
    resetCheckoutPaymentFields();
  });
})();
</script>
@endpush
