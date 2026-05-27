<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="changePasswordModalLabel">Ganti Password</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <form id="changePasswordForm" novalidate data-no-page-loader>
        <div class="modal-body">
          <div id="changePasswordAlert" class="alert alert-danger d-none small" role="alert"></div>

          <div class="mb-3">
            <label for="modal_current_password" class="form-label">Password lama <span class="text-danger">*</span></label>
            <input
              type="password"
              class="form-control"
              id="modal_current_password"
              name="current_password"
              required
              autocomplete="current-password"
            />
          </div>

          <div class="mb-3">
            <label for="modal_password" class="form-label">Password baru <span class="text-danger">*</span></label>
            <input
              type="password"
              class="form-control"
              id="modal_password"
              name="password"
              required
              minlength="6"
              autocomplete="new-password"
            />
            <div class="form-text">Minimal 6 karakter.</div>
          </div>

          <div class="mb-0">
            <label for="modal_password_confirmation" class="form-label">Konfirmasi password baru <span class="text-danger">*</span></label>
            <input
              type="password"
              class="form-control"
              id="modal_password_confirmation"
              name="password_confirmation"
              required
              minlength="6"
              autocomplete="new-password"
            />
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary" id="changePasswordSubmitBtn">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
(function () {
  const form = document.getElementById('changePasswordForm');
  const modalEl = document.getElementById('changePasswordModal');
  if (!form || !modalEl) return;

  const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  const alertEl = document.getElementById('changePasswordAlert');
  const submitBtn = document.getElementById('changePasswordSubmitBtn');
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  const updateUrl = @json(route('profile.password.update'));

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

  function firstValidationError(body) {
    if (!body || !body.errors) return null;
    const first = Object.values(body.errors)[0];
    return Array.isArray(first) ? first[0] : String(first);
  }

  function resetForm() {
    form.reset();
    hideAlert();
  }

  modalEl.addEventListener('hidden.bs.modal', resetForm);

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    hideAlert();

    const payload = {
      current_password: document.getElementById('modal_current_password')?.value || '',
      password: document.getElementById('modal_password')?.value || '',
      password_confirmation: document.getElementById('modal_password_confirmation')?.value || '',
    };

    if (submitBtn) submitBtn.disabled = true;

    fetch(updateUrl, {
      method: 'PUT',
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
        if (!r.ok) {
          throw new Error(firstValidationError(r.body) || (r.body && r.body.message) || 'Gagal menyimpan password.');
        }
        modal.hide();
        resetForm();
        const msg = (r.body && r.body.message) ? r.body.message : 'Password berhasil diubah.';
        if (typeof AppToast !== 'undefined') {
          AppToast.show(msg, 'success');
        }
      })
      .catch(function (err) {
        showAlert(err.message || 'Terjadi kesalahan.');
        if (typeof AppToast !== 'undefined') {
          AppToast.show(err.message || 'Gagal menyimpan password.', 'danger');
        }
      })
      .finally(function () {
        if (submitBtn) submitBtn.disabled = false;
      });
  });
})();
</script>
