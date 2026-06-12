@extends('layouts.layout')

@section('content')
<div class="app-content-header">
  <div class="container-fluid">
    <div class="row">
      <div class="col-sm-6"><h3 class="mb-0">Promo Sewa Meja</h3></div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-end">
          <li class="breadcrumb-item"><a href="#">Data Master</a></li>
          <li class="breadcrumb-item active">Promo Sewa</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<div class="app-content">
  <div class="container-fluid">
    <div class="alert alert-info small">
      Atur <strong>tanggal berlaku</strong> (opsional) dan <strong>jam harian</strong>.
      Kosongkan tanggal = tanpa batas periode; isi salah satu atau keduanya untuk membatasi tanggal.
    </div>
    <div class="card">
      <div class="card-header">
        <div class="row">
          <div class="col-md-6">
            <h3 class="card-title mb-0">Daftar Promo</h3>
          </div>
          <div class="col-md-6 text-end">
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#promoModal" id="btnAddPromo">+ Tambah Promo</button>
          </div>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped table-hover mb-0">
            <thead class="table-light">
              <tr>
                @if ($canAssignAnyToko)
                  <th>Toko</th>
                @endif
                <th>Nama</th>
                <th class="text-end">Tarif promo / jam</th>
                <th class="text-end">Batas jam promo</th>
                <th>Periode</th>
                <th>Jam berlaku</th>
                <th>Status</th>
                <th class="text-end" style="width:120px">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($promos as $promo)
                <tr>
                  @if ($canAssignAnyToko)
                    <td class="small">{{ $promo->toko->nama ?? ('Toko #'.$promo->id_toko) }}</td>
                  @endif
                  <td>{{ $promo->nama }}</td>
                  <td class="text-end font-monospace">Rp {{ number_format((float) $promo->promo_hourly_rate, 0, ',', '.') }}</td>
                  <td class="text-end">{{ number_format((float) $promo->promo_duration_limit, 2, ',', '.') }} jam</td>
                  <td class="small">{{ $promo->periodeFormatted() }}</td>
                  <td class="small font-monospace">{{ $promo->jamMulaiFormatted() }} – {{ $promo->jamSelesaiFormatted() }}</td>
                  <td>
                    @if ($promo->is_active)
                      <span class="badge text-bg-success">Aktif</span>
                    @else
                      <span class="badge text-bg-secondary">Nonaktif</span>
                    @endif
                  </td>
                  <td class="text-end">
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-edit-promo" data-bs-toggle="modal" data-bs-target="#promoModal"
                      data-id="{{ $promo->id }}"
                      data-nama="{{ $promo->nama }}"
                      data-rate="{{ $promo->promo_hourly_rate }}"
                      data-limit="{{ $promo->promo_duration_limit }}"
                      data-jam-mulai="{{ $promo->jamMulaiFormatted() }}"
                      data-jam-selesai="{{ $promo->jamSelesaiFormatted() }}"
                      data-tgl-awal="{{ $promo->tgl_awal ? $promo->tgl_awal->format('Y-m-d') : '' }}"
                      data-tgl-akhir="{{ $promo->tgl_akhir ? $promo->tgl_akhir->format('Y-m-d') : '' }}"
                      data-active="{{ $promo->is_active ? '1' : '0' }}"
                      data-id-toko="{{ $promo->id_toko }}">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-promo" data-id="{{ $promo->id }}" data-nama="{{ $promo->nama }}">
                      <i class="bi bi-trash"></i>
                    </button>
                  </td>
                </tr>
              @empty
                <tr><td colspan="{{ $canAssignAnyToko ? 8 : 7 }}" class="text-center text-secondary py-4">Belum ada promo.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="promoModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="promoModalTitle">Tambah Promo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="promoForm" data-no-page-loader>
        <div class="modal-body">
          <div id="promoAlert" class="alert alert-danger d-none"></div>
          <input type="hidden" id="promo_id" />
          @if ($canAssignAnyToko)
            <div class="mb-3">
              <label class="form-label" for="promo_id_toko">Toko <span class="text-danger">*</span></label>
              <select class="form-select" id="promo_id_toko" required>
                <option value="">— Pilih toko —</option>
                @foreach ($tokos as $toko)
                  <option value="{{ $toko->id }}">{{ $toko->nama }}</option>
                @endforeach
              </select>
            </div>
          @else
            <input type="hidden" id="promo_id_toko" value="{{ auth()->user()->id_toko }}" />
          @endif
          <div class="mb-3">
            <label class="form-label" for="promo_nama">Nama promo</label>
            <input type="text" class="form-control" id="promo_nama" required maxlength="255" placeholder="Happy Hour" />
          </div>
          <div class="mb-3">
            <label class="form-label" for="promo_hourly_rate">Tarif promo / jam (Rp)</label>
            <input type="number" class="form-control" id="promo_hourly_rate" min="0" step="1" required />
          </div>
          <div class="mb-3">
            <label class="form-label" for="promo_duration_limit">Batas durasi promo (jam)</label>
            <input type="number" class="form-control" id="promo_duration_limit" min="0.01" max="999" step="0.01" required />
            <div class="form-text">Maks. jam ditagihkan dengan tarif promo per sesi sewa.</div>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label" for="promo_tgl_awal">Tanggal mulai</label>
              <input type="date" class="form-control" id="promo_tgl_awal" />
              <div class="form-text">Opsional. Kosong = tidak ada batas awal.</div>
            </div>
            <div class="col-6">
              <label class="form-label" for="promo_tgl_akhir">Tanggal akhir</label>
              <input type="date" class="form-control" id="promo_tgl_akhir" />
              <div class="form-text">Opsional. Kosong = tidak ada batas akhir.</div>
            </div>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label" for="promo_jam_mulai">Jam mulai <span class="text-danger">*</span></label>
              <input type="time" class="form-control" id="promo_jam_mulai" required value="12:00" />
            </div>
            <div class="col-6">
              <label class="form-label" for="promo_jam_selesai">Jam selesai <span class="text-danger">*</span></label>
              <input type="time" class="form-control" id="promo_jam_selesai" required value="15:00" />
            </div>
          </div>
          <div class="form-text mb-3">Contoh: 12:00–15:00 = Happy Hour. Untuk melewati tengah malam, isi mis. 22:00–02:00.</div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="promo_active" checked />
            <label class="form-check-label" for="promo_active">Aktif</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
  const routes = {
    store: @json(route('rental-promos.store')),
    update: (id) => @json(url('/rental-promos')) + '/' + id,
    destroy: (id) => @json(url('/rental-promos')) + '/' + id,
  };
  const canAssignAnyToko = @json($canAssignAnyToko);
  const idTokoSelect = document.getElementById('promo_id_toko');
  const idTokoHidden = document.getElementById('promo_id_toko');
  const form = document.getElementById('promoForm');

  function resetForm() {
    document.getElementById('promo_id').value = '';
    document.getElementById('promo_nama').value = '';
    document.getElementById('promo_hourly_rate').value = '';
    document.getElementById('promo_duration_limit').value = '';
    document.getElementById('promo_tgl_awal').value = '';
    document.getElementById('promo_tgl_akhir').value = '';
    document.getElementById('promo_jam_mulai').value = '12:00';
    document.getElementById('promo_jam_selesai').value = '15:00';
    document.getElementById('promo_active').checked = true;
    if (idTokoSelect) idTokoSelect.value = '';
    document.getElementById('promoModalTitle').textContent = 'Tambah Promo';
    document.getElementById('promoAlert').classList.add('d-none');
  }

  document.getElementById('btnAddPromo')?.addEventListener('click', resetForm);
  document.querySelectorAll('.btn-edit-promo').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.getElementById('promo_id').value = btn.dataset.id;
      document.getElementById('promo_nama').value = btn.dataset.nama;
      document.getElementById('promo_hourly_rate').value = btn.dataset.rate;
      document.getElementById('promo_duration_limit').value = btn.dataset.limit;
      document.getElementById('promo_tgl_awal').value = btn.dataset.tglAwal || '';
      document.getElementById('promo_tgl_akhir').value = btn.dataset.tglAkhir || '';
      document.getElementById('promo_jam_mulai').value = btn.dataset.jamMulai || '12:00';
      document.getElementById('promo_jam_selesai').value = btn.dataset.jamSelesai || '15:00';
      document.getElementById('promo_active').checked = btn.dataset.active === '1';
      if (idTokoSelect) idTokoSelect.value = btn.dataset.idToko || '';
      document.getElementById('promoModalTitle').textContent = 'Ubah Promo';
    });
  });

  form?.addEventListener('submit', function (e) {
    e.preventDefault();
    const id = document.getElementById('promo_id').value;
    const idTokoVal = idTokoSelect ? idTokoSelect.value : (idTokoHidden ? idTokoHidden.value : '');
    const payload = {
      nama: document.getElementById('promo_nama').value.trim(),
      promo_hourly_rate: parseFloat(document.getElementById('promo_hourly_rate').value) || 0,
      promo_duration_limit: parseFloat(document.getElementById('promo_duration_limit').value) || 0,
      tgl_awal: document.getElementById('promo_tgl_awal').value || null,
      tgl_akhir: document.getElementById('promo_tgl_akhir').value || null,
      jam_mulai: document.getElementById('promo_jam_mulai').value,
      jam_selesai: document.getElementById('promo_jam_selesai').value,
      is_active: document.getElementById('promo_active').checked,
    };
    if (canAssignAnyToko) {
      payload.id_toko = parseInt(idTokoVal, 10) || 0;
    }
    const url = id ? routes.update(id) : routes.store;
    const method = id ? 'PUT' : 'POST';
    fetch(url, {
      method: method,
      headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json', 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })
      .then(function (res) { return res.json().then(function (body) { return { ok: res.ok, body: body }; }); })
      .then(function (r) {
        if (r.ok) {
          AppToast.saveForReload(r.body?.message || 'Tersimpan.');
          window.location.reload();
        } else {
          AppToast.show(r.body?.message || 'Gagal.', 'danger');
        }
      });
  });

  document.querySelectorAll('.btn-delete-promo').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (!confirm('Hapus promo "' + btn.dataset.nama + '"?')) return;
      fetch(routes.destroy(btn.dataset.id), {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
      })
        .then(function (res) { return res.json().then(function (body) { return { ok: res.ok, body: body }; }); })
        .then(function (r) {
          if (r.ok) {
            AppToast.saveForReload(r.body?.message || 'Dihapus.');
            window.location.reload();
          }
        });
    });
  });
})();
</script>
@endpush
