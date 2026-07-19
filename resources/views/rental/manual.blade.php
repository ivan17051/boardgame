@extends('layouts.layout')

@section('content')
@php
  $fmtRp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
  $defaultTokoId = \App\Support\TokoScope::canSeeAll() ? 0 : (int) \App\Support\TokoScope::userIdToko();
@endphp

<div class="app-content-header">
  <div class="container-fluid">
    <div class="row">
      <div class="col-sm-6">
        <h3 class="mb-0">Input sewa manual</h3>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-end">
          <li class="breadcrumb-item"><a href="{{ route('rental.index') }}">Kasir</a></li>
          <li class="breadcrumb-item active">Input manual</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<div class="app-content">
  <div class="container-fluid">
    <div class="callout callout-info mb-4">
      <p class="mb-0">
        Untuk transaksi yang sudah selesai tanpa timer kasir. Isi <strong>meja</strong> dan <strong>jam ditagihkan</strong> untuk sewa meja, atau kosongkan keduanya (jam = 0) untuk input <strong>item tambahan saja</strong>.
      </p>
    </div>

    <div class="card">
      <div class="card-header">
        <h3 class="card-title mb-0">Form sewa manual</h3>
      </div>
      <div class="card-body">
        <form id="manualRentalForm" novalidate data-no-page-loader>
          <div id="manualAlert" class="alert alert-danger d-none"></div>

          <div class="row g-3">
            <div class="col-md-4">
              <label for="tanggal" class="form-label">Tanggal transaksi <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="tanggal" name="tanggal" value="{{ now()->toDateString() }}" required />
            </div>
            <div class="col-md-4">
              <label for="id_meja" class="form-label">Meja</label>
              <select class="form-select" id="id_meja" name="id_meja">
                <option value="">— Tanpa meja —</option>
                @foreach ($mejas as $m)
                  <option
                    value="{{ $m->id }}"
                    data-harga-non-member="{{ (float) $m->harga }}"
                    data-harga-member="{{ (float) ($m->harga_member ?? $m->harga) }}"
                    data-toko="{{ $m->toko->nama ?? '' }}"
                    data-toko-id="{{ (int) $m->id_toko }}"
                    {{ $m->status === 'rented' ? 'disabled' : '' }}
                  >
                    {{ $m->toko->nama ?? 'Toko' }} — {{ $m->nama }}
                    @if ($m->status === 'rented') (sedang disewa) @endif
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4">
              <label for="nama_customer" class="form-label">Nama customer <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="nama_customer" name="nama_customer" maxlength="255" required autocomplete="name" />
            </div>

            <div class="col-md-4">
              <label class="form-label d-block">Tipe customer <span class="text-danger">*</span></label>
              <div class="btn-group w-100" role="group">
                <input type="radio" class="btn-check" name="tipe_customer" id="manual_non_member" value="non_member" checked />
                <label class="btn btn-outline-primary" for="manual_non_member">Non-Member</label>
                <input type="radio" class="btn-check" name="tipe_customer" id="manual_member" value="member" />
                <label class="btn btn-outline-primary" for="manual_member">Member</label>
              </div>
              <div class="form-text" id="manualRateHint">Tarif: —</div>
            </div>
            <div class="col-md-4">
              <label for="jam_ditagihkan" class="form-label">Jam ditagihkan</label>
              <input type="number" class="form-control" id="jam_ditagihkan" name="jam_ditagihkan" min="0" max="999" value="0" />
              <div class="form-text">0 = tanpa sewa meja. Wajib diisi jika memilih meja.</div>
            </div>
            @if ($rentalPromos->isNotEmpty())
              <div class="col-md-4">
                <label for="manual_id_promo" class="form-label">Promo / diskon</label>
                <select class="form-select" id="manual_id_promo" name="id_promo">
                  <option value="">— Tanpa promo —</option>
                  @foreach ($rentalPromos as $promo)
                    <option
                      value="{{ $promo->id }}"
                      data-toko-id="{{ (int) $promo->id_toko }}"
                      data-rate="{{ (float) $promo->promo_hourly_rate }}"
                      data-limit="{{ ($promo->promo_duration_limit !== null && (float) $promo->promo_duration_limit > 0) ? $promo->promo_duration_limit : '' }}"
                      data-jam-mulai="{{ $promo->jamMulaiFormatted() }}"
                      data-jam-selesai="{{ $promo->jamSelesaiFormatted() }}"
                    >
                      {{ $promo->nama }} — {{ $fmtRp($promo->promo_hourly_rate) }}/jam · {{ $promo->periodeFormatted() }} · {{ $promo->jamMulaiFormatted() }}–{{ $promo->jamSelesaiFormatted() }}
                    </option>
                  @endforeach
                </select>
                <div class="form-text" id="manualPromoHint"></div>
              </div>
            @endif
            <div class="col-md-4 d-flex align-items-end">
              <div class="border rounded p-3 bg-light w-100">
                <div class="small text-secondary">Perkiraan biaya sewa</div>
                <div class="fs-5 fw-semibold font-monospace" id="previewSewa">Rp 0</div>
              </div>
            </div>
          </div>

          <hr class="my-4" />
          <div class="row">
            <div class="col-md-8">
              <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                <h6 class="fw-semibold mb-0">Item tambahan &amp; diskon</h6>
                <button type="button" class="btn btn-sm btn-outline-primary" id="manualTambahItemBtn">
                  <i class="bi bi-plus-lg me-1"></i>Tambah item
                </button>
              </div>
              <div class="table-responsive">
                <table class="table table-sm align-middle" id="manualAdditionalItemsTable">
                  <thead class="table-light">
                    <tr>
                      <th>Item</th>
                      <th class="text-end">Nilai</th>
                      <th style="width:90px">Qty</th>
                      <th class="text-end">Subtotal</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse ($additionalItems as $item)
                      <tr data-item-id="{{ $item->id }}" data-item-harga="{{ (float) $item->harga }}" data-item-discount="{{ $item->is_discount ? '1' : '0' }}" data-item-toko="{{ (int) ($item->id_toko ?? 0) }}">
                        <td>
                          {{ $item->nama }}
                          @if ($item->is_discount)
                            <span class="badge text-bg-warning text-dark ms-1">Diskon</span>
                          @endif
                        </td>
                        <td class="text-end font-monospace small">
                          @if ($item->is_discount)
                            − {{ $fmtRp($item->harga) }}
                          @else
                            {{ $fmtRp($item->harga) }}
                          @endif
                        </td>
                        <td>
                          <input type="number" class="form-control form-control-sm manual-additional-qty" min="0" max="999" value="0" data-item-id="{{ $item->id }}" />
                        </td>
                        <td class="text-end font-monospace small manual-line-total">Rp 0</td>
                      </tr>
                    @empty
                    @endforelse
                  </tbody>
                </table>
              </div>
              <nav id="manualAdditionalItemsPager" class="d-none mt-2" aria-label="Pagination item tambahan"></nav>
            </div>
            <div class="col-md-4">
              <h6 class="fw-semibold">Ringkasan &amp; pembayaran</h6>
              <div class="g-3 mb-3">
                <div class="border rounded p-3">
                  <div class="d-flex justify-content-between small"><span>Sewa meja</span><span class="font-monospace" id="sumSewa">Rp 0</span></div>
                  <div class="d-flex justify-content-between small" id="sumAdditionalRow"><span>Item tambahan</span><span class="font-monospace" id="sumAdditionalPositive">Rp 0</span></div>
                  <div class="d-flex justify-content-between small text-danger d-none" id="sumDiscountRow"><span>Diskon</span><span class="font-monospace" id="sumDiscount">− Rp 0</span></div>
                  <hr class="my-2" />
                  <div class="d-flex justify-content-between fw-bold"><span>Total</span><span class="font-monospace text-primary" id="sumGrand">Rp 0</span></div>
                </div>
              </div>
            </div>
          </div>

          <hr class="my-4" />
          

          <div class="row g-3">
            <div class="col-md-4">
              <label for="jumlah_bayar" class="form-label">Jumlah bayar <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input type="number" class="form-control" id="jumlah_bayar" min="0" step="1" required />
              </div>
            </div>
            <div class="col-md-4">
              <label for="metode_pembayaran" class="form-label">Metode pembayaran <span class="text-danger">*</span></label>
              <select class="form-select" id="metode_pembayaran" required>
                <option value="">— Pilih —</option>
                <option value="tunai">Tunai</option>
                <option value="transfer">Transfer bank</option>
                <option value="qris">QRIS / e-wallet</option>
                <option value="kartu">Kartu debit/kredit</option>
                <option value="lainnya">Lainnya</option>
              </select>
            </div>
            <div class="col-md-4">
              <label for="bukti" class="form-label">Bukti bayar <span id="bukti_required" class="text-danger">*</span></label>
              <input type="file" class="form-control" id="bukti" accept=".jpg,.jpeg,.png,.webp,.pdf" />
              <div class="form-text" id="bukti_help">Wajib untuk non-tunai.</div>
            </div>
          </div>

          <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary" id="manualSubmitBtn">Simpan transaksi</button>
            <a href="{{ route('rental.index') }}" class="btn btn-outline-secondary">Kembali ke kasir</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="quickAddItemModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Tambah item</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div id="quickAddItemAlert" class="alert alert-danger d-none small"></div>
        <div class="mb-3">
          <label for="quick_item_nama" class="form-label">Nama</label>
          <input type="text" class="form-control" id="quick_item_nama" maxlength="255" autocomplete="off" />
        </div>
        <div class="mb-0">
          <label for="quick_item_harga" class="form-label">Harga</label>
          <div class="input-group">
            <span class="input-group-text">Rp</span>
            <input type="number" class="form-control" id="quick_item_harga" min="0" step="1" />
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="quickAddItemSaveBtn">Simpan</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
  tr.item-toko-hidden,
  tr.item-page-hidden { display: none !important; }
</style>
@endpush

@push('scripts')
<script>
(function () {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
  const storeUrl = @json(route('rental.manual.store'));
  const quickAddUrl = @json(route('additional-items.quick-store'));
  const defaultTokoId = @json($defaultTokoId);
  const canSeeAllToko = @json(\App\Support\TokoScope::canSeeAll());
  const userIdToko = @json(\App\Support\TokoScope::userIdToko());
  const form = document.getElementById('manualRentalForm');
  const alertEl = document.getElementById('manualAlert');
  const mejaEl = document.getElementById('id_meja');
  const jamEl = document.getElementById('jam_ditagihkan');
  const jumlahBayarEl = document.getElementById('jumlah_bayar');
  const metodeEl = document.getElementById('metode_pembayaran');
  const buktiEl = document.getElementById('bukti');
  const ITEMS_PER_PAGE = 10;
  const manualItemsTable = document.getElementById('manualAdditionalItemsTable');
  const manualItemsPager = document.getElementById('manualAdditionalItemsPager');
  const quickAddItemModalEl = document.getElementById('quickAddItemModal');
  const quickAddItemModal = quickAddItemModalEl ? new bootstrap.Modal(quickAddItemModalEl) : null;
  const quickItemNamaEl = document.getElementById('quick_item_nama');
  const quickItemHargaEl = document.getElementById('quick_item_harga');
  const quickAddItemAlert = document.getElementById('quickAddItemAlert');

  function fmtRp(n) {
    const val = Number(n || 0);
    if (val < 0) {
      return '− Rp ' + Math.abs(val).toLocaleString('id-ID', { maximumFractionDigits: 0 });
    }
    return 'Rp ' + val.toLocaleString('id-ID', { maximumFractionDigits: 0 });
  }

  function additionalLineSubtotal(row, qty) {
    const harga = parseFloat(row?.getAttribute('data-item-harga') || '0');
    const isDiscount = row?.getAttribute('data-item-discount') === '1';
    const subtotal = harga * qty;
    return isDiscount ? -subtotal : subtotal;
  }

  function selectedMejaRates() {
    const opt = mejaEl?.selectedOptions[0];
    if (!opt || !opt.value) return { non: 0, member: 0 };
    return {
      non: parseFloat(opt.getAttribute('data-harga-non-member')) || 0,
      member: parseFloat(opt.getAttribute('data-harga-member')) || 0,
    };
  }

  function isMember() {
    return document.getElementById('manual_member')?.checked;
  }

  function currentRate() {
    const r = selectedMejaRates();
    return isMember() ? r.member : r.non;
  }

  function selectedPromo() {
    const sel = document.getElementById('manual_id_promo');
    if (!sel || !sel.value || sel.disabled) return null;
    const opt = sel.selectedOptions[0];
    if (!opt) return null;
    const limitRaw = opt.getAttribute('data-limit');
    const limit = limitRaw === '' || limitRaw === null ? 0 : parseFloat(limitRaw);
    return {
      rate: parseFloat(opt.getAttribute('data-rate') || '0'),
      limit: Number.isNaN(limit) ? 0 : limit,
    };
  }

  function computeSewaPrice(billedHours, normalRate, promo) {
    billedHours = Math.max(0, billedHours);
    if (billedHours <= 0) return 0;
    if (!promo || !promo.rate) return billedHours * normalRate;
    if (!promo.limit || promo.limit <= 0) {
      return billedHours * promo.rate;
    }
    if (billedHours <= promo.limit) {
      return billedHours * promo.rate;
    }
    const promoPart = promo.limit * promo.rate;
    const normalPart = (billedHours - promo.limit) * normalRate;
    return promoPart + normalPart;
  }

  function syncPromoAvailability() {
    const sel = document.getElementById('manual_id_promo');
    if (!sel) return;
    const hours = parseInt(jamEl?.value, 10) || 0;
    const disabled = hours <= 0;
    sel.disabled = disabled;
    if (disabled) sel.value = '';
  }

  function syncPromoOptions() {
    const sel = document.getElementById('manual_id_promo');
    const hint = document.getElementById('manualPromoHint');
    if (!sel) return;
    syncPromoAvailability();
    const opt = mejaEl?.selectedOptions[0];
    const tokoId = parseInt(opt?.getAttribute('data-toko-id') || '0', 10) || defaultTokoId || 0;
    Array.from(sel.options).forEach(function (o, idx) {
      if (idx === 0) return;
      const optToko = parseInt(o.getAttribute('data-toko-id') || '0', 10) || 0;
      const show = !tokoId || optToko === tokoId;
      o.hidden = !show;
      if (!show && o.selected) sel.value = '';
    });
    const promo = selectedPromo();
    if (hint) {
      if (!promo) {
        hint.textContent = '';
      } else if (!promo.limit || promo.limit <= 0) {
        hint.textContent = 'Promo ' + fmtRp(promo.rate) + '/jam tanpa batas durasi (hingga jam promo berakhir di checkout).';
      } else {
        hint.textContent = 'Promo ' + fmtRp(promo.rate) + '/jam untuk ' + promo.limit + ' jam, lalu tarif normal.';
      }
    }
  }

  function collectAdditional() {
    const items = [];
    document.querySelectorAll('.manual-additional-qty').forEach(function (inp) {
      const qty = parseInt(inp.value, 10) || 0;
      const row = inp.closest('tr');
      if (row && row.classList.contains('item-toko-hidden')) return;
      if (qty > 0) items.push({ id: parseInt(inp.getAttribute('data-item-id'), 10), qty: qty });
    });
    return items;
  }

  function paginateManualItems(page) {
    if (!manualItemsTable) return;
    const allRows = Array.from(manualItemsTable.querySelectorAll('tbody tr[data-item-id]'));
    const eligible = allRows.filter(function (row) {
      return !row.classList.contains('item-toko-hidden');
    });
    const total = eligible.length;
    const totalPages = Math.max(1, Math.ceil(total / ITEMS_PER_PAGE));
    let current = page != null ? page : (parseInt(manualItemsTable.dataset.page || '1', 10) || 1);
    current = Math.min(Math.max(1, current), totalPages);
    manualItemsTable.dataset.page = String(current);

    allRows.forEach(function (row) {
      row.classList.add('item-page-hidden');
    });
    eligible.forEach(function (row, idx) {
      const onPage = idx >= (current - 1) * ITEMS_PER_PAGE && idx < current * ITEMS_PER_PAGE;
      row.classList.toggle('item-page-hidden', !onPage);
    });

    if (!manualItemsPager) return;
    if (total <= ITEMS_PER_PAGE) {
      manualItemsPager.classList.add('d-none');
      manualItemsPager.innerHTML = '';
      return;
    }

    manualItemsPager.classList.remove('d-none');
    manualItemsPager.innerHTML =
      '<div class="d-flex flex-wrap align-items-center justify-content-between gap-2">' +
        '<span class="small text-secondary">' +
          'Menampilkan ' + ((current - 1) * ITEMS_PER_PAGE + 1) + '–' + Math.min(current * ITEMS_PER_PAGE, total) +
          ' dari ' + total + ' item' +
        '</span>' +
        '<div class="btn-group btn-group-sm" role="group">' +
          '<button type="button" class="btn btn-outline-secondary item-page-prev"' + (current <= 1 ? ' disabled' : '') + '>Sebelumnya</button>' +
          '<button type="button" class="btn btn-outline-secondary disabled">' + current + ' / ' + totalPages + '</button>' +
          '<button type="button" class="btn btn-outline-secondary item-page-next"' + (current >= totalPages ? ' disabled' : '') + '>Berikutnya</button>' +
        '</div>' +
      '</div>';

    manualItemsPager.querySelector('.item-page-prev')?.addEventListener('click', function () {
      paginateManualItems(current - 1);
    });
    manualItemsPager.querySelector('.item-page-next')?.addEventListener('click', function () {
      paginateManualItems(current + 1);
    });
  }

  function syncAdditionalItemsByToko() {
    const canSeeAll = @json(\App\Support\TokoScope::canSeeAll());
    if (!canSeeAll) {
      paginateManualItems(1);
      return;
    }
    const opt = mejaEl?.selectedOptions[0];
    const tokoId = parseInt(opt?.getAttribute('data-toko-id') || '0', 10) || defaultTokoId || 0;

    document.querySelectorAll('#manualAdditionalItemsTable tr[data-item-id][data-item-toko]').forEach(function (row) {
      const itemToko = parseInt(row.getAttribute('data-item-toko') || '0', 10) || 0;
      const hide = tokoId ? itemToko !== tokoId : false;
      row.classList.toggle('item-toko-hidden', hide);
      if (hide) {
        row.querySelectorAll('input.manual-additional-qty').forEach(function (inp) {
          inp.value = '0';
        });
        const cell = row.querySelector('.manual-line-total');
        if (cell) cell.textContent = fmtRp(0);
      }
    });
    paginateManualItems(1);
  }

  function additionalTotal() {
    let positive = 0;
    let discount = 0;
    document.querySelectorAll('.manual-additional-qty').forEach(function (inp) {
      const row = inp.closest('tr');
      if (row && row.classList.contains('item-toko-hidden')) return;
      const qty = parseInt(inp.value, 10) || 0;
      const subtotal = additionalLineSubtotal(row, qty);
      const cell = row?.querySelector('.manual-line-total');
      if (cell) cell.textContent = fmtRp(subtotal);
      if (subtotal < 0) {
        discount += Math.abs(subtotal);
      } else {
        positive += subtotal;
      }
    });
    return { positive: positive, discount: discount, net: positive - discount };
  }

  function hasAdditionalItems() {
    return collectAdditional().length > 0;
  }

  function recalcTotals() {
    const hours = parseInt(jamEl?.value, 10) || 0;
    const rate = hours > 0 ? currentRate() : 0;
    const sewa = computeSewaPrice(hours, rate, selectedPromo());
    const add = additionalTotal();
    const grand = Math.max(0, sewa + add.net);

    document.getElementById('manualRateHint').textContent = hours > 0
      ? 'Tarif normal: ' + fmtRp(rate) + ' / jam (' + (isMember() ? 'Member' : 'Non-Member') + ')'
      : 'Tanpa sewa meja — isi item tambahan di bawah.';
    document.getElementById('previewSewa').textContent = fmtRp(sewa);
    document.getElementById('sumSewa').textContent = fmtRp(sewa);
    document.getElementById('sumAdditionalPositive').textContent = fmtRp(add.positive);
    const discountRow = document.getElementById('sumDiscountRow');
    const discountEl = document.getElementById('sumDiscount');
    if (discountRow && discountEl) {
      discountRow.classList.toggle('d-none', add.discount <= 0);
      discountEl.textContent = '− Rp ' + add.discount.toLocaleString('id-ID', { maximumFractionDigits: 0 });
    }
    const additionalRow = document.getElementById('sumAdditionalRow');
    if (additionalRow) {
      additionalRow.classList.toggle('d-none', add.positive <= 0 && add.discount > 0);
    }
    document.getElementById('sumGrand').textContent = fmtRp(grand);

    if (jumlahBayarEl && (jumlahBayarEl.dataset.auto !== '0' || jumlahBayarEl.value === '')) {
      jumlahBayarEl.value = String(Math.round(grand));
      jumlahBayarEl.dataset.auto = '1';
    }
  }

  function syncBukti() {
    const tunai = metodeEl?.value === 'tunai';
    document.getElementById('bukti_required')?.classList.toggle('d-none', tunai);
    const help = document.getElementById('bukti_help');
    if (help) {
      help.textContent = 'Opsional. Dapat dilengkapi nanti di menu Data Sewa.';
    }
  }

  mejaEl?.addEventListener('change', function () {
    syncAdditionalItemsByToko();
    syncPromoOptions();
    recalcTotals();
  });
  document.getElementById('manual_id_promo')?.addEventListener('change', function () {
    syncPromoOptions();
    recalcTotals();
  });
  jamEl?.addEventListener('input', function () {
    syncPromoOptions();
    recalcTotals();
  });
  document.querySelectorAll('input[name="tipe_customer"]').forEach(function (i) {
    i.addEventListener('change', recalcTotals);
  });
  document.getElementById('manualAdditionalItemsTable')?.addEventListener('input', function (e) {
    if (!e.target.closest('.manual-additional-qty')) return;
    recalcTotals();
  });
  metodeEl?.addEventListener('change', syncBukti);
  jumlahBayarEl?.addEventListener('input', function () { jumlahBayarEl.dataset.auto = '0'; });

  syncBukti();
  syncAdditionalItemsByToko();
  syncPromoOptions();
  recalcTotals();

  function currentTokoIdForQuickAdd() {
    if (!canSeeAllToko) return userIdToko || defaultTokoId || 0;
    const opt = mejaEl?.selectedOptions[0];
    return parseInt(opt?.getAttribute('data-toko-id') || '0', 10) || defaultTokoId || 0;
  }

  function escapeHtml(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function showQuickAddAlert(msg) {
    if (!quickAddItemAlert) return;
    quickAddItemAlert.textContent = msg || 'Terjadi kesalahan.';
    quickAddItemAlert.classList.remove('d-none');
  }

  function hideQuickAddAlert() {
    if (!quickAddItemAlert) return;
    quickAddItemAlert.classList.add('d-none');
    quickAddItemAlert.textContent = '';
  }

  function appendManualItem(item) {
    if (!manualItemsTable) return;
    const tbody = manualItemsTable.querySelector('tbody');
    if (!tbody) return;
    const harga = Number(item.harga) || 0;
    tbody.insertAdjacentHTML(
      'beforeend',
      '<tr data-item-id="' + item.id + '" data-item-harga="' + harga + '" data-item-discount="0" data-item-toko="' + (item.id_toko || 0) + '">' +
        '<td>' + escapeHtml(item.nama) + '</td>' +
        '<td class="text-end font-monospace small">' + fmtRp(harga) + '</td>' +
        '<td><input type="number" class="form-control form-control-sm manual-additional-qty" min="0" max="999" value="0" data-item-id="' + item.id + '" /></td>' +
        '<td class="text-end font-monospace small manual-line-total">Rp 0</td>' +
      '</tr>'
    );
    syncAdditionalItemsByToko();
    recalcTotals();
  }

  document.getElementById('manualTambahItemBtn')?.addEventListener('click', function () {
    hideQuickAddAlert();
    if (quickItemNamaEl) quickItemNamaEl.value = '';
    if (quickItemHargaEl) quickItemHargaEl.value = '';
    const tokoId = currentTokoIdForQuickAdd();
    if (!tokoId) {
      if (typeof AppToast !== 'undefined') {
        AppToast.show(canSeeAllToko
          ? 'Pilih meja terlebih dahulu agar toko item diketahui.'
          : 'Akun belum terhubung ke toko.', 'danger');
      } else {
        alert(canSeeAllToko ? 'Pilih meja terlebih dahulu.' : 'Akun belum terhubung ke toko.');
      }
      return;
    }
    quickAddItemModal?.show();
    setTimeout(function () { quickItemNamaEl?.focus(); }, 200);
  });

  document.getElementById('quickAddItemSaveBtn')?.addEventListener('click', function () {
    const nama = (quickItemNamaEl?.value || '').trim();
    const harga = parseFloat(quickItemHargaEl?.value ?? '');
    hideQuickAddAlert();
    if (!nama) {
      showQuickAddAlert('Nama wajib diisi.');
      quickItemNamaEl?.focus();
      return;
    }
    if (!Number.isFinite(harga) || harga < 0) {
      showQuickAddAlert('Harga wajib diisi (min. 0).');
      quickItemHargaEl?.focus();
      return;
    }
    const payload = { nama: nama, harga: harga };
    if (canSeeAllToko) {
      payload.id_toko = currentTokoIdForQuickAdd();
      if (!payload.id_toko) {
        showQuickAddAlert('Pilih meja terlebih dahulu agar toko item diketahui.');
        return;
      }
    }
    const btn = this;
    btn.disabled = true;
    fetch(quickAddUrl, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json', 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })
      .then(function (res) { return res.json().then(function (body) { return { ok: res.ok, status: res.status, body: body }; }); })
      .then(function (r) {
        btn.disabled = false;
        if (!r.ok) {
          const msg = r.status === 422 && r.body?.errors
            ? (Object.values(r.body.errors)[0]?.[0] || 'Validasi gagal.')
            : (r.body?.message || 'Gagal menambah item.');
          showQuickAddAlert(msg);
          return;
        }
        if (r.body?.item) appendManualItem(r.body.item);
        quickAddItemModal?.hide();
        if (typeof AppToast !== 'undefined') {
          AppToast.show(r.body?.message || 'Item ditambahkan.', 'success');
        }
      })
      .catch(function () {
        btn.disabled = false;
        showQuickAddAlert('Jaringan bermasalah.');
      });
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

  form?.addEventListener('submit', function (e) {
    e.preventDefault();
    alertEl?.classList.add('d-none');

    const metode = metodeEl?.value || '';
    const jumlahBayar = parseFloat(jumlahBayarEl?.value || '');
    const hasBukti = buktiEl?.files?.length > 0;

    const hours = parseInt(jamEl?.value, 10) || 0;
    const hasMeja = !!mejaEl?.value;
    const hasAdditional = hasAdditionalItems();

    if (hours > 0 && !hasMeja) {
      alertEl.textContent = 'Pilih meja jika ada jam ditagihkan.';
      alertEl.classList.remove('d-none');
      return;
    }
    if (hours <= 0 && !hasAdditional) {
      alertEl.textContent = 'Isi jam ditagihkan atau pilih minimal satu item tambahan.';
      alertEl.classList.remove('d-none');
      return;
    }
    if (!Number.isFinite(jumlahBayar) || jumlahBayar < 0) {
      alertEl.textContent = 'Jumlah bayar tidak valid.';
      alertEl.classList.remove('d-none');
      return;
    }
    if (!metode) {
      alertEl.textContent = 'Pilih metode pembayaran.';
      alertEl.classList.remove('d-none');
      return;
    }

    const btn = document.getElementById('manualSubmitBtn');

    function doSubmit() {
      btn.disabled = true;

      const fd = new FormData();
      fd.append('tanggal', document.getElementById('tanggal').value);
      if (mejaEl.value) fd.append('id_meja', mejaEl.value);
      fd.append('nama_customer', document.getElementById('nama_customer').value.trim());
      fd.append('tipe_customer', document.querySelector('input[name="tipe_customer"]:checked')?.value || 'non_member');
      fd.append('jam_ditagihkan', String(hours));
      const idPromo = document.getElementById('manual_id_promo')?.value;
      if (idPromo) fd.append('id_promo', idPromo);
      fd.append('additional_items', JSON.stringify(collectAdditional()));
      fd.append('metode_pembayaran', metode);
      fd.append('jumlah_bayar', String(jumlahBayar));
      if (hasBukti) fd.append('bukti', buktiEl.files[0]);

      fetch(storeUrl, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
        body: fd,
      })
        .then(function (res) { return res.json().then(function (body) { return { ok: res.ok, status: res.status, body: body }; }); })
        .then(function (r) {
          if (r.ok) {
            if (r.body?.invoice_url) window.open(r.body.invoice_url, '_blank', 'noopener,noreferrer');
            AppToast.saveForReload(r.body?.message || 'Tersimpan.');
            window.location.href = @json(route('rental.manual.index'));
            return;
          }
          btn.disabled = false;
          let msg = r.body?.message || 'Gagal menyimpan.';
          if (r.status === 422 && r.body?.errors) {
            const first = Object.values(r.body.errors)[0];
            msg = Array.isArray(first) ? first[0] : String(first);
          }
          alertEl.textContent = msg;
          alertEl.classList.remove('d-none');
          AppToast.show(msg, 'danger');
        })
        .catch(function () {
          btn.disabled = false;
          AppToast.show('Jaringan bermasalah.', 'danger');
        });
    }

    if (!hasBukti) {
      confirmProceedWithoutBukti(doSubmit);
      return;
    }

    doSubmit();
  });
})();
</script>
@endpush
