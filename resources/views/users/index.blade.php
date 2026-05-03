@extends('layouts.layout')

@section('content')
<!--begin::App Content Header-->
<div class="app-content-header">
  <div class="container-fluid">
    <div class="row">
      <div class="col-sm-6">
        <h3 class="mb-0">Users</h3>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-end">
          <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
          <li class="breadcrumb-item active" aria-current="page">Users</li>
        </ol>
      </div>
    </div>
  </div>
</div>
<!--end::App Content Header-->

<div class="app-content">
  <div class="container-fluid">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title mb-0">All users</h3>
        <button type="button" class="btn btn-primary btn-sm" id="btn-add-user" data-bs-toggle="modal" data-bs-target="#userModal">
          <i class="bi bi-person-plus-fill me-1"></i> Add user
        </button>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th scope="col">Username</th>
                <th scope="col">Role</th>
                <th scope="col">Created</th>
                <th scope="col" class="text-end" style="width: 140px">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($users as $user)
                <tr data-user-id="{{ $user->id }}">
                  <td class="col-name">{{ $user->username }}</td>
                  <td class="col-email">{{ $user->role }}</td>
                  <td class="text-secondary">{{ $user->doc }}</td>
                  <td class="text-end">
                    <button
                      type="button"
                      class="btn btn-outline-secondary btn-sm btn-edit-user"
                      data-bs-toggle="modal"
                      data-bs-target="#userModal"
                      data-user-id="{{ $user->id }}"
                      data-name="{{ $user->username }}"
                      data-email="{{ $user->role }}"
                      title="Edit"
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
                      title="Delete"
                    >
                      <i class="bi bi-trash"></i>
                    </button>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="4" class="text-center text-secondary py-4">No users yet.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Create / Edit Modal -->
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="userModalLabel">Add user</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="userForm" novalidate action="{{ route('users.store') }}" method="POST">
        <div class="modal-body">
          <div id="userFormAlert" class="alert alert-danger d-none" role="alert"></div>
          <input type="hidden" id="user_id" name="user_id" value="" />
          <div class="mb-3">
            <label for="user_name" class="form-label">Name</label>
            <input type="text" class="form-control" id="user_name" name="name" required autocomplete="name" />
          </div>
          <div class="mb-3">
            <label for="user_email" class="form-label">Role</label>
            <input type="text" class="form-control" id="user_role" name="role" required autocomplete="role" />
          </div>
          <div class="mb-3">
            <label for="user_password" class="form-label">Password</label>
            <input type="password" class="form-control" id="user_password" name="password" autocomplete="new-password" />
            <div class="form-text" id="passwordHelp">Required for new users. Leave blank when editing to keep the current password.</div>
          </div>
          <div class="mb-0">
            <label for="user_password_confirmation" class="form-label">Confirm password</label>
            <input type="password" class="form-control" id="user_password_confirmation" name="password_confirmation" autocomplete="new-password" />
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="userFormSubmit">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete confirmation -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteUserModalLabel">Delete user</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">Delete <strong id="deleteUserName"></strong>? This cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteUser">Delete</button>
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
    titleEl.textContent = 'Add user';
    passwordInput.setAttribute('required', 'required');
    passwordConfirmInput.setAttribute('required', 'required');
    passwordHelp.textContent = 'Minimum 8 characters.';
  });

  document.querySelectorAll('.btn-edit-user').forEach((btn) => {
    btn.addEventListener('click', () => {
      resetUserForm();
      titleEl.textContent = 'Edit user';
      userIdInput.value = btn.dataset.userId || '';
      document.getElementById('user_name').value = btn.dataset.name || '';
      document.getElementById('user_email').value = btn.dataset.email || '';
      passwordInput.removeAttribute('required');
      passwordConfirmInput.removeAttribute('required');
      passwordHelp.textContent = 'Leave blank to keep the current password.';
    });
  });

  userModalEl?.addEventListener('hidden.bs.modal', () => {
    resetUserForm();
    titleEl.textContent = 'Add user';
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
        alert(data.message || 'Could not delete user.');
        return;
      }
      deleteModal?.hide();
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
      name: document.getElementById('user_name').value.trim(),
      email: document.getElementById('user_email').value.trim(),
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
        showAlert(data.message || 'Something went wrong.');
        return;
      }

      userModal?.hide();
      window.location.reload();
    } finally {
      submitBtn.disabled = false;
    }
  });
})();
</script>
@endpush
