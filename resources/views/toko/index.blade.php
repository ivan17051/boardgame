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
          <li class="breadcrumb-item"><a href="#">Data Master</a></li>
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
            <h3 class="card-title mb-0">Semua Toko</h3>
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
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="tokoModalLabel">Tambah toko</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <form id="tokoForm" novalidate action="{{ route('toko.store') }}" method="POST" data-no-page-loader>
        <div class="modal-body">
          <div id="tokoFormAlert" class="alert alert-danger d-none" role="alert"></div>
          <input type="hidden" id="toko_id" name="toko_id" value="" />

          <ul class="nav nav-pills nav-justified mb-3" id="tokoWizardNav" role="tablist">
            <li class="nav-item" role="presentation">
              <span class="nav-link active py-2" id="tokoWizardTab1" data-wizard-step="1" role="tab" aria-selected="true">
                <span class="badge bg-primary-subtle text-primary-emphasis me-1">1</span> Informasi toko
              </span>
            </li>
            <li class="nav-item" role="presentation">
              <span class="nav-link disabled py-2" id="tokoWizardTab2" data-wizard-step="2" role="tab" aria-selected="false" tabindex="-1">
                <span class="badge bg-secondary-subtle text-secondary-emphasis me-1">2</span> Detail meja
              </span>
            </li>
          </ul>

          <div id="tokoWizardStep1" class="toko-wizard-pane">
            <div class="mb-3">
              <label for="toko_nama" class="form-label">Nama</label>
              <input type="text" class="form-control" id="toko_nama" name="nama" required autocomplete="organization" />
            </div>
            <div class="mb-3">
              <label for="toko_alamat" class="form-label">Alamat</label>
              <textarea class="form-control" id="toko_alamat" name="alamat" rows="3"></textarea>
            </div>
            <div class="mb-0">
              <label for="toko_jumlah_meja" class="form-label">Jumlah meja</label>
              <input type="number" class="form-control" id="toko_jumlah_meja" name="jumlah_meja" min="0" step="1" required />
              <div class="form-text">Langkah berikutnya: isi nama, harga non-member, dan harga member untuk setiap meja.</div>
            </div>
          </div>

          <div id="tokoWizardStep2" class="toko-wizard-pane d-none">
            <div id="mejaWizardEmpty" class="text-center text-secondary py-4 d-none">
              <i class="bi bi-table fs-2 d-block mb-2 opacity-50"></i>
              <p class="mb-0 small">Toko ini tidak memiliki meja. Klik <strong>Simpan</strong> untuk menyelesaikan.</p>
            </div>
            <div id="mejaWizardPanel" class="d-none">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <span id="mejaWizardProgress" class="fw-semibold small"></span>
                <span class="badge text-bg-light border" id="mejaWizardBadge">1 / 1</span>
              </div>
              <div id="mejaInputsContainer"></div>
            </div>
          </div>
        </div>
        <div class="modal-footer justify-content-between">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary d-none" id="btnTokoWizardBack">Kembali</button>
            <button type="button" class="btn btn-primary" id="btnTokoWizardNext">Lanjut</button>
            <button type="submit" class="btn btn-primary d-none" id="tokoFormSubmit">Simpan</button>
          </div>
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
<style>
  #tokoWizardNav .nav-link {
    cursor: default;
    font-size: 0.875rem;
  }
  #tokoWizardNav .nav-link:not(.disabled) {
    cursor: pointer;
  }
  .meja-input-row.d-none {
    display: none !important;
  }
</style>
<script>
(function () {
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
  const wizardStep1 = document.getElementById('tokoWizardStep1');
  const wizardStep2 = document.getElementById('tokoWizardStep2');
  const wizardTab1 = document.getElementById('tokoWizardTab1');
  const wizardTab2 = document.getElementById('tokoWizardTab2');
  const mejaWizardEmpty = document.getElementById('mejaWizardEmpty');
  const mejaWizardPanel = document.getElementById('mejaWizardPanel');
  const mejaWizardProgress = document.getElementById('mejaWizardProgress');
  const mejaWizardBadge = document.getElementById('mejaWizardBadge');
  const btnWizardBack = document.getElementById('btnTokoWizardBack');
  const btnWizardNext = document.getElementById('btnTokoWizardNext');
  const submitBtn = document.getElementById('tokoFormSubmit');

  const MEJA_PER_PAGE = 3;

  let deleteTargetId = null;
  let wizardStep = 1;
  let mejaPage = 0;
  let mejaCount = 0;
  let pendingMejaData = null;

  function getMejaPageCount() {
    return mejaCount > 0 ? Math.ceil(mejaCount / MEJA_PER_PAGE) : 0;
  }

  function getMejaPageRange(page) {
    const start = page * MEJA_PER_PAGE;
    const end = Math.min(start + MEJA_PER_PAGE, mejaCount);
    return { start, end };
  }

  function getJumlahMeja() {
    const raw = jumlahMejaInput?.value ?? '';
    const n = raw === '' ? 0 : parseInt(raw, 10);
    return Number.isFinite(n) && n >= 0 ? n : 0;
  }

  function setWizardStep(step) {
    wizardStep = step;
    wizardStep1?.classList.toggle('d-none', step !== 1);
    wizardStep2?.classList.toggle('d-none', step !== 2);

    wizardTab1?.classList.toggle('active', step === 1);
    wizardTab1?.classList.toggle('disabled', step !== 1);
    wizardTab1?.setAttribute('aria-selected', step === 1 ? 'true' : 'false');

    wizardTab2?.classList.toggle('active', step === 2);
    wizardTab2?.classList.toggle('disabled', step !== 2);
    wizardTab2?.setAttribute('aria-selected', step === 2 ? 'true' : 'false');

    syncWizardButtons();
  }

  function showMejaPage(page) {
    mejaPage = page;
    const { start, end } = getMejaPageRange(page);
    const rows = mejaInputsContainer?.querySelectorAll('.meja-input-row') ?? [];
    rows.forEach((row, i) => {
      row.classList.toggle('d-none', i < start || i >= end);
    });

    const pageCount = getMejaPageCount();
    if (mejaWizardProgress) {
      if (mejaCount === 0) {
        mejaWizardProgress.textContent = '';
      } else if (start + 1 === end) {
        mejaWizardProgress.textContent = `Meja ${start + 1} dari ${mejaCount}`;
      } else {
        mejaWizardProgress.textContent = `Meja ${start + 1}–${end} dari ${mejaCount}`;
      }
    }
    if (mejaWizardBadge) {
      mejaWizardBadge.textContent = pageCount > 0 ? `${page + 1} / ${pageCount}` : '';
    }
    syncWizardButtons();
  }

  function syncWizardButtons() {
    const onStep1 = wizardStep === 1;
    const onStep2 = wizardStep === 2;
    const hasMeja = mejaCount > 0;
    const pageCount = getMejaPageCount();
    const isLastPage = !hasMeja || mejaPage >= pageCount - 1;
    const isFirstPage = mejaPage <= 0;

    btnWizardBack?.classList.toggle('d-none', onStep1);
    btnWizardNext?.classList.toggle('d-none', onStep2 && isLastPage);
    submitBtn?.classList.toggle('d-none', !(onStep2 && isLastPage));

    if (btnWizardNext) {
      if (onStep1) {
        btnWizardNext.textContent = 'Lanjut';
      } else if (hasMeja && !isLastPage) {
        btnWizardNext.textContent = 'Halaman berikutnya';
      }
    }

    if (btnWizardBack) {
      btnWizardBack.textContent = onStep2 && !isFirstPage ? 'Halaman sebelumnya' : 'Kembali';
    }
  }

  function syncMejaWizardPanels() {
    const n = mejaCount;
    mejaWizardEmpty?.classList.toggle('d-none', n > 0);
    mejaWizardPanel?.classList.toggle('d-none', n === 0);
  }

  function renderMejaRows(count, existing) {
    if (!mejaInputsContainer) return;
    mejaInputsContainer.innerHTML = '';
    const n = Math.max(0, parseInt(count, 10) || 0);
    mejaCount = n;
    const existingList = Array.isArray(existing) ? existing : [];

    for (let i = 0; i < n; i++) {
      const rowData = existingList[i] || {};
      const wrap = document.createElement('div');
      wrap.className = 'border rounded p-2 meja-input-row' + (i >= MEJA_PER_PAGE ? ' d-none' : '');

      const title = document.createElement('div');
      title.className = 'fw-semibold mb-1';
      title.textContent = `Meja ${i + 1}`;
      wrap.appendChild(title);

      const row = document.createElement('div');
      row.className = 'row g-2';

      const colNama = document.createElement('div');
      colNama.className = 'col-md-12';
      const lblNama = document.createElement('label');
      lblNama.className = 'form-label';
      lblNama.textContent = 'Nama meja';
      const inpNama = document.createElement('input');
      inpNama.type = 'text';
      inpNama.className = 'form-control meja-nama';
      inpNama.required = true;
      inpNama.autocomplete = 'off';
      if (rowData.nama != null) inpNama.value = String(rowData.nama);
      colNama.appendChild(lblNama);
      colNama.appendChild(inpNama);

      const colHarga = document.createElement('div');
      colHarga.className = 'col-md-6';
      const lblHarga = document.createElement('label');
      lblHarga.className = 'form-label';
      lblHarga.textContent = 'Harga non-member / jam';
      const inpHarga = document.createElement('input');
      inpHarga.type = 'number';
      inpHarga.className = 'form-control meja-harga';
      inpHarga.min = '0';
      inpHarga.step = '0.01';
      inpHarga.required = true;
      if (rowData.harga != null && rowData.harga !== '') inpHarga.value = String(rowData.harga);
      colHarga.appendChild(lblHarga);
      colHarga.appendChild(inpHarga);

      const colHargaMember = document.createElement('div');
      colHargaMember.className = 'col-md-6';
      const lblHargaMember = document.createElement('label');
      lblHargaMember.className = 'form-label';
      lblHargaMember.textContent = 'Harga member / jam';
      const inpHargaMember = document.createElement('input');
      inpHargaMember.type = 'number';
      inpHargaMember.className = 'form-control meja-harga-member';
      inpHargaMember.min = '0';
      inpHargaMember.step = '0.01';
      if (rowData.harga_member != null && rowData.harga_member !== '') inpHargaMember.value = String(rowData.harga_member);
      colHargaMember.appendChild(lblHargaMember);
      colHargaMember.appendChild(inpHargaMember);

      row.appendChild(colNama);
      row.appendChild(colHarga);
      row.appendChild(colHargaMember);
      wrap.appendChild(row);
      mejaInputsContainer.appendChild(wrap);
    }

    syncMejaWizardPanels();
    showMejaPage(0);
  }

  function collectMejaRows() {
    const wraps = mejaInputsContainer?.querySelectorAll('.meja-input-row') ?? [];
    const meja = [];
    wraps.forEach((wrap) => {
      const nama = wrap.querySelector('.meja-nama')?.value.trim() ?? '';
      const hargaRaw = wrap.querySelector('.meja-harga')?.value ?? '';
      const hargaMemberRaw = wrap.querySelector('.meja-harga-member')?.value ?? '';
      const harga = hargaRaw === '' ? 0 : parseFloat(hargaRaw);
      const hargaMember = hargaMemberRaw === '' ? null : parseFloat(hargaMemberRaw);
      meja.push({
        nama,
        harga: Number.isFinite(harga) ? harga : 0,
        harga_member: hargaMemberRaw !== '' && Number.isFinite(hargaMember) ? hargaMember : null,
      });
    });
    return meja;
  }

  function validateStep1() {
    const nama = document.getElementById('toko_nama')?.value.trim() ?? '';
    if (!nama) {
      showAlert('Nama toko wajib diisi.');
      document.getElementById('toko_nama')?.focus();
      return false;
    }
    const jm = getJumlahMeja();
    if (jumlahMejaInput && jumlahMejaInput.value === '') {
      showAlert('Jumlah meja wajib diisi.');
      jumlahMejaInput.focus();
      return false;
    }
    if (jm < 0) {
      showAlert('Jumlah meja tidak valid.');
      jumlahMejaInput?.focus();
      return false;
    }
    return true;
  }

  function validateMejaRowAt(index) {
    const rows = mejaInputsContainer?.querySelectorAll('.meja-input-row') ?? [];
    const row = rows[index];
    if (!row) return true;

    const namaInp = row.querySelector('.meja-nama');
    const hargaInp = row.querySelector('.meja-harga');
    const nama = namaInp?.value.trim() ?? '';
    const hargaRaw = hargaInp?.value ?? '';

    if (!nama) {
      showAlert(`Nama meja ${index + 1} wajib diisi.`);
      namaInp?.focus();
      return false;
    }
    if (hargaRaw === '' || parseFloat(hargaRaw) < 0 || !Number.isFinite(parseFloat(hargaRaw))) {
      showAlert(`Harga meja ${index + 1} wajib diisi (min. 0).`);
      hargaInp?.focus();
      return false;
    }
    return true;
  }

  function validateCurrentMejaPage() {
    const { start, end } = getMejaPageRange(mejaPage);
    for (let i = start; i < end; i++) {
      if (!validateMejaRowAt(i)) {
        return false;
      }
    }
    return true;
  }

  function validateAllMejaRows() {
    const rows = mejaInputsContainer?.querySelectorAll('.meja-input-row') ?? [];
    for (let i = 0; i < rows.length; i++) {
      if (!validateMejaRowAt(i)) {
        showMejaPage(Math.floor(i / MEJA_PER_PAGE));
        return false;
      }
    }
    return true;
  }

  function goToStep2() {
    const n = getJumlahMeja();
    renderMejaRows(n, pendingMejaData);
    pendingMejaData = null;
    setWizardStep(2);
    clearAlert();
  }

  function goToStep1() {
    pendingMejaData = collectMejaRows();
    setWizardStep(1);
    clearAlert();
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
    pendingMejaData = null;
    mejaCount = 0;
    mejaPage = 0;
    renderMejaRows(0, null);
    setWizardStep(1);
  }

  document.getElementById('btn-add-toko')?.addEventListener('click', () => {
    resetTokoForm();
    titleEl.textContent = 'Tambah toko';
    jumlahMejaInput.value = '0';
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
        pendingMejaData = Array.isArray(payload.meja) ? payload.meja : [];
      } catch (_) {
        showAlert('Data toko tidak valid.');
      }
    });
  });

  btnWizardNext?.addEventListener('click', () => {
    clearAlert();
    if (wizardStep === 1) {
      if (!validateStep1()) return;
      goToStep2();
      return;
    }
    if (wizardStep === 2 && mejaCount > 0) {
      if (!validateCurrentMejaPage()) return;
      const pageCount = getMejaPageCount();
      if (mejaPage < pageCount - 1) {
        showMejaPage(mejaPage + 1);
      }
    }
  });

  btnWizardBack?.addEventListener('click', () => {
    clearAlert();
    if (wizardStep === 2 && mejaCount > 0 && mejaPage > 0) {
      showMejaPage(mejaPage - 1);
      return;
    }
    if (wizardStep === 2) {
      goToStep1();
    }
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
        AppToast.show(data.message || 'Gagal menghapus toko.', 'danger');
        return;
      }
      deleteModal?.hide();
      AppToast.saveForReload(data.message || 'Toko dihapus.');
      window.location.reload();
    } finally {
      confirmDeleteBtn.disabled = false;
    }
  });

  form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearAlert();

    if (wizardStep !== 2) {
      if (!validateStep1()) return;
      goToStep2();
      if (mejaCount > 0 && !validateAllMejaRows()) return;
    } else if (mejaCount > 0 && !validateAllMejaRows()) {
      return;
    }

    const id = tokoIdInput.value;
    const isEdit = Boolean(id);
    const url = isEdit ? routes.update(id) : routes.store;
    const method = isEdit ? 'PUT' : 'POST';
    const jumlahOk = getJumlahMeja();
    const meja = collectMejaRows();

    if (meja.length !== jumlahOk) {
      showAlert('Jumlah data meja tidak sesuai. Periksa kembali langkah detail meja.');
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
        if (wizardStep === 1) goToStep2();
        return;
      }

      if (!res.ok) {
        AppToast.show(data.message || 'Terjadi kesalahan.', 'danger');
        return;
      }

      tokoModal?.hide();
      AppToast.saveForReload(data.message || (isEdit ? 'Toko diperbarui.' : 'Toko ditambahkan.'));
      window.location.reload();
    } finally {
      submitBtn.disabled = false;
    }
  });

  setWizardStep(1);
})();
</script>
@endpush
