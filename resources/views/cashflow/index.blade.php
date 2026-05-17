@extends('layouts.layout')

@section('content')
<div class="app-content-header">
  <div class="container-fluid">
    <div class="row">
      <div class="col-sm-6">
        <h3 class="mb-0">Arus kas</h3>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-end">
          <li class="breadcrumb-item"><a href="{{ url('/') }}">Beranda</a></li>
          <li class="breadcrumb-item active" aria-current="page">Arus kas</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<div class="app-content">
  <div class="container-fluid">
  <div class="callout callout-warning mb-4">
      <h5 class="fw-bold">Catatan!</h5>
      <p>
        Pendapatan dari sewa meja masuk otomatis setelah checkout. Klik <strong>Lengkapi</strong> untuk mengisi metode pembayaran dan bukti transaksi.
      </p>
    </div>
    
    <div class="card">
      <div class="card-header">
        <h3 class="card-title mb-0">Riwayat pemasukan</h3>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped table-hover align-middle mb-0" id="table-cash-flow">
            <thead class="table-light">
              <tr>
                <th scope="col">Tanggal</th>
                <th scope="col">Jenis</th>
                <th scope="col">Keterangan</th>
                <th scope="col" class="text-end">Jumlah</th>
                <th scope="col" style="width: 130px">Status</th>
                <th scope="col" class="text-end" style="width: 110px">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($entries as $row)
                @php
                  $status = $row->kelengkapanStatus();
                @endphp
                <tr
                  data-cash-flow-id="{{ $row->id }}"
                  data-keterangan="{{ e($row->keterangan) }}"
                  data-tanggal="{{ $row->waktu_pembayaran->format('d/m/Y H:i') }}"
                  data-total="{{ number_format((float) $row->total, 0, ',', '.') }}"
                  data-metode="{{ $row->metode_pembayaran ?? '' }}"
                  data-bukti-url="{{ $row->buktiUrl() ?? '' }}"
                  data-status="{{ $status }}"
                >
                  <td class="text-nowrap col-tanggal">{{ $row->waktu_pembayaran->format('d/m/Y H:i') }}</td>
                  <td class="col-jenis">
                    @if ($row->tipe_transaksi == 'income')
                      <span class="badge text-bg-success">Pemasukan</span>
                    @else
                      <span class="badge text-bg-danger">Pengeluaran</span>
                    @endif
                  </td>
                  <td class="text-break col-keterangan">{{ $row->keterangan ?: '-' }}</td>
                  <td class="text-end font-monospace col-jumlah">
                    @if ($row->tipe_transaksi == 'income')
                      <span class="text-success">+ Rp {{ number_format((float) $row->total, 0, ',', '.') }}</span>
                    @else
                      <span class="text-danger">− Rp {{ number_format((float) $row->total, 0, ',', '.') }}</span>
                    @endif
                  </td>
                  <td class="col-status">
                    @if ($row->tipe_transaksi == 'income')
                      @if ($status === 'lengkap')
                        <span class="badge text-bg-success status-badge">Lengkap</span>
                      @elseif ($status === 'sebagian')
                        <span class="badge text-bg-info text-dark status-badge">Sebagian</span>
                      @else
                        <span class="badge text-bg-warning text-dark status-badge">Belum lengkap</span>
                      @endif
                    @else
                      <span class="text-secondary">—</span>
                    @endif
                  </td>
                  <td class="text-end col-aksi">
                    @if ($row->tipe_transaksi == 'income')
                      <div class="btn-group" role="group" aria-label="Aksi arus kas">
                        <button
                          type="button"
                          class="btn btn-outline-primary btn-sm btn-open-kelengkapan"
                          data-bs-toggle="modal"
                          data-bs-target="#kelengkapanModal"
                          title="Lengkapi pembayaran"
                        >
                          <i class="bi bi-pencil-square"></i>
                        </button>
                        @if ($status === 'lengkap')
                          <a
                            href="{{ route('cashflow.invoice', $row) }}"
                            class="btn btn-outline-secondary btn-sm"
                            target="_blank"
                            rel="noopener noreferrer"
                            data-no-page-loader
                            title="Cetak kuitansi"
                          >
                            <i class="bi bi-printer"></i>
                          </a>
                        @endif
                      </div>
                    @else
                      <span class="text-secondary">—</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center text-secondary py-4">Belum ada entri arus kas.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Modal kelengkapan pembayaran --}}
<div class="modal fade" id="kelengkapanModal" tabindex="-1" aria-labelledby="kelengkapanModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="kelengkapanModalLabel">Lengkapi pembayaran</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div id="kelengkapanAlert" class="alert alert-danger d-none" role="alert"></div>
        <input type="hidden" id="kelengkapan_id" value="" />

        <dl class="row small mb-3">
          <dt class="col-sm-4 text-secondary">Tanggal</dt>
          <dd class="col-sm-8 mb-1" id="kelengkapan_tanggal">—</dd>
          <dt class="col-sm-4 text-secondary">Keterangan</dt>
          <dd class="col-sm-8 mb-1" id="kelengkapan_keterangan">—</dd>
          <dt class="col-sm-4 text-secondary">Jumlah</dt>
          <dd class="col-sm-8 mb-0" id="kelengkapan_jumlah">—</dd>
        </dl>

        <hr class="my-3" />

        <div class="mb-3">
          <label for="kelengkapan_metode" class="form-label">Metode pembayaran</label>
          <select class="form-select" id="kelengkapan_metode" required>
            <option value="">— Pilih metode —</option>
            <option value="tunai">Tunai</option>
            <option value="transfer">Transfer bank</option>
            <option value="qris">QRIS / e-wallet</option>
            <option value="kartu">Kartu debit/kredit</option>
            <option value="lainnya">Lainnya</option>
          </select>
        </div>

        <div class="mb-0">
          <label for="kelengkapan_bukti" class="form-label">Bukti transaksi</label>
          <div id="kelengkapan_bukti_existing" class="mb-2"></div>
          <input
            type="file"
            class="form-control"
            id="kelengkapan_bukti"
            accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf"
          />
          <div class="form-text">JPG, PNG, WEBP, atau PDF. Maks. 5&nbsp;MB. Kosongkan jika tidak mengganti bukti.</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="kelengkapanSaveBtn">Simpan</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  const baseUrl = @json(url('/cashflow')) + '/';

  const modalEl = document.getElementById('kelengkapanModal');
  const modal = modalEl ? new bootstrap.Modal(modalEl) : null;
  const alertEl = document.getElementById('kelengkapanAlert');
  const idInput = document.getElementById('kelengkapan_id');
  const tanggalEl = document.getElementById('kelengkapan_tanggal');
  const keteranganEl = document.getElementById('kelengkapan_keterangan');
  const jumlahEl = document.getElementById('kelengkapan_jumlah');
  const metodeEl = document.getElementById('kelengkapan_metode');
  const buktiInput = document.getElementById('kelengkapan_bukti');
  const buktiExistingEl = document.getElementById('kelengkapan_bukti_existing');
  const saveBtn = document.getElementById('kelengkapanSaveBtn');

  let activeRow = null;

  function hideAlert() {
    if (!alertEl) return;
    alertEl.classList.add('d-none');
    alertEl.textContent = '';
  }

  function showAlert(msg) {
    if (!alertEl) return;
    alertEl.textContent = msg || 'Terjadi kesalahan.';
    alertEl.classList.remove('d-none');
  }

  function statusBadgeHtml(status, label) {
    if (status === 'lengkap') {
      return '<span class="badge text-bg-success status-badge">' + escapeHtml(label) + '</span>';
    }
    if (status === 'sebagian') {
      return '<span class="badge text-bg-info text-dark status-badge">' + escapeHtml(label) + '</span>';
    }
    return '<span class="badge text-bg-warning text-dark status-badge">' + escapeHtml(label) + '</span>';
  }

  function escapeHtml(s) {
    if (!s) return '';
    const div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML;
  }

  function setBuktiExisting(url) {
    if (!buktiExistingEl) return;
    if (!url) {
      buktiExistingEl.innerHTML = '<p class="small text-secondary mb-0">Belum ada bukti diunggah.</p>';
      return;
    }
    buktiExistingEl.innerHTML =
      '<a href="' + 
      url.replace(/"/g, '&quot;') +
      '" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener noreferrer">' +
      '<i class="bi bi-file-earmark-arrow-up me-1"></i>Lihat bukti saat ini</a>';
  }

  function updateRowFromResponse(tr, body) {
    if (!tr || !body) return;
    if (body.metode_pembayaran !== undefined) {
      tr.setAttribute('data-metode', body.metode_pembayaran || '');
    }
    if (body.bukti_url !== undefined) {
      tr.setAttribute('data-bukti-url', body.bukti_url || '');
    }
    if (body.status && body.status_label) {
      tr.setAttribute('data-status', body.status);
      const statusCell = tr.querySelector('.col-status');
      if (statusCell) {
        statusCell.innerHTML = statusBadgeHtml(body.status, body.status_label);
      }
    }
  }

  document.querySelectorAll('#table-cash-flow .btn-open-kelengkapan').forEach(function (btn) {
    btn.addEventListener('click', function () {
      activeRow = btn.closest('tr');
      if (!activeRow) return;
      hideAlert();

      const id = activeRow.getAttribute('data-cash-flow-id');
      const keterangan = activeRow.getAttribute('data-keterangan') || '—';
      const tanggal = activeRow.getAttribute('data-tanggal') || '—';
      const total = activeRow.getAttribute('data-total') || '0';
      const metode = activeRow.getAttribute('data-metode') || '';
      const buktiUrl = activeRow.getAttribute('data-bukti-url') || '';

      if (idInput) idInput.value = id;
      if (tanggalEl) tanggalEl.textContent = tanggal;
      if (keteranganEl) keteranganEl.textContent = keterangan;
      if (jumlahEl) jumlahEl.textContent = 'Rp ' + total;
      if (metodeEl) metodeEl.value = metode;
      if (buktiInput) buktiInput.value = '';
      setBuktiExisting(buktiUrl);
    });
  });

  if (modalEl) {
    modalEl.addEventListener('hidden.bs.modal', function () {
      activeRow = null;
      hideAlert();
      if (buktiInput) buktiInput.value = '';
    });
  }

  function firstValidationError(body) {
    if (!body || !body.errors) return null;
    const first = Object.values(body.errors)[0];
    return Array.isArray(first) ? first[0] : String(first);
  }

  if (saveBtn) {
    saveBtn.addEventListener('click', function () {
      hideAlert();
      const id = idInput ? idInput.value : '';
      const metode = metodeEl ? metodeEl.value : '';
      const hasFile = buktiInput && buktiInput.files && buktiInput.files.length > 0;

      if (!id) return;
      if (!metode) {
        showAlert('Pilih metode pembayaran.');
        if (typeof AppToast !== 'undefined') {
          AppToast.show('Pilih metode pembayaran.', 'warning');
        }
        return;
      }

      saveBtn.disabled = true;
      let lastBody = null;

      fetch(baseUrl + id + '/metode-pembayaran', {
        method: 'PATCH',
        headers: {
          'X-CSRF-TOKEN': csrf,
          Accept: 'application/json',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ metode_pembayaran: metode }),
      })
        .then(function (res) {
          return res.json().then(function (body) {
            return { ok: res.ok, status: res.status, body: body };
          });
        })
        .then(function (r) {
          if (!r.ok) {
            throw new Error(firstValidationError(r.body) || (r.body && r.body.message) || 'Gagal menyimpan metode.');
          }
          lastBody = r.body;
          if (!hasFile) {
            return null;
          }
          const fd = new FormData();
          fd.append('bukti', buktiInput.files[0]);
          return fetch(baseUrl + id + '/bukti', {
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': csrf,
              Accept: 'application/json',
            },
            body: fd,
          }).then(function (res) {
            return res.json().then(function (body) {
              return { ok: res.ok, status: res.status, body: body };
            });
          });
        })
        .then(function (r) {
          if (r === null) {
            return;
          }
          if (!r.ok) {
            throw new Error(firstValidationError(r.body) || (r.body && r.body.message) || 'Gagal mengunggah bukti.');
          }
          lastBody = Object.assign({}, lastBody, r.body);
        })
        .then(function () {
          if (activeRow && lastBody) {
            updateRowFromResponse(activeRow, lastBody);
          }
          if (modal) modal.hide();
          var msg = lastBody && lastBody.message ? lastBody.message : 'Data pembayaran tersimpan.';
          if (typeof AppToast !== 'undefined') {
            AppToast.show(msg, 'success');
          }
        })
        .catch(function (err) {
          var errMsg = err.message || 'Terjadi kesalahan.';
          showAlert(errMsg);
          if (typeof AppToast !== 'undefined') {
            AppToast.show(errMsg, 'danger');
          }
        })
        .finally(function () {
          saveBtn.disabled = false;
        });
    });
  }
})();
</script>
@endpush
