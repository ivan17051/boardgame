<script>
(function () {
  var loginUrl = @json(route('login'));
  var showing = false;
  var originalFetch = window.fetch;

  function isJsonRequest(init) {
    init = init || {};
    var headers = init.headers;
    if (headers instanceof Headers) {
      return (
        (headers.get('Accept') || '').indexOf('application/json') !== -1 ||
        headers.get('X-Requested-With') === 'XMLHttpRequest'
      );
    }
    if (typeof headers === 'object' && headers !== null) {
      var accept = headers.Accept || headers.accept || '';
      return accept.indexOf('application/json') !== -1 || headers['X-Requested-With'] === 'XMLHttpRequest';
    }
    return false;
  }

  function showSessionExpired(message) {
    if (showing) return;
    showing = true;

    var text = message || 'Sesi Anda telah berakhir. Silakan masuk kembali untuk melanjutkan.';

    if (typeof Swal !== 'undefined') {
      Swal.fire({
        icon: 'warning',
        title: 'Sesi berakhir',
        text: text,
        confirmButtonText: 'Masuk',
        confirmButtonColor: '#006131',
        allowOutsideClick: false,
        allowEscapeKey: false,
      }).then(function () {
        window.location.href = loginUrl;
      });
      return;
    }

    alert(text);
    window.location.href = loginUrl;
  }

  async function handleExpiredResponse(response, isApi) {
    if (!isApi) return;

    if (response.status === 401 || response.status === 419) {
      var message = null;
      try {
        var body = await response.clone().json();
        message = body && body.message ? body.message : null;
      } catch (err) {
        // not JSON
      }
      showSessionExpired(message);
      return;
    }

    if (response.redirected && response.url && response.url.indexOf('/login') !== -1) {
      showSessionExpired();
      return;
    }

    var contentType = response.headers.get('content-type') || '';
    if (response.ok && contentType.indexOf('text/html') !== -1) {
      try {
        var html = await response.clone().text();
        if (
          html.indexOf('login-box') !== -1 ||
          html.indexOf('Masuk untuk memulai') !== -1 ||
          html.indexOf('name="csrf-token"') !== -1 && html.indexOf('login.store') !== -1
        ) {
          showSessionExpired();
        }
      } catch (err) {
        // ignore
      }
    }
  }

  window.fetch = function (input, init) {
    var isApi = isJsonRequest(init);
    return originalFetch.apply(this, arguments).then(function (response) {
      handleExpiredResponse(response, isApi);
      return response;
    });
  };
})();
</script>
