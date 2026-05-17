@extends('layouts.layout')

@section('content')
<div class="app-content-header">
  <div class="container-fluid">
    <div class="row">
      <div class="col-sm-6">
        <h3 class="mb-0">Sewa meja</h3>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-end">
          <li class="breadcrumb-item"><a href="{{ url('/') }}">Beranda</a></li>
          <li class="breadcrumb-item active" aria-current="page">Sewa</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<div class="app-content">
  <div class="container-fluid">
    <div class="card">
      <div class="card-header">
        <div class="row align-items-center">
          <div class="col-md-6">
            <h3 class="card-title mb-0">Semua meja per toko</h3>
          </div>
          <div class="col-md-6 text-end">
            <button
              type="button"
              class="btn btn-primary btn-sm"
              id="btn-open-checkin"
              data-bs-toggle="modal"
              data-bs-target="#checkinModal"
              {{ $mejasAvailable->isEmpty() ? 'disabled' : '' }}
            >
              <span aria-hidden="true">+</span> Mulai Sewa Baru
            </button>
          </div>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped table-hover align-middle mb-0" id="table-meja-status">
            <thead class="table-light">
              <tr>
                <th scope="col" style="width: 56px">No</th>
                <th scope="col">Nama toko</th>
                <th scope="col">Nama meja</th>
                <th scope="col" style="width: 120px">Status</th>
                <th scope="col">Nama customer</th>
                <th scope="col">Waktu mulai</th>
                <th scope="col" style="width: 110px">Durasi</th>
                <th scope="col" class="text-end" style="width: 120px">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @php
                $no = 0;
                $totalMeja = $tokos->sum(function ($t) {
                    return $t->meja->count();
                });
              @endphp
              @if ($totalMeja === 0)
                <tr>
                  <td colspan="8" class="text-center text-secondary py-4">
                    Belum ada meja. Tambah data toko &amp; meja di menu <strong>Toko</strong>.
                  </td>
                </tr>
              @else
                @foreach ($tokos as $toko)
                  @foreach ($toko->meja as $meja)
                    @php
                      $no++;
                      $rental = $meja->activeRental;
                      $st = $meja->status ?: 'active';
                    @endphp
                    <tr
                      @if ($rental)
                        data-rental-id="{{ $rental->id }}"
                        data-start-epoch="{{ $rental->waktu_start->timestamp }}"
                      @endif
                    >
                      <td>{{ $no }}</td>
                      <td>{{ $toko->nama }}</td>
                      <td><span class="fw-medium">{{ $meja->nama }}</span></td>
                      <td>
                        @if ($st === 'active')
                          <span class="badge text-bg-success">Aktif</span>
                        @elseif ($st === 'rented')
                          <span class="badge text-bg-warning text-dark">Disewa</span>
                        @else
                          <span class="badge text-bg-secondary">{{ $st }}</span>
                        @endif
                      </td>
                      <td>
                        @if ($rental)
                          {{ $rental->nama_customer }}
                        @else
                          <span class="text-secondary">—</span>
                        @endif
                      </td>
                      <td class="text-nowrap">
                        @if ($rental)
                          {{ $rental->waktu_start->format('d/m/Y H:i:s') }}
                        @else
                          <span class="text-secondary">—</span>
                        @endif
                      </td>
                      <td class="font-monospace rental-duration" data-role="duration">
                        @if ($rental)
                          00:00:00
                        @else
                          <span class="text-secondary">—</span>
                        @endif
                      </td>
                      <td class="text-end">
                        @if ($rental)
                          <button type="button" class="btn btn-success btn-sm btn-checkout" data-rental-id="{{ $rental->id }}">
                            Selesai
                          </button>
                        @else
                          <span class="text-secondary small">—</span>
                        @endif
                      </td>
                    </tr>
                  @endforeach
                @endforeach
              @endif
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Check-in --}}
<div class="modal fade" id="checkinModal" tabindex="-1" aria-labelledby="checkinModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="checkinModalLabel">Mulai sewa baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <form id="checkinForm" novalidate data-no-page-loader>
        <div class="modal-body">
          <div id="checkinAlert" class="alert alert-danger d-none" role="alert"></div>
          <div class="mb-3">
            <label for="checkin_id_meja" class="form-label">Meja</label>
            <select class="form-select" id="checkin_id_meja" name="id_meja" required>
              <option value="" disabled {{ $mejasAvailable->isEmpty() ? 'selected' : '' }}>— Pilih meja —</option>
              @foreach ($mejasAvailable as $m)
                <option value="{{ $m->id }}">
                  {{ $m->toko->nama ?? 'Toko' }} — {{ $m->nama }} (Rp {{ number_format((float) $m->harga, 0, ',', '.') }}/jam)
                </option>
              @endforeach
            </select>
            <div class="form-text">Hanya meja berstatus <strong>aktif</strong> (siap disewa).</div>
          </div>
          <div class="mb-0">
            <label for="checkin_nama_customer" class="form-label">Nama customer</label>
            <input type="text" class="form-control" id="checkin_nama_customer" name="nama_customer" required maxlength="255" autocomplete="name" />
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary" id="checkinSubmit">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Checkout: konfirmasi + rincian --}}
<div class="modal fade" id="checkoutModal" tabindex="-1" aria-labelledby="checkoutModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="checkoutModalLabel">Selesaikan sewa</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <p class="mb-2">Ringkasan perhitungan saat ini:</p>
        <div id="checkoutSummary" class="border rounded p-3 bg-body-secondary small"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="checkoutConfirmBtn" disabled>Konfirmasi selesai</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  const routes = {
    store: @json(route('rental.store')),
    checkoutPreview: (id) => @json(url('/sewa')) + '/' + id + '/checkout-preview',
    checkout: (id) => @json(url('/sewa')) + '/' + id + '/checkout',
  };

  function pad2(n) {
    return String(n).padStart(2, '0');
  }

  /** Elapsed seconds → HH:mm:ss (jam tidak dibatasi 24) */
  function formatHMS(totalSeconds) {
    const s = Math.max(0, Math.floor(totalSeconds));
    const h = Math.floor(s / 3600);
    const m = Math.floor((s % 3600) / 60);
    const sec = s % 60;
    return pad2(h) + ':' + pad2(m) + ':' + pad2(sec);
  }

  function tickDurations() {
    const nowSec = Math.floor(Date.now() / 1000);
    document.querySelectorAll('#table-meja-status tbody tr[data-start-epoch]').forEach(function (tr) {
      const start = parseInt(tr.getAttribute('data-start-epoch'), 10);
      const cell = tr.querySelector('.rental-duration');
      if (!cell || Number.isNaN(start)) return;
      cell.textContent = formatHMS(nowSec - start);
    });
  }

  tickDurations();
  setInterval(tickDurations, 1000);

  const checkinForm = document.getElementById('checkinForm');
  const checkinAlert = document.getElementById('checkinAlert');
  const checkoutModalEl = document.getElementById('checkoutModal');
  const checkoutModal = checkoutModalEl ? new bootstrap.Modal(checkoutModalEl) : null;
  const checkoutSummary = document.getElementById('checkoutSummary');
  const checkoutConfirmBtn = document.getElementById('checkoutConfirmBtn');
  let checkoutRentalId = null;

  function showCheckinError(msg) {
    if (!checkinAlert) return;
    checkinAlert.textContent = msg || 'Terjadi kesalahan.';
    checkinAlert.classList.remove('d-none');
  }

  function hideCheckinError() {
    if (!checkinAlert) return;
    checkinAlert.classList.add('d-none');
    checkinAlert.textContent = '';
  }

  if (checkinForm) {
    checkinForm.addEventListener('submit', function (e) {
      e.preventDefault();
      hideCheckinError();
      const fd = new FormData(checkinForm);
      const payload = {
        id_meja: fd.get('id_meja'),
        nama_customer: fd.get('nama_customer'),
      };

      fetch(routes.store, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrf,
          Accept: 'application/json',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload),
      })
        .then(function (res) {
          return res.json().then(function (body) {
            return { ok: res.ok, status: res.status, body: body };
          });
        })
        .then(function (r) {
          if (r.ok) {
            AppToast.saveForReload((r.body && r.body.message) ? r.body.message : 'Sewa dimulai.');
            window.location.reload();
            return;
          }
          if (r.status === 422 && r.body && r.body.errors) {
            const first = Object.values(r.body.errors)[0];
            const msg = Array.isArray(first) ? first[0] : String(first);
            showCheckinError(msg);
            return;
          }
          const errMsg = r.body && r.body.message ? r.body.message : 'Gagal menyimpan.';
          showCheckinError(errMsg);
          AppToast.show(errMsg, 'danger');
        })
        .catch(function () {
          showCheckinError('Jaringan bermasalah.');
          AppToast.show('Jaringan bermasalah.', 'danger');
        });
    });
  }

  document.querySelectorAll('.btn-checkout').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const id = this.getAttribute('data-rental-id');
      if (!id || !checkoutModal || !checkoutSummary || !checkoutConfirmBtn) return;
      checkoutRentalId = id;
      checkoutConfirmBtn.disabled = true;
      checkoutSummary.innerHTML = '<p class="mb-0 text-secondary">Memuat…</p>';
      checkoutModal.show();

      fetch(routes.checkoutPreview(id), {
        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
      })
        .then(function (res) {
          return res.json().then(function (body) {
            return { ok: res.ok, body: body };
          });
        })
        .then(function (r) {
          if (!r.ok) {
            checkoutSummary.innerHTML = '<p class="text-danger mb-0">Gagal memuat rincian.</p>';
            AppToast.show('Gagal memuat rincian checkout.', 'danger');
            return;
          }
          const d = r.body;
          checkoutSummary.innerHTML =
            '<p class="mb-2"><strong>' +
            escapeHtml(d.nama_meja) +
            '</strong> · ' +
            escapeHtml(d.nama_customer) +
            '</p>' +
            '<p class="mb-2 text-secondary small">Mulai: ' +
            escapeHtml(d.waktu_start) +
            '</p>' +
            (d.breakdown_html || '');
          checkoutConfirmBtn.disabled = false;
        })
        .catch(function () {
          checkoutSummary.innerHTML = '<p class="text-danger mb-0">Jaringan bermasalah.</p>';
          AppToast.show('Jaringan bermasalah.', 'danger');
        });
    });
  });

  function escapeHtml(s) {
    if (!s) return '';
    const div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML;
  }

  if (checkoutConfirmBtn) {
    checkoutConfirmBtn.addEventListener('click', function () {
      if (!checkoutRentalId) return;
      checkoutConfirmBtn.disabled = true;
      fetch(routes.checkout(checkoutRentalId), {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrf,
          Accept: 'application/json',
        },
      })
        .then(function (res) {
          return res.json().then(function (body) {
            return { ok: res.ok, body: body };
          });
        })
        .then(function (r) {
          if (r.ok) {
            if (checkoutModal) checkoutModal.hide();
            AppToast.saveForReload((r.body && r.body.message) ? r.body.message : 'Sewa selesai.');
            window.location.reload();
            return;
          }
          checkoutConfirmBtn.disabled = false;
          const err = (r.body && r.body.message) || 'Checkout gagal.';
          AppToast.show(err, 'danger');
        })
        .catch(function () {
          checkoutConfirmBtn.disabled = false;
          AppToast.show('Jaringan bermasalah.', 'danger');
        });
    });
  }
})();
</script>
@endpush
