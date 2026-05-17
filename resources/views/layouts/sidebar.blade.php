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

      
    </ul>
    <!--end::Sidebar Menu-->
  </nav>
</div>