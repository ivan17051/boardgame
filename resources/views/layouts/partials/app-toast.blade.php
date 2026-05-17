<script>
(function () {
  var STORAGE_KEY = 'app.toast';

  function show(message, variant) {
    if (!message || typeof Swal === 'undefined') return;

    variant = variant || 'success';
    var icon = variant === 'danger' ? 'error' : variant === 'warning' ? 'warning' : 'success';

    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: icon,
      title: message,
      showConfirmButton: false,
      timer: 3500,
      timerProgressBar: true,
    });
  }

  function saveForReload(message, variant) {
    if (!message) return;
    try {
      sessionStorage.setItem(STORAGE_KEY, JSON.stringify({ message: message, variant: variant || 'success' }));
    } catch (err) {
      // sessionStorage unavailable
    }
  }

  function showSaved() {
    var raw;
    try {
      raw = sessionStorage.getItem(STORAGE_KEY);
    } catch (err) {
      return;
    }
    if (!raw) return;
    sessionStorage.removeItem(STORAGE_KEY);
    try {
      var payload = JSON.parse(raw);
      show(payload.message, payload.variant);
    } catch (err) {
      // ignore invalid JSON
    }
  }

  window.AppToast = {
    show: show,
    saveForReload: saveForReload,
    showSaved: showSaved,
  };

  document.addEventListener('DOMContentLoaded', showSaved);
})();
</script>
