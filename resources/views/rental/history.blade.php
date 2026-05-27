@extends('layouts.layout')

@section('content')
<div class="app-content-header">
  <div class="container-fluid">
    <div class="row">
      <div class="col-sm-6">
        <h3 class="mb-0">Data Sewa</h3>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-end">
          <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Beranda</a></li>
          <li class="breadcrumb-item active" aria-current="page">Data Sewa</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<div class="app-content">
  <div class="container-fluid">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title mb-0">Riwayat sewa</h3>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table id="table-rental-history" class="table table-striped table-hover w-100 align-middle">
            <thead class="table-light">
              <tr>
                <th>ID</th>
                <th>Mulai</th>
                <th>Pelanggan</th>
                <th>Meja / Toko</th>
                <th>Tipe</th>
                <th>Status</th>
                <th class="text-end">Total</th>
                <th>Metode</th>
                <th>Pembayaran</th>
                <th class="text-end" style="width: 130px">Aksi</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="rentalEditModal" tabindex="-1" aria-labelledby="rentalEditModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="rentalEditModalLabel">Edit data sewa</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div id="rentalEditAlert" class="alert alert-danger d-none" role="alert"></div>
        <input type="hidden" id="rental_edit_id" value="" />

        <dl class="row small mb-3">
          <dt class="col-sm-3 text-secondary">Meja</dt>
          <dd class="col-sm-9 mb-1" id="rental_edit_meja">—</dd>
          <dt class="col-sm-3 text-secondary">Status</dt>
          <dd class="col-sm-9 mb-1" id="rental_edit_status">—</dd>
        </dl>

        <div class="row g-3">
          <div class="col-md-6">
            <label for="rental_edit_nama_customer" class="form-label">Nama pelanggan <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="rental_edit_nama_customer" maxlength="255" required />
          </div>
          <div class="col-md-6">
            <label for="rental_edit_tipe_customer" class="form-label">Tipe pelanggan <span class="text-danger">*</span></label>
            <select class="form-select" id="rental_edit_tipe_customer" required>
              <option value="member">Member</option>
              <option value="non_member">Non-Member</option>
            </select>
          </div>

          <div class="col-md-6 rental-edit-completed-only">
            <label for="rental_edit_total_harga" class="form-label">Total tagihan</label>
            <div class="input-group">
              <span class="input-group-text">Rp</span>
              <input type="number" class="form-control" id="rental_edit_total_harga" min="0" step="1" />
            </div>
          </div>
          <div class="col-md-6 rental-edit-completed-only">
            <label for="rental_edit_jumlah_bayar" class="form-label">Jumlah bayar</label>
            <div class="input-group">
              <span class="input-group-text">Rp</span>
              <input type="number" class="form-control" id="rental_edit_jumlah_bayar" min="0" step="1" />
            </div>
          </div>
          <div class="col-md-6 rental-edit-completed-only">
            <label for="rental_edit_metode" class="form-label">Metode pembayaran</label>
            <select class="form-select" id="rental_edit_metode">
              <option value="">— Belum diisi —</option>
              <option value="tunai">Tunai</option>
              <option value="transfer">Transfer bank</option>
              <option value="qris">QRIS / e-wallet</option>
              <option value="kartu">Kartu debit/kredit</option>
              <option value="lainnya">Lainnya</option>
            </select>
          </div>
          <div class="col-md-6 rental-edit-completed-only">
            <label for="rental_edit_waktu_pembayaran" class="form-label">Waktu pembayaran</label>
            <input type="datetime-local" class="form-control" id="rental_edit_waktu_pembayaran" />
          </div>
        </div>

        <p class="small text-secondary mb-0 mt-3 rental-edit-active-note d-none">
          Sewa masih aktif — hanya nama dan tipe pelanggan yang dapat diubah.
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="rentalEditSaveBtn">Simpan</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" crossorigin="anonymous" />
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js" crossorigin="anonymous"></script>
<script>
(function () {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  const routes = {
    data: @json(route('rental.history.data')),
    show: (id) => @json(url('/sewa/riwayat')) + '/' + id,
    update: (id) => @json(url('/sewa/riwayat')) + '/' + id,
    destroy: (id) => @json(url('/sewa/riwayat')) + '/' + id,
  };

  const editModalEl = document.getElementById('rentalEditModal');
  const editModal = editModalEl ? new bootstrap.Modal(editModalEl) : null;
  const editAlert = document.getElementById('rentalEditAlert');
  const editIdEl = document.getElementById('rental_edit_id');
  const completedFields = document.querySelectorAll('.rental-edit-completed-only');
  const activeNote = document.querySelector('.rental-edit-active-note');
  let currentStatus = '';

  function hideEditAlert() {
    if (!editAlert) return;
    editAlert.classList.add('d-none');
    editAlert.textContent = '';
  }

  function showEditAlert(msg) {
    if (!editAlert) return;
    editAlert.textContent = msg || 'Terjadi kesalahan.';
    editAlert.classList.remove('d-none');
  }

  function firstValidationError(body) {
    if (!body || !body.errors) return null;
    const first = Object.values(body.errors)[0];
    return Array.isArray(first) ? first[0] : String(first);
  }

  function toggleCompletedFields(isCompleted) {
    completedFields.forEach(function (el) {
      el.classList.toggle('d-none', !isCompleted);
    });
    if (activeNote) {
      activeNote.classList.toggle('d-none', isCompleted);
    }
  }

  const table = $('#table-rental-history').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: routes.data,
      type: 'GET',
    },
    order: [[1, 'desc']],
    pageLength: 25,
    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
    language: {
      emptyTable: 'Belum ada data sewa.',
      zeroRecords: 'Tidak ada data yang cocok.',
      info: 'Menampilkan _START_–_END_ dari _TOTAL_ data',
      infoEmpty: 'Menampilkan 0 data',
      infoFiltered: '(disaring dari _MAX_ data)',
      lengthMenu: 'Tampilkan _MENU_ data',
      search: 'Cari:',
      paginate: { first: 'Awal', last: 'Akhir', next: '›', previous: '‹' },
      processing: 'Memuat…',
    },
    columns: [
      { data: 'id', width: '60px' },
      { data: 'waktu_start' },
      { data: 'nama_customer' },
      { data: 'meja_toko' },
      { data: 'tipe_customer' },
      { data: 'status_html', orderable: false, searchable: false },
      { data: 'total_harga', className: 'text-end font-monospace' },
      { data: 'metode_pembayaran' },
      { data: 'pembayaran_html', orderable: false, searchable: false },
      { data: 'actions', orderable: false, searchable: false, className: 'text-end' },
    ],
    columnDefs: [
      { targets: [5, 8, 9], render: function (data) { return data; } },
    ],
  });

  $('#table-rental-history').on('click', '.btn-rental-edit', function () {
    const id = this.getAttribute('data-id');
    if (!id) return;
    hideEditAlert();

    fetch(routes.show(id), { headers: { Accept: 'application/json' } })
      .then(function (res) { return res.json().then(function (body) { return { ok: res.ok, body: body }; }); })
      .then(function (r) {
        if (!r.ok) throw new Error((r.body && r.body.message) || 'Gagal memuat data.');
        const d = r.body;
        currentStatus = d.status || '';
        editIdEl.value = d.id;
        document.getElementById('rental_edit_meja').textContent = (d.nama_meja || '—') + (d.nama_toko ? ' · ' + d.nama_toko : '');
        document.getElementById('rental_edit_status').textContent = d.status === 'active' ? 'Aktif' : (d.status === 'completed' ? 'Selesai' : d.status);
        document.getElementById('rental_edit_nama_customer').value = d.nama_customer || '';
        document.getElementById('rental_edit_tipe_customer').value = d.tipe_customer || 'non_member';
        document.getElementById('rental_edit_total_harga').value = d.total_harga != null ? Math.round(d.total_harga) : '';
        document.getElementById('rental_edit_jumlah_bayar').value = d.jumlah_bayar != null ? Math.round(d.jumlah_bayar) : '';
        document.getElementById('rental_edit_metode').value = d.metode_pembayaran || '';
        document.getElementById('rental_edit_waktu_pembayaran').value = d.waktu_pembayaran || '';
        toggleCompletedFields(d.status === 'completed');
        if (editModal) editModal.show();
      })
      .catch(function (err) {
        if (typeof AppToast !== 'undefined') AppToast.show(err.message || 'Gagal memuat data.', 'danger');
      });
  });

  document.getElementById('rentalEditSaveBtn')?.addEventListener('click', function () {
    hideEditAlert();
    const id = editIdEl ? editIdEl.value : '';
    if (!id) return;

    const payload = {
      nama_customer: document.getElementById('rental_edit_nama_customer').value.trim(),
      tipe_customer: document.getElementById('rental_edit_tipe_customer').value,
    };

    if (currentStatus === 'completed') {
      payload.total_harga = parseFloat(document.getElementById('rental_edit_total_harga').value) || 0;
      payload.jumlah_bayar = parseFloat(document.getElementById('rental_edit_jumlah_bayar').value) || 0;
      payload.metode_pembayaran = document.getElementById('rental_edit_metode').value || null;
      const waktu = document.getElementById('rental_edit_waktu_pembayaran').value;
      if (waktu) payload.waktu_pembayaran = waktu;
    }

    if (!payload.nama_customer) {
      showEditAlert('Nama pelanggan wajib diisi.');
      return;
    }

    const btn = this;
    btn.disabled = true;

    fetch(routes.update(id), {
      method: 'PUT',
      headers: {
        'X-CSRF-TOKEN': csrf,
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(payload),
    })
      .then(function (res) { return res.json().then(function (body) { return { ok: res.ok, body: body }; }); })
      .then(function (r) {
        if (!r.ok) throw new Error(firstValidationError(r.body) || (r.body && r.body.message) || 'Gagal menyimpan.');
        if (editModal) editModal.hide();
        table.ajax.reload(null, false);
        if (typeof AppToast !== 'undefined') AppToast.show(r.body.message || 'Data tersimpan.', 'success');
      })
      .catch(function (err) {
        showEditAlert(err.message || 'Terjadi kesalahan.');
        if (typeof AppToast !== 'undefined') AppToast.show(err.message || 'Gagal menyimpan.', 'danger');
      })
      .finally(function () { btn.disabled = false; });
  });

  $('#table-rental-history').on('click', '.btn-rental-delete', function () {
    const id = this.getAttribute('data-id');
    const customer = this.getAttribute('data-customer') || '';
    if (!id) return;

    const doDelete = function () {
      fetch(routes.destroy(id), {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
      })
        .then(function (res) { return res.json().then(function (body) { return { ok: res.ok, body: body }; }); })
        .then(function (r) {
          if (!r.ok) throw new Error((r.body && r.body.message) || 'Gagal menghapus.');
          table.ajax.reload(null, false);
          if (typeof AppToast !== 'undefined') AppToast.show(r.body.message || 'Data dihapus.', 'success');
        })
        .catch(function (err) {
          if (typeof AppToast !== 'undefined') AppToast.show(err.message || 'Gagal menghapus.', 'danger');
        });
    };

    if (typeof Swal !== 'undefined') {
      Swal.fire({
        title: 'Hapus data sewa?',
        html: 'Data sewa <strong>' + customer.replace(/</g, '&lt;') + '</strong> akan dihapus permanen.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#dc3545',
      }).then(function (result) {
        if (result.isConfirmed) doDelete();
      });
    } else if (window.confirm('Hapus data sewa "' + customer + '"?')) {
      doDelete();
    }
  });
})();
</script>
@endpush
