@extends('layouts.layout')

@section('content')
@php
  $fmtRp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
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
        Untuk transaksi yang sudah selesai tanpa timer kasir. Isi <strong>jam ditagihkan</strong> dan tanggal transaksi — tidak perlu waktu mulai/selesai.
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
              <label for="id_meja" class="form-label">Meja <span class="text-danger">*</span></label>
              <select class="form-select" id="id_meja" name="id_meja" required>
                <option value="">— Pilih meja —</option>
                @foreach ($mejas as $m)
                  <option
                    value="{{ $m->id }}"
                    data-harga-non-member="{{ (float) $m->harga }}"
                    data-harga-member="{{ (float) ($m->harga_member ?? $m->harga) }}"
                    data-toko="{{ $m->toko->nama ?? '' }}"
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
              <label for="jam_ditagihkan" class="form-label">Jam ditagihkan <span class="text-danger">*</span></label>
              <input type="number" class="form-control" id="jam_ditagihkan" name="jam_ditagihkan" min="1" max="999" value="1" required />
              <div class="form-text">Jumlah jam yang ditagihkan ke customer.</div>
            </div>
            <div class="col-md-4 d-flex align-items-end">
              <div class="border rounded p-3 bg-light w-100">
                <div class="small text-secondary">Perkiraan biaya sewa</div>
                <div class="fs-5 fw-semibold font-monospace" id="previewSewa">Rp 0</div>
              </div>
            </div>
          </div>

          @if ($additionalItems->isNotEmpty())
            <hr class="my-4" />
            <div class="row">
              <div class="col-md-8">
                <h6 class="fw-semibold">Item tambahan (F&amp;B)</h6>
                <div class="table-responsive">
                  <table class="table table-sm align-middle">
                    <thead class="table-light">
                      <tr>
                        <th>Item</th>
                        <th class="text-end">Harga</th>
                        <th style="width:90px">Qty</th>
                        <th class="text-end">Subtotal</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach ($additionalItems as $item)
                        <tr data-item-id="{{ $item->id }}" data-item-harga="{{ (float) $item->harga }}">
                          <td>{{ $item->nama }}</td>
                          <td class="text-end font-monospace small">{{ $fmtRp($item->harga) }}</td>
                          <td>
                            <input type="number" class="form-control form-control-sm manual-additional-qty" min="0" max="999" value="0" data-item-id="{{ $item->id }}" />
                          </td>
                          <td class="text-end font-monospace small manual-line-total">Rp 0</td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              </div>
              <div class="col-md-4">
                <h6 class="fw-semibold">Ringkasan &amp; pembayaran</h6>
                <div class="g-3 mb-3">
                  <div class="border rounded p-3">
                    <div class="d-flex justify-content-between small"><span>Sewa meja</span><span class="font-monospace" id="sumSewa">Rp 0</span></div>
                    <div class="d-flex justify-content-between small"><span>F&amp;B</span><span class="font-monospace" id="sumAdditional">Rp 0</span></div>
                    <hr class="my-2" />
                    <div class="d-flex justify-content-between fw-bold"><span>Total</span><span class="font-monospace text-primary" id="sumGrand">Rp 0</span></div>
                  </div>
                </div>
              </div>
            </div>
          @endif

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
@endsection

@push('scripts')
<script>
(function () {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
  const storeUrl = @json(route('rental.manual.store'));
  const form = document.getElementById('manualRentalForm');
  const alertEl = document.getElementById('manualAlert');
  const mejaEl = document.getElementById('id_meja');
  const jamEl = document.getElementById('jam_ditagihkan');
  const jumlahBayarEl = document.getElementById('jumlah_bayar');
  const metodeEl = document.getElementById('metode_pembayaran');
  const buktiEl = document.getElementById('bukti');

  function fmtRp(n) {
    return 'Rp ' + Number(n || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });
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

  function collectAdditional() {
    const items = [];
    document.querySelectorAll('.manual-additional-qty').forEach(function (inp) {
      const qty = parseInt(inp.value, 10) || 0;
      if (qty > 0) items.push({ id: parseInt(inp.getAttribute('data-item-id'), 10), qty: qty });
    });
    return items;
  }

  function additionalTotal() {
    let sum = 0;
    document.querySelectorAll('.manual-additional-qty').forEach(function (inp) {
      const row = inp.closest('tr');
      const harga = parseFloat(row?.getAttribute('data-item-harga') || '0');
      const qty = parseInt(inp.value, 10) || 0;
      sum += harga * qty;
      const cell = row?.querySelector('.manual-line-total');
      if (cell) cell.textContent = fmtRp(harga * qty);
    });
    return sum;
  }

  function recalcTotals() {
    const rate = currentRate();
    const hours = parseInt(jamEl?.value, 10) || 0;
    const sewa = Math.max(0, hours) * rate;
    const add = additionalTotal();
    const grand = sewa + add;

    document.getElementById('manualRateHint').textContent =
      'Tarif: ' + fmtRp(rate) + ' / jam (' + (isMember() ? 'Member' : 'Non-Member') + ')';
    document.getElementById('previewSewa').textContent = fmtRp(sewa);
    document.getElementById('sumSewa').textContent = fmtRp(sewa);
    document.getElementById('sumAdditional').textContent = fmtRp(add);
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
      help.textContent = tunai ? 'Opsional untuk tunai.' : 'Wajib untuk metode non-tunai.';
    }
  }

  mejaEl?.addEventListener('change', recalcTotals);
  jamEl?.addEventListener('input', recalcTotals);
  document.querySelectorAll('input[name="tipe_customer"]').forEach(function (i) {
    i.addEventListener('change', recalcTotals);
  });
  document.querySelectorAll('.manual-additional-qty').forEach(function (inp) {
    inp.addEventListener('input', recalcTotals);
  });
  metodeEl?.addEventListener('change', syncBukti);
  jumlahBayarEl?.addEventListener('input', function () { jumlahBayarEl.dataset.auto = '0'; });

  syncBukti();
  recalcTotals();

  form?.addEventListener('submit', function (e) {
    e.preventDefault();
    alertEl?.classList.add('d-none');

    const metode = metodeEl?.value || '';
    const jumlahBayar = parseFloat(jumlahBayarEl?.value || '');
    const hasBukti = buktiEl?.files?.length > 0;

    if (!mejaEl?.value) {
      alertEl.textContent = 'Pilih meja.';
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
    if (metode !== 'tunai' && !hasBukti) {
      alertEl.textContent = 'Bukti wajib untuk metode non-tunai.';
      alertEl.classList.remove('d-none');
      return;
    }

    const btn = document.getElementById('manualSubmitBtn');
    btn.disabled = true;

    const fd = new FormData();
    fd.append('tanggal', document.getElementById('tanggal').value);
    fd.append('id_meja', mejaEl.value);
    fd.append('nama_customer', document.getElementById('nama_customer').value.trim());
    fd.append('tipe_customer', document.querySelector('input[name="tipe_customer"]:checked')?.value || 'non_member');
    fd.append('jam_ditagihkan', jamEl.value);
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
  });
})();
</script>
@endpush
