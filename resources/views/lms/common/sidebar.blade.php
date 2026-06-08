<aside class="sidebar">
    <button type="button" class="sidebar-close-btn">
        <iconify-icon icon="radix-icons:cross-2"></iconify-icon>
    </button>
    <div>
        <a href="index.html" class="sidebar-logo">
            {{-- <img src="assets/images/logo.png" alt="site logo" class="light-logo">
            <img src="assets/images/logo-light.png" alt="site logo" class="dark-logo">
            <img src="assets/images/logo-icon.png" alt="site logo" class="logo-icon"> --}}
            <span class="logo-text fs-6">LMS</span>
        </a>
    </div>
    <div class="sidebar-menu-area">
        <ul class="sidebar-menu" id="sidebar-menu">
            <li class="{{ Route::currentRouteName() == 'lms.dashboard' ? 'active-page' : '' }}">
                <a href="{{ route('lms.dashboard') }}"
                    class="{{ Route::currentRouteName() == 'lms.dashboard' ? 'active-page' : '' }}">
                    <iconify-icon icon="solar:home-2-outline" class="menu-icon"></iconify-icon>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="{{ Route::currentRouteName() == 'lms.leads' ? 'active-page' : '' }}">
                <a href="{{ route('lms.leads') }}"
                    class="{{ Route::currentRouteName() == 'lms.leads' ? 'active-page' : '' }}">
                    <iconify-icon icon="solar:home-2-outline" class="menu-icon"></iconify-icon>
                    <span>Leads</span>
                </a>
            </li>
            <li class="{{ Route::currentRouteName() == 'lms.lead-fields.list' ? 'active-page' : '' }}">
                <a href="{{ route('lms.lead-fields.list') }}"
                    class="{{ Route::currentRouteName() == 'lms.lead-fields.list' ? 'active-page' : '' }}">
                    <iconify-icon icon="solar:home-2-outline" class="menu-icon"></iconify-icon>
                    <span>Fields</span>
                </a>
            </li>
            <li class="{{ Route::currentRouteName() == 'lms.feedbacks.list' ? 'active-page' : '' }}">
                <a href="{{ route('lms.feedbacks.list') }}"
                    class="{{ Route::currentRouteName() == 'lms.feedbacks.list' ? 'active-page' : '' }}">
                    <iconify-icon icon="solar:home-2-outline" class="menu-icon"></iconify-icon>
                    <span>Feedbacks</span>
                </a>
            </li>
            <li class="{{ Route::currentRouteName() == 'lms.users.list' ? 'active-page' : '' }}">
                <a href="{{ route('lms.users.list') }}"
                    class="{{ Route::currentRouteName() == 'lms.users.list' ? 'active-page' : '' }}">
                    <iconify-icon icon="solar:home-2-outline" class="menu-icon"></iconify-icon>
                    <span>Users</span>
                </a>
            </li>

        </ul>
    </div>
</aside>