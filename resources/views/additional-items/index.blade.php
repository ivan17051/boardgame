@extends('layouts.layout')

@section('content')
<div class="app-content-header">
  <div class="container-fluid">
    <div class="row">
      <div class="col-sm-6"><h3 class="mb-0">Item Tambahan (F&amp;B)</h3></div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-end">
          <li class="breadcrumb-item"><a href="#">Data Master</a></li>
          <li class="breadcrumb-item active">Item Tambahan</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<div class="app-content">
  <div class="container-fluid">
    <div class="card">
      <div class="card-header">
        <div class="row">
          <div class="col-md-6">
            <h3 class="card-title mb-0">Item Tambahan</h3>
          </div>
          <div class="col-md-6 text-end">
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#itemModal" id="btnAddItem">+ Tambah Item</button>
          </div>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>Nama</th>
                <th class="text-end">Harga</th>
                <th>Status</th>
                <th class="text-end" style="width:120px">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($items as $item)
                <tr>
                  <td>{{ $item->nama }}</td>
                  <td class="text-end font-monospace">Rp {{ number_format((float) $item->harga, 0, ',', '.') }}</td>
                  <td>
                    @if ($item->is_active)
                      <span class="badge text-bg-success">Aktif</span>
                    @else
                      <span class="badge text-bg-secondary">Nonaktif</span>
                    @endif
                  </td>
                  <td class="text-end">
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-edit-item" data-bs-toggle="modal" data-bs-target="#itemModal"
                      data-id="{{ $item->id }}" data-nama="{{ $item->nama }}" data-harga="{{ $item->harga }}" data-active="{{ $item->is_active ? '1' : '0' }}">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-item" data-id="{{ $item->id }}" data-nama="{{ $item->nama }}">
                      <i class="bi bi-trash"></i>
                    </button>
                  </td>
                </tr>
              @empty
                <tr><td colspan="4" class="text-center text-secondary py-4">Belum ada item.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="itemModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="itemModalTitle">Tambah Item Tambahan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="itemForm" data-no-page-loader>
        <div class="modal-body">
          <div id="itemAlert" class="alert alert-danger d-none"></div>
          <input type="hidden" id="item_id" />
          <div class="mb-3">
            <label class="form-label" for="item_nama">Nama</label>
            <input type="text" class="form-control" id="item_nama" required maxlength="255" />
          </div>
          <div class="mb-3">
            <label class="form-label" for="item_harga">Harga</label>
            <input type="number" class="form-control" id="item_harga" min="0" step="1" required />
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="item_active" checked />
            <label class="form-check-label" for="item_active">Aktif</label>
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
    store: @json(route('additional-items.store')),
    update: (id) => @json(url('/additional-items')) + '/' + id,
    destroy: (id) => @json(url('/additional-items')) + '/' + id,
  };
  const modal = document.getElementById('itemModal');
  const form = document.getElementById('itemForm');

  function resetForm() {
    document.getElementById('item_id').value = '';
    document.getElementById('item_nama').value = '';
    document.getElementById('item_harga').value = '';
    document.getElementById('item_active').checked = true;
    document.getElementById('itemModalTitle').textContent = 'Tambah item';
    document.getElementById('itemAlert').classList.add('d-none');
  }

  document.getElementById('btnAddItem')?.addEventListener('click', resetForm);
  document.querySelectorAll('.btn-edit-item').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.getElementById('item_id').value = btn.dataset.id;
      document.getElementById('item_nama').value = btn.dataset.nama;
      document.getElementById('item_harga').value = btn.dataset.harga;
      document.getElementById('item_active').checked = btn.dataset.active === '1';
      document.getElementById('itemModalTitle').textContent = 'Ubah item';
    });
  });

  form?.addEventListener('submit', function (e) {
    e.preventDefault();
    const id = document.getElementById('item_id').value;
    const payload = {
      nama: document.getElementById('item_nama').value.trim(),
      harga: parseFloat(document.getElementById('item_harga').value) || 0,
      is_active: document.getElementById('item_active').checked,
    };
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

  document.querySelectorAll('.btn-delete-item').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (!confirm('Hapus item "' + btn.dataset.nama + '"?')) return;
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
