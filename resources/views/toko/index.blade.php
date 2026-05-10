@extends('layouts.layout')

@section('content')
<!--begin::App Content Header-->
<div class="app-content-header">
  <div class="container-fluid">
    <div class="row">
      <div class="col-sm-6">
        <h3 class="mb-0">Toko</h3>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-end">
          <li class="breadcrumb-item"><a href="{{ url('/') }}">Beranda</a></li>
          <li class="breadcrumb-item active" aria-current="page">Toko</li>
        </ol>
      </div>
    </div>
  </div>
</div>
<!--end::App Content Header-->

<div class="app-content">
  <div class="container-fluid">
    <div class="card">
      <div class="card-header">
        <div class="row">
          <div class="col-md-6">
            <h3 class="card-title mb-0">Semua toko</h3>
          </div>
          <div class="col-md-6 text-end">
            <button type="button" class="btn btn-primary btn-sm" id="btn-add-toko" data-bs-toggle="modal" data-bs-target="#tokoModal">
              <i class="bi bi-shop me-1"></i> Tambah toko
            </button>
          </div>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th scope="col">Nama</th>
                <th scope="col">Alamat</th>
                <th scope="col">Jumlah meja</th>
                <th scope="col">Dibuat</th>
                <th scope="col" class="text-end" style="width: 140px">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($tokos as $toko)
                <tr data-toko-id="{{ $toko->id }}">
                  <td class="col-nama">{{ $toko->nama }}</td>
                  <td class="col-alamat text-break">{{ $toko->alamat }}</td>
                  <td class="col-jumlah-meja">{{ $toko->jumlah_meja }}</td>
                  <td class="text-secondary">{{ $toko->doc }}</td>
                  <td class="text-end">
                    <button
                      type="button"
                      class="btn btn-outline-secondary btn-sm btn-edit-toko"
                      data-bs-toggle="modal"
                      data-bs-target="#tokoModal"
                      data-payload='@json($toko)'
                      title="Ubah"
                    >
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button
                      type="button"
                      class="btn btn-outline-danger btn-sm btn-delete-toko"
                      data-bs-toggle="modal"
                      data-bs-target="#deleteTokoModal"
                      data-toko-id="{{ $toko->id }}"
                      data-name="{{ $toko->nama }}"
                      title="Hapus"
                    >
                      <i class="bi bi-trash"></i>
                    </button>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center text-secondary py-4">Belum ada toko.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal tambah / ubah -->
<div class="modal fade" id="tokoModal" tabindex="-1" aria-labelledby="tokoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="tokoModalLabel">Tambah toko</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <form id="tokoForm" novalidate action="{{ route('toko.store') }}" method="POST">
        <div class="modal-body">
          <div id="tokoFormAlert" class="alert alert-danger d-none" role="alert"></div>
          <input type="hidden" id="toko_id" name="toko_id" value="" />
          <div class="mb-3">
            <label for="toko_nama" class="form-label">Nama</label>
            <input type="text" class="form-control" id="toko_nama" name="nama" required autocomplete="organization" />
          </div>
          <div class="mb-3">
            <label for="toko_alamat" class="form-label">Alamat</label>
            <textarea class="form-control" id="toko_alamat" name="alamat" rows="3"></textarea>
          </div>
          <div class="mb-2">
            <label for="toko_jumlah_meja" class="form-label">Jumlah meja</label>
            <input type="number" class="form-control" id="toko_jumlah_meja" name="jumlah_meja" min="0" step="1" required />
            <div class="form-text">Setelah mengubah angka, klik di luar kolom atau tekan Tab untuk memuat baris nama &amp; harga per meja.</div>
          </div>
          <div class="mb-0">
            <label class="form-label">Detail meja</label>
            <div id="mejaInputsContainer" class="small"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary" id="tokoFormSubmit">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Konfirmasi hapus -->
<div class="modal fade" id="deleteTokoModal" tabindex="-1" aria-labelledby="deleteTokoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteTokoModalLabel">Hapus toko</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">Hapus <strong id="deleteTokoName"></strong>? Tindakan ini tidak dapat dibatalkan.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteToko">Hapus</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  const TOAST_STORAGE_KEY = 'toko.index.toast';
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  const routes = {
    store: @json(route('toko.store')),
    update: (id) => @json(url('/toko')) + '/' + id,
    destroy: (id) => @json(url('/toko')) + '/' + id,
  };

  const tokoModalEl = document.getElementById('tokoModal');
  const tokoModal = tokoModalEl ? new bootstrap.Modal(tokoModalEl) : null;
  const deleteModalEl = document.getElementById('deleteTokoModal');
  const deleteModal = deleteModalEl ? new bootstrap.Modal(deleteModalEl) : null;

  const form = document.getElementById('tokoForm');
  const alertEl = document.getElementById('tokoFormAlert');
  const titleEl = document.getElementById('tokoModalLabel');
  const tokoIdInput = document.getElementById('toko_id');
  const deleteNameEl = document.getElementById('deleteTokoName');
  const confirmDeleteBtn = document.getElementById('confirmDeleteToko');
  const jumlahMejaInput = document.getElementById('toko_jumlah_meja');
  const mejaInputsContainer = document.getElementById('mejaInputsContainer');

  let deleteTargetId = null;

  function renderMejaRows(count, existing) {
    if (!mejaInputsContainer) return;
    mejaInputsContainer.innerHTML = '';
    const n = Math.max(0, parseInt(count, 10) || 0);
    const existingList = Array.isArray(existing) ? existing : [];
    for (let i = 0; i < n; i++) {
      const rowData = existingList[i] || {};
      const wrap = document.createElement('div');
      wrap.className = 'border rounded p-2 mb-2 meja-input-row';

      const title = document.createElement('div');
      title.className = 'small text-secondary mb-1';
      title.textContent = `Meja ${i + 1}`;
      wrap.appendChild(title);

      const row = document.createElement('div');
      row.className = 'row g-2';

      const colNama = document.createElement('div');
      colNama.className = 'col-md-6';
      const lblNama = document.createElement('label');
      lblNama.className = 'form-label small mb-0';
      lblNama.textContent = 'Nama';
      const inpNama = document.createElement('input');
      inpNama.type = 'text';
      inpNama.className = 'form-control form-control-sm meja-nama';
      inpNama.required = true;
      inpNama.autocomplete = 'off';
      if (rowData.nama != null) inpNama.value = String(rowData.nama);
      colNama.appendChild(lblNama);
      colNama.appendChild(inpNama);

      const colHarga = document.createElement('div');
      colHarga.className = 'col-md-6';
      const lblHarga = document.createElement('label');
      lblHarga.className = 'form-label small mb-0';
      lblHarga.textContent = 'Harga';
      const inpHarga = document.createElement('input');
      inpHarga.type = 'number';
      inpHarga.className = 'form-control form-control-sm meja-harga';
      inpHarga.min = '0';
      inpHarga.step = '0.01';
      inpHarga.required = true;
      if (rowData.harga != null && rowData.harga !== '') inpHarga.value = String(rowData.harga);
      colHarga.appendChild(lblHarga);
      colHarga.appendChild(inpHarga);

      row.appendChild(colNama);
      row.appendChild(colHarga);
      wrap.appendChild(row);
      mejaInputsContainer.appendChild(wrap);
    }
    if (n === 0) {
      const hint = document.createElement('p');
      hint.className = 'text-secondary mb-0 small';
      hint.textContent = 'Set jumlah meja di atas untuk mengisi nama dan harga tiap meja.';
      mejaInputsContainer.appendChild(hint);
    }
  }

  function collectMejaRows() {
    const wraps = mejaInputsContainer?.querySelectorAll('.meja-input-row') ?? [];
    const meja = [];
    wraps.forEach((wrap) => {
      const nama = wrap.querySelector('.meja-nama')?.value.trim() ?? '';
      const hargaRaw = wrap.querySelector('.meja-harga')?.value ?? '';
      const harga = hargaRaw === '' ? 0 : parseFloat(hargaRaw);
      meja.push({
        nama,
        harga: Number.isFinite(harga) ? harga : 0,
      });
    });
    return meja;
  }

  function getToastContainer() {
    let container = document.getElementById('toastContainer');
    if (container) return container;

    container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '1090';
    document.body.appendChild(container);
    return container;
  }

  function showToast(message, variant = 'success') {
    if (!message) return;
    const container = getToastContainer();
    const toneClass = variant === 'danger' ? 'text-bg-danger' : 'text-bg-success';
    const toastEl = document.createElement('div');
    toastEl.className = `toast align-items-center border-0 ${toneClass}`;
    toastEl.setAttribute('role', 'alert');
    toastEl.setAttribute('aria-live', 'assertive');
    toastEl.setAttribute('aria-atomic', 'true');
    toastEl.innerHTML = `
      <div class="d-flex">
        <div class="toast-body">${message}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Tutup"></button>
      </div>
    `;

    container.appendChild(toastEl);
    const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
    toast.show();
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove(), { once: true });
  }

  function saveToastForReload(message, variant = 'success') {
    sessionStorage.setItem(TOAST_STORAGE_KEY, JSON.stringify({ message, variant }));
  }

  function showSavedToast() {
    const raw = sessionStorage.getItem(TOAST_STORAGE_KEY);
    if (!raw) return;
    sessionStorage.removeItem(TOAST_STORAGE_KEY);
    try {
      const payload = JSON.parse(raw);
      showToast(payload.message, payload.variant);
    } catch (_) {
      // Ignore invalid JSON from stale storage.
    }
  }

  function clearAlert() {
    if (!alertEl) return;
    alertEl.classList.add('d-none');
    alertEl.textContent = '';
  }

  function showAlert(message) {
    if (!alertEl) return;
    alertEl.textContent = message;
    alertEl.classList.remove('d-none');
  }

  function showErrors(errors) {
    const lines = [];
    for (const key of Object.keys(errors)) {
      errors[key].forEach((msg) => lines.push(msg));
    }
    showAlert(lines.join(' '));
  }

  function resetTokoForm() {
    form.reset();
    tokoIdInput.value = '';
    clearAlert();
    renderMejaRows(0, null);
  }

  document.getElementById('btn-add-toko')?.addEventListener('click', () => {
    resetTokoForm();
    titleEl.textContent = 'Tambah toko';
    jumlahMejaInput.value = '0';
    renderMejaRows(0, null);
  });

  jumlahMejaInput?.addEventListener('change', () => {
    const n = parseInt(jumlahMejaInput.value, 10) || 0;
    renderMejaRows(n, null);
  });

  document.querySelectorAll('.btn-edit-toko').forEach((btn) => {
    btn.addEventListener('click', () => {
      resetTokoForm();
      titleEl.textContent = 'Ubah toko';
      try {
        const payload = JSON.parse(btn.getAttribute('data-payload') || '{}');
        tokoIdInput.value = payload.id != null ? String(payload.id) : '';
        document.getElementById('toko_nama').value = payload.nama || '';
        document.getElementById('toko_alamat').value = payload.alamat || '';
        const jm = payload.jumlah_meja != null ? parseInt(payload.jumlah_meja, 10) : 0;
        jumlahMejaInput.value = String(Number.isFinite(jm) ? jm : 0);
        const mejaList = Array.isArray(payload.meja) ? payload.meja : [];
        renderMejaRows(Number.isFinite(jm) ? jm : 0, mejaList);
      } catch (_) {
        showAlert('Data toko tidak valid.');
      }
    });
  });

  tokoModalEl?.addEventListener('hidden.bs.modal', () => {
    resetTokoForm();
    titleEl.textContent = 'Tambah toko';
  });

  document.querySelectorAll('.btn-delete-toko').forEach((btn) => {
    btn.addEventListener('click', () => {
      deleteTargetId = btn.dataset.tokoId || null;
      deleteNameEl.textContent = btn.dataset.name || '';
    });
  });

  confirmDeleteBtn?.addEventListener('click', async () => {
    if (!deleteTargetId || !csrf) return;
    confirmDeleteBtn.disabled = true;
    try {
      const res = await fetch(routes.destroy(deleteTargetId), {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': csrf,
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok) {
        showToast(data.message || 'Gagal menghapus toko.', 'danger');
        return;
      }
      deleteModal?.hide();
      saveToastForReload(data.message || 'Toko dihapus.');
      window.location.reload();
    } finally {
      confirmDeleteBtn.disabled = false;
    }
  });

  form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearAlert();

    const submitBtn = document.getElementById('tokoFormSubmit');

    const id = tokoIdInput.value;
    const isEdit = Boolean(id);
    const url = isEdit ? routes.update(id) : routes.store;
    const method = isEdit ? 'PUT' : 'POST';

    const jumlahRaw = document.getElementById('toko_jumlah_meja').value;
    const jumlahMeja = jumlahRaw === '' ? 0 : parseInt(jumlahRaw, 10);
    const jumlahOk = Number.isFinite(jumlahMeja) ? jumlahMeja : 0;

    const meja = collectMejaRows();
    if (meja.length !== jumlahOk) {
      renderMejaRows(jumlahOk, null);
      showAlert(
        jumlahOk === 0
          ? 'Jumlah meja 0 — tidak perlu baris meja.'
          : `Isi nama dan harga untuk ${jumlahOk} meja. Kolom detail telah diselaraskan dengan jumlah meja.`
      );
      return;
    }

    const body = {
      nama: document.getElementById('toko_nama').value.trim(),
      alamat: document.getElementById('toko_alamat').value.trim(),
      jumlah_meja: jumlahOk,
      meja,
    };

    submitBtn.disabled = true;
    try {
      const res = await fetch(url, {
        method,
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(body),
      });

      const data = await res.json().catch(() => ({}));

      if (res.status === 422 && data.errors) {
        showErrors(data.errors);
        return;
      }

      if (!res.ok) {
        showToast(data.message || 'Terjadi kesalahan.', 'danger');
        return;
      }

      tokoModal?.hide();
      saveToastForReload(data.message || (isEdit ? 'Toko diperbarui.' : 'Toko ditambahkan.'));
      window.location.reload();
    } finally {
      submitBtn.disabled = false;
    }
  });

  showSavedToast();
})();
</script>
@endpush
