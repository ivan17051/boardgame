<!doctype html>
<html lang="id">
  <!--begin::Head-->
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Masuk — Omahjong</title>

    <!--begin::Accessibility Meta Tags-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
    <meta name="color-scheme" content="light dark" />
    <meta name="theme-color" content="#006131" media="(prefers-color-scheme: light)" />
    <meta name="theme-color" content="#1a1a1a" media="(prefers-color-scheme: dark)" />
    <!--end::Accessibility Meta Tags-->

    <!--begin::Primary Meta Tags-->
    <meta name="title" content="Masuk — Omahjong" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <!--end::Primary Meta Tags-->

    <!--begin::Accessibility Features-->
    <meta name="supported-color-schemes" content="light dark" />
    <link rel="preload" href="{{ asset('public/css/adminlte.css') }}" as="style" />
    <!--end::Accessibility Features-->

    <!--begin::Fonts-->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css"
      integrity="sha256-tXJfXfp6Ewt1ilPzLDtQnJV4hclT9XuaZUKyUvmyr+Q="
      crossorigin="anonymous"
      media="print"
      onload="this.media = 'all'"
    />
    <!--end::Fonts-->

    <!--begin::Third Party Plugin(OverlayScrollbars)-->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css"
      crossorigin="anonymous"
    />
    <!--end::Third Party Plugin(OverlayScrollbars)-->

    <!--begin::Third Party Plugin(Bootstrap Icons)-->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
      crossorigin="anonymous"
    />
    <!--end::Third Party Plugin(Bootstrap Icons)-->

    <!--begin::Required Plugin(AdminLTE)-->
    <link rel="stylesheet" href="{{ asset('public/css/adminlte.css') }}" />
    <!--end::Required Plugin(AdminLTE)-->
  </head>
  <!--end::Head-->
  <!--begin::Body-->
  <body class="login-page bg-body-secondary">
    @include('layouts.partials.page-loader')
    <div class="login-box">
      <div class="card card-outline card-primary">
        <div class="card-header">
          <a
            href="{{ url('/') }}"
            class="link-dark text-center link-offset-2 link-opacity-100 link-opacity-50-hover d-block"
          >
            <h1 class="mb-0 d-flex align-items-center justify-content-center gap-2">
              <img src="{{ asset('public/assets/img/logo.png') }}" alt="Omahjong" class="opacity-75" style="max-height: 50px" />
              <!-- <span><b>Omah</b>jong</span> -->
            </h1>
          </a>
        </div>
        <div class="card-body login-card-body">
          <p class="login-box-msg">Masuk untuk memulai</p>

          @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <strong class="d-block mb-1">Gagal masuk</strong>
              <ul class="mb-0 ps-3 small">
                @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
            </div>
          @endif

          @if (session('status'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              {{ session('status') }}
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
            </div>
          @endif

          <form action="{{ route('login.store') }}" method="post" novalidate>
            @csrf
            <div class="input-group mb-1 has-validation flex-wrap">
              <div class="form-floating flex-grow-1">
                <input
                  id="loginUsername"
                  name="username"
                  type="text"
                  class="form-control @error('username') is-invalid @enderror"
                  value="{{ old('username') }}"
                  placeholder=""
                  autocomplete="username"
                  autofocus
                  required
                />
                <label for="loginUsername">Username</label>
              </div>
              <div class="input-group-text">
                <span class="bi bi-person-fill"></span>
              </div>
              @error('username')
                <div class="invalid-feedback w-100">{{ $message }}</div>
              @enderror
            </div>
            <div class="input-group mb-1 has-validation flex-wrap">
              <div class="form-floating flex-grow-1">
                <input
                  id="loginPassword"
                  name="password"
                  type="password"
                  class="form-control @error('password') is-invalid @enderror"
                  placeholder=""
                  autocomplete="current-password"
                  required
                />
                <label for="loginPassword">Password</label>
              </div>
              <div class="input-group-text">
                <span class="bi bi-lock-fill"></span>
              </div>
              @error('password')
                <div class="invalid-feedback w-100">{{ $message }}</div>
              @enderror
            </div>
            <!--begin::Row-->
            <div class="row mt-4">
              <!-- <div class="col-8 d-inline-flex align-items-center">
                <div class="form-check">
                  <input
                    class="form-check-input"
                    type="checkbox"
                    name="remember"
                    value="1"
                    id="flexCheckDefault"
                    {{ old('remember') ? 'checked' : '' }}
                  />
                  <label class="form-check-label" for="flexCheckDefault"> Ingat saya </label>
                </div>
              </div>
              <div class="col-4">
                <div class="d-grid gap-2">
                  <button type="submit" class="btn btn-primary">Masuk</button>
                </div>
              </div> -->
              <div class="col-12">
              <button type="submit" class="btn btn-primary w-100 btn-lg">Masuk</button>
              </div>
              
            </div>
            <!--end::Row-->
          </form>

          <!-- <p class="text-center mt-3 mb-0">
            <a href="{{ route('home') }}" class="small text-secondary">
              <i class="bi bi-play-circle me-1"></i>Sewa meja mandiri (tamu)
            </a>
          </p> -->

          <!-- <div class="social-auth-links text-center mb-3 d-grid gap-2">
            <p>- ATAU -</p>
            <a href="#" class="btn btn-primary">
              <i class="bi bi-facebook me-2"></i> Masuk dengan Facebook
            </a>
            <a href="#" class="btn btn-danger">
              <i class="bi bi-google me-2"></i> Masuk dengan Google+
            </a>
          </div> -->

          <!-- <p class="mb-1">
            <a href="#">Lupa password</a>
          </p>
          <p class="mb-0">
            <a href="#" class="text-center d-block">Daftar keanggotaan baru</a>
          </p> -->
        </div>
        <!-- /.login-card-body -->
      </div>
    </div>
    <!-- /.login-box -->

    <!--begin::Third Party Plugin(OverlayScrollbars)-->
    <script
      src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js"
      crossorigin="anonymous"
    ></script>
    <!--end::Third Party Plugin(OverlayScrollbars)-->
    <!--begin::Required Plugin(popperjs for Bootstrap 5)-->
    <script
      src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
      crossorigin="anonymous"
    ></script>
    <!--end::Required Plugin(popperjs for Bootstrap 5)-->
    <!--begin::Required Plugin(Bootstrap 5)-->
    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js"
      crossorigin="anonymous"
    ></script>
    <!--end::Required Plugin(Bootstrap 5)-->
    <!--begin::Required Plugin(AdminLTE)-->
    <script src="{{ asset('public/js/adminlte.js') }}"></script>
    <!--end::Required Plugin(AdminLTE)-->
    <!--begin::OverlayScrollbars Configure-->
    <script>
      const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
      const Default = {
        scrollbarTheme: 'os-theme-light',
        scrollbarAutoHide: 'leave',
        scrollbarClickScroll: true,
      };
      document.addEventListener('DOMContentLoaded', function () {
        const sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);
        const isMobile = window.innerWidth <= 992;
        if (
          sidebarWrapper &&
          typeof OverlayScrollbarsGlobal !== 'undefined' &&
          OverlayScrollbarsGlobal?.OverlayScrollbars !== undefined &&
          !isMobile
        ) {
          OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
            scrollbars: {
              theme: Default.scrollbarTheme,
              autoHide: Default.scrollbarAutoHide,
              clickScroll: Default.scrollbarClickScroll,
            },
          });
        }
      });
    </script>
    <!--end::OverlayScrollbars Configure-->
  </body>
  <!--end::Body-->
</html>
