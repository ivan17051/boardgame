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
  </style>
  @stack('styles')
</head>
<body>
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
  @stack('scripts')
</body>
</html>
