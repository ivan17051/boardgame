<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
  <title>@yield('title', 'Omahjong')</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous" />
  <link rel="stylesheet" href="{{ asset('public/css/adminlte.css') }}" />
  <style>
    :root {
      --brand: #006131;
      --brand-dark: #004d26;
    }
    body {
      min-height: 100vh;
      background: linear-gradient(165deg, #f0f7f3 0%, #e8f0eb 45%, #f8faf9 100%);
    }
    .public-navbar {
      background: rgba(255, 255, 255, 0.92);
      backdrop-filter: blur(8px);
      border-bottom: 1px solid rgba(0, 97, 49, 0.1);
      box-shadow: 0 4px 20px rgba(0, 60, 30, 0.05);
    }
    .public-navbar .navbar-brand img {
      height: 36px;
      width: auto;
    }
    .public-navbar .btn-admin {
      font-size: 0.8rem;
      padding: 0.3rem 0.75rem;
      border-color: var(--brand);
      color: var(--brand);
    }
    .public-navbar .btn-admin:hover {
      background: var(--brand);
      border-color: var(--brand);
      color: #fff;
    }
    .page-shell {
      max-width: 1100px;
      margin: 0 auto;
      padding: 1.5rem 1rem 2.5rem;
    }
    .phone-country-select {
      max-width: 9.5rem;
      flex: 0 0 auto;
    }
    .page-loader {
      position: fixed;
      inset: 0;
      z-index: 2000;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 1rem;
      background: rgba(240, 247, 243, 0.82);
      backdrop-filter: blur(6px);
      opacity: 0;
      visibility: hidden;
      transition: opacity 0.2s ease, visibility 0.2s ease;
    }
    .page-loader.is-active {
      opacity: 1;
      visibility: visible;
    }
    .page-loader__spinner {
      width: 3rem;
      height: 3rem;
      border: 0.3rem solid rgba(0, 97, 49, 0.18);
      border-top-color: var(--brand);
      border-radius: 50%;
      animation: pageLoaderSpin 0.7s linear infinite;
    }
    .page-loader__text {
      font-size: 0.9rem;
      font-weight: 600;
      color: var(--brand-dark);
      letter-spacing: 0.02em;
    }
    @keyframes pageLoaderSpin {
      to { transform: rotate(360deg); }
    }
    @media (prefers-reduced-motion: reduce) {
      .page-loader { transition: none; }
      .page-loader__spinner { animation-duration: 1.4s; }
    }
  </style>
  @stack('styles')
</head>
<body>
  <div class="page-loader" id="pageLoader" role="status" aria-live="polite" aria-hidden="true">
    <div class="page-loader__spinner"></div>
    <span class="page-loader__text">Memuat...</span>
  </div>

  <nav class="public-navbar sticky-top">
    <div class="container-fluid px-3 px-md-4">
      <div class="d-flex align-items-center justify-content-between py-2">
        <a class="navbar-brand p-0 m-0" href="{{ route('home') }}">
          <img src="{{ asset('public/assets/img/logo.png') }}" alt="Omahjong" />
        </a>
        @auth
          <a href="{{ route('rental.index') }}" class="btn btn-sm btn-admin">
            <i class="bi bi-speedometer2 me-1"></i>Dashboard
          </a>
        @else
          <a href="{{ route('login') }}" class="btn btn-sm btn-admin">
            <i class="bi bi-box-arrow-in-right me-1"></i>Admin
          </a>
        @endauth
      </div>
    </div>
  </nav>

  <div class="page-shell">
    @yield('content')
  </div>

  <script>
    (function () {
      const loader = document.getElementById('pageLoader');
      if (!loader) return;

      let hideTimer = null;

      const show = () => {
        window.clearTimeout(hideTimer);
        loader.classList.add('is-active');
        loader.setAttribute('aria-hidden', 'false');
      };

      const hide = () => {
        loader.classList.remove('is-active');
        loader.setAttribute('aria-hidden', 'true');
      };

      const isModifiedClick = (event) =>
        event.defaultPrevented || event.button !== 0 ||
        event.metaKey || event.ctrlKey || event.shiftKey || event.altKey;

      document.addEventListener('click', (event) => {
        const link = event.target.closest('a');
        if (!link || isModifiedClick(event)) return;

        const href = link.getAttribute('href');
        if (!href || href.startsWith('#') || link.hasAttribute('download')) return;
        if (link.dataset.noLoading !== undefined) return;
        if (/^(javascript:|mailto:|tel:)/i.test(href)) return;
        if (link.target && link.target !== '_self') return;

        const url = new URL(link.href, window.location.href);
        if (url.origin !== window.location.origin) return;
        if (url.pathname === window.location.pathname && url.hash) return;

        show();
      });

      document.addEventListener('submit', (event) => {
        const form = event.target;
        if (event.defaultPrevented) return;
        if (form.dataset.noLoading !== undefined) return;
        if ((form.getAttribute('target') || '_self') !== '_self') return;
        show();
      });

      window.addEventListener('pageshow', hide);
      window.addEventListener('pagehide', hide);
    })();
  </script>

  @stack('scripts')
</body>
</html>
