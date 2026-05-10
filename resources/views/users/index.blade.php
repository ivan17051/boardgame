@extends('layouts.layout')

@section('content')
<!--begin::App Content Header-->
<div class="app-content-header">
  <div class="container-fluid">
    <div class="row">
      <div class="col-sm-6">
        <h3 class="mb-0">Pengguna</h3>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-end">
          <li class="breadcrumb-item"><a href="{{ url('/') }}">Beranda</a></li>
          <li class="breadcrumb-item active" aria-current="page">Pengguna</li>
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
            <h3 class="card-title mb-0">Semua pengguna</h3>
          </div>
          <div class="col-md-6 text-end">
            <button type="button" class="btn btn-primary btn-sm" id="btn-add-user" data-bs-toggle="modal" data-bs-target="#userModal">
              <i class="bi bi-person-plus-fill me-1"></i> Tambah pengguna
            </button>
          </div>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th scope="col">Nama pengguna</th>
                <th scope="col">Username</th>
                <th scope="col">Peran</th>
                <th scope="col">Status</th>
                <th scope="col">Dibuat</th>
                <th scope="col" class="text-end" style="width: 140px">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($users as $user)
                <tr data-user-id="{{ $user->id }}">
                  <td class="col-name">{{ $user->nama }}</td>
                  <td class="col-username">{{ $user->username }}</td>
                  <td class="col-role">
                    <span class="badge {{ $user->role === 'admin' ? 'bg-primary' : 'bg-secondary' }}">
                      {{ $user->role === 'cashier' ? 'Kasir' : ($user->role === 'admin' ? 'Admin' : $user->role) }}
                    </span>
                  </td>
                  <td class="col-status">
                    <span class="badge {{ $user->is_active == 1 ? 'bg-success' : 'bg-danger' }}">
                      {{ $user->is_active == 1 ? 'Aktif' : 'Nonaktif' }}
                    </span>
                  </td>
                  <td class="col-created-at">{{ $user->doc }}</td>
                  <td class="text-end">
                    <button
                      type="button"
                      class="btn btn-outline-secondary btn-sm btn-edit-user"
                      data-bs-toggle="modal"
                      data-bs-target="#userModal"
                      data-user-id="{{ $user->id }}"
                      data-username="{{ $user->username }}"
                      data-role="{{ $user->role }}"
                      title="Ubah"
                    >
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button
                      type="button"
                      class="btn btn-outline-danger btn-sm btn-delete-user"
                      data-bs-toggle="modal"
                      data-bs-target="#deleteUserModal"
                      data-user-id="{{ $user->id }}"
                      data-name="{{ $user->username }}"
                      title="Hapus"
                    >
                      <i class="bi bi-trash"></i>
                    </button>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="4" class="text-center text-secondary py-4">Belum ada pengguna.</td>
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
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="userModalLabel">Tambah pengguna</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <form id="userForm" novalidate action="{{ route('users.store') }}" method="POST">
        <div class="modal-body">
          <div id="userFormAlert" class="alert alert-danger d-none" role="alert"></div>
          <input type="hidden" id="user_id" name="user_id" value="" />
          <div class="mb-3">
            <label for="user_name" class="form-label">Nama</label>
            <input type="text" class="form-control" id="user_name" name="name" required autocomplete="name" />
          </div>
          <div class="mb-3">
            <label for="user_role" class="form-label">Peran</label>
            <select class="form-select" id="user_role" name="role" required>
              <option value="">Pilih peran</option>
              <option value="admin">Admin</option>
              <option value="cashier">Kasir</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="user_password" class="form-label">Kata sandi</label>
            <input type="password" class="form-control" id="user_password" name="password" autocomplete="new-password" />
            <div class="form-text" id="passwordHelp">Wajib untuk pengguna baru. Kosongkan saat mengubah untuk mempertahankan kata sandi saat ini.</div>
          </div>
          <div class="mb-0">
            <label for="user_password_confirmation" class="form-label">Konfirmasi kata sandi</label>
            <input type="password" class="form-control" id="user_password_confirmation" name="password_confirmation" autocomplete="new-password" />
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary" id="userFormSubmit">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Konfirmasi hapus -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteUserModalLabel">Hapus pengguna</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">Hapus <strong id="deleteUserName"></strong>? Tindakan ini tidak dapat dibatalkan.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteUser">Hapus</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  const TOAST_STORAGE_KEY = 'users.index.toast';
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  const routes = {
    store: @json(route('users.store')),
    update: (id) => @json(url('/users')) + '/' + id,
    destroy: (id) => @json(url('/users')) + '/' + id,
  };

  const userModalEl = document.getElementById('userModal');
  const userModal = userModalEl ? new bootstrap.Modal(userModalEl) : null;
  const deleteModalEl = document.getElementById('deleteUserModal');
  const deleteModal = deleteModalEl ? new bootstrap.Modal(deleteModalEl) : null;

  const form = document.getElementById('userForm');
  const alertEl = document.getElementById('userFormAlert');
  const titleEl = document.getElementById('userModalLabel');
  const userIdInput = document.getElementById('user_id');
  const passwordInput = document.getElementById('user_password');
  const passwordConfirmInput = document.getElementById('user_password_confirmation');
  const passwordHelp = document.getElementById('passwordHelp');
  const deleteNameEl = document.getElementById('deleteUserName');
  const confirmDeleteBtn = document.getElementById('confirmDeleteUser');

  let deleteTargetId = null;

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

  function resetUserForm() {
    form.reset();
    userIdInput.value = '';
    passwordInput.removeAttribute('required');
    passwordConfirmInput.removeAttribute('required');
    clearAlert();
  }

  document.getElementById('btn-add-user')?.addEventListener('click', () => {
    resetUserForm();
    titleEl.textContent = 'Tambah pengguna';
    passwordInput.setAttribute('required', 'required');
    passwordConfirmInput.setAttribute('required', 'required');
    passwordHelp.textContent = 'Minimal 8 karakter.';
  });

  document.querySelectorAll('.btn-edit-user').forEach((btn) => {
    btn.addEventListener('click', () => {
      resetUserForm();
      titleEl.textContent = 'Ubah pengguna';
      userIdInput.value = btn.dataset.userId || '';
      document.getElementById('user_name').value = btn.dataset.username || '';
      document.getElementById('user_role').value = btn.dataset.role || '';
      passwordInput.removeAttribute('required');
      passwordConfirmInput.removeAttribute('required');
      passwordHelp.textContent = 'Kosongkan untuk mempertahankan kata sandi saat ini.';
    });
  });

  userModalEl?.addEventListener('hidden.bs.modal', () => {
    resetUserForm();
    titleEl.textContent = 'Tambah pengguna';
  });

  document.querySelectorAll('.btn-delete-user').forEach((btn) => {
    btn.addEventListener('click', () => {
      deleteTargetId = btn.dataset.userId || null;
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
        showToast(data.message || 'Gagal menghapus pengguna.', 'danger');
        return;
      }
      deleteModal?.hide();
      saveToastForReload(data.message || 'Pengguna dihapus.');
      window.location.reload();
    } finally {
      confirmDeleteBtn.disabled = false;
    }
  });

  form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearAlert();

    const id = userIdInput.value;
    const isEdit = Boolean(id);
    const url = isEdit ? routes.update(id) : routes.store;
    const method = isEdit ? 'PUT' : 'POST';

    const body = {
      username: document.getElementById('user_name').value.trim(),
      role: document.getElementById('user_role').value.trim(),
    };

    const pwd = passwordInput.value;
    const pwdC = passwordConfirmInput.value;
    if (!isEdit || pwd.length > 0 || pwdC.length > 0) {
      body.password = pwd;
      body.password_confirmation = pwdC;
    }

    const submitBtn = document.getElementById('userFormSubmit');
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

      userModal?.hide();
      saveToastForReload(data.message || (isEdit ? 'Pengguna diperbarui.' : 'Pengguna ditambahkan.'));
      window.location.reload();
    } finally {
      submitBtn.disabled = false;
    }
  });

  showSavedToast();
})();
</script>
@endpush
