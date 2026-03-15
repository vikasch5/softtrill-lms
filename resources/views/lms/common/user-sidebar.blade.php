<aside class="sidebar">
  <button type="button" class="sidebar-close-btn">
    <iconify-icon icon="radix-icons:cross-2"></iconify-icon>
  </button>
  <div>
    <a href="index.html" class="sidebar-logo">
      {{-- <img src="assets/images/logo.png" alt="site logo" class="light-logo">
      <img src="assets/images/logo-light.png" alt="site logo" class="dark-logo">
      <img src="assets/images/logo-icon.png" alt="site logo" class="logo-icon"> --}}
      <span class="logo-text fs-6">JobKart</span>
    </a>
  </div>
  <div class="sidebar-menu-area">
    <ul class="sidebar-menu" id="sidebar-menu">

      <li>
        <a href="{{ route('user.dashboard')}}">
          <iconify-icon icon="solar:home-2-outline" class="menu-icon"></iconify-icon>
          <span>Dashboard</span>
        </a>
      </li>

      <li>
        <a href="{{ route('user.profile') }}">
          <iconify-icon icon="solar:users-group-rounded-outline" class="menu-icon"></iconify-icon>
          <span>My Profile</span>
        </a>
      </li>

    </ul>

  </div>
</aside>