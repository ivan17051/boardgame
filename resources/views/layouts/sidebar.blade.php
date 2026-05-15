<div class="sidebar-wrapper">
  <nav class="mt-2">
    <!--begin::Sidebar Menu-->
    <ul
      class="nav sidebar-menu flex-column"
      data-lte-toggle="treeview"
      role="navigation"
      aria-label="Main navigation"
      data-accordion="false"
      id="navigation"
    >
      
      <li class="nav-item">
        <a href="{{ url('/') }}" class="nav-link {{ url()->current() == url('/') ? 'active' : '' }}">
          <i class="nav-icon bi bi-speedometer"></i>
          <p>Dashboard</p>
        </a>
      </li>

      <li class="nav-header">DATA MASTER</li>
      <li class="nav-item">
        <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
          <i class="nav-icon bi bi-people-fill"></i>
          <p>Users</p>
        </a>
      </li>
      <li class="nav-item">
        <a href="{{ route('toko.index') }}" class="nav-link {{ request()->routeIs('toko.*') ? 'active' : '' }}">
          <i class="nav-icon bi bi-shop"></i>
          <p>Toko</p>
        </a>
      </li>
      
      <li class="nav-header">TRANSAKSI</li>
      <li class="nav-item">
        <a href="{{ route('rental.index') }}" class="nav-link {{ request()->routeIs('rental.*') ? 'active' : '' }}">
          <i class="nav-icon bi bi-clock-history"></i>
          <p>Sewa meja</p>
        </a>
      </li>
      <li class="nav-item">
        <a href="{{ route('cashflow.index') }}" class="nav-link {{ request()->routeIs('cashflow.*') ? 'active' : '' }}">
          <i class="nav-icon bi bi-cash-stack"></i>
          <p>Arus kas</p>
        </a>
      </li>

      <li class="nav-header">EXAMPLES</li>
      <li class="nav-item">
        <a href="#" class="nav-link">
          <i class="nav-icon bi bi-box-arrow-in-right"></i>
          <p>
            Auth
            <i class="nav-arrow bi bi-chevron-right"></i>
          </p>
        </a>
        <ul class="nav nav-treeview">
          <li class="nav-item">
            <a href="#" class="nav-link">
              <i class="nav-icon bi bi-box-arrow-in-right"></i>
              <p>
                Version 1
                <i class="nav-arrow bi bi-chevron-right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="./examples/login.html" class="nav-link">
                  <i class="nav-icon bi bi-circle"></i>
                  <p>Login</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="./examples/register.html" class="nav-link">
                  <i class="nav-icon bi bi-circle"></i>
                  <p>Register</p>
                </a>
              </li>
            </ul>
          </li>
          <li class="nav-item">
            <a href="#" class="nav-link">
              <i class="nav-icon bi bi-box-arrow-in-right"></i>
              <p>
                Version 2
                <i class="nav-arrow bi bi-chevron-right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="./examples/login-v2.html" class="nav-link">
                  <i class="nav-icon bi bi-circle"></i>
                  <p>Login</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="./examples/register-v2.html" class="nav-link">
                  <i class="nav-icon bi bi-circle"></i>
                  <p>Register</p>
                </a>
              </li>
            </ul>
          </li>
          <li class="nav-item">
            <a href="./examples/lockscreen.html" class="nav-link">
              <i class="nav-icon bi bi-circle"></i>
              <p>Lockscreen</p>
            </a>
          </li>
        </ul>
      </li>

    </ul>
    <!--end::Sidebar Menu-->
  </nav>
</div>