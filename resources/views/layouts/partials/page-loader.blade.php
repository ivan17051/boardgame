<div id="page-loader" class="page-loader is-active" role="status" aria-live="polite" aria-label="Memuat halaman">
  <div class="page-loader__backdrop"></div>
  <div class="page-loader__panel">
    <div class="page-loader__spinner" aria-hidden="true"></div>
    <p class="page-loader__text mb-0">Memuat…</p>
  </div>
</div>

<style>
  .page-loader {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.2s ease, visibility 0.2s ease;
  }

  .page-loader.is-active {
    pointer-events: auto;
    opacity: 1;
    visibility: visible;
  }

  .page-loader__backdrop {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(2px);
  }

  .page-loader__panel {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    min-width: 160px;
    padding: 1.25rem 1.5rem;
    border-radius: 0.75rem;
    background: var(--bs-body-bg, #fff);
    box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.15);
  }

  .page-loader__spinner {
    width: 2.5rem;
    height: 2.5rem;
    border: 0.25rem solid rgba(0, 97, 49, 0.2);
    border-top-color: #006131;
    border-radius: 50%;
    animation: page-loader-spin 0.75s linear infinite;
  }

  .page-loader__text {
    font-size: 0.9rem;
    color: var(--bs-secondary-color, #6c757d);
  }

  @keyframes page-loader-spin {
    to {
      transform: rotate(360deg);
    }
  }
</style>

<script>
(function () {
  var loader = document.getElementById('page-loader');
  if (!loader) return;

  function showLoader() {
    loader.classList.add('is-active');
    loader.setAttribute('aria-busy', 'true');
  }

  function hideLoader() {
    loader.classList.remove('is-active');
    loader.removeAttribute('aria-busy');
  }

  window.PageLoader = { show: showLoader, hide: hideLoader };

  window.addEventListener('load', hideLoader);
  window.addEventListener('pageshow', function (e) {
    if (e.persisted) hideLoader();
  });

  window.addEventListener('beforeunload', showLoader);

  function shouldNavigate(href, link) {
    if (!href || href === '#' || href.indexOf('javascript:') === 0) return false;
    if (href.charAt(0) === '#') return false;
    if (link.getAttribute('target') === '_blank') return false;
    if (link.hasAttribute('download')) return false;
    if (link.getAttribute('data-bs-toggle')) return false;
    if (link.getAttribute('data-lte-toggle')) return false;
    if (link.getAttribute('data-no-page-loader') !== null) return false;

    try {
      var url = new URL(href, window.location.href);
      if (url.origin !== window.location.origin) return false;
      if (
        url.pathname === window.location.pathname &&
        url.search === window.location.search &&
        url.hash
      ) {
        return false;
      }
      return true;
    } catch (err) {
      return false;
    }
  }

  document.addEventListener('click', function (e) {
    var link = e.target.closest('a[href]');
    if (!link || e.defaultPrevented) return;
    if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    if (!shouldNavigate(link.getAttribute('href'), link)) return;
    showLoader();
  });

  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form || form.tagName !== 'FORM') return;
    if (form.getAttribute('data-no-page-loader') !== null) return;
    if (form.getAttribute('target') === '_blank') return;
    var method = (form.getAttribute('method') || 'get').toLowerCase();
    if (method === 'dialog') return;

    setTimeout(function () {
      if (e.defaultPrevented) return;
      showLoader();
    }, 0);
  });
})();
</script>
