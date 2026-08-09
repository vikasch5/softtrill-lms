<aside class="sidebar">
    <button type="button" class="sidebar-close-btn">
        <iconify-icon icon="radix-icons:cross-2"></iconify-icon>
    </button>
    <div>
        <a href="{{ route('lms.dashboard') }}" class="sidebar-logo d-flex justify-content-center">
            <img src="{{ asset('lms/images/logo.png') }}" alt="site logo" class="light-logo d-flex justify-content-center">
            {{-- <span class="logo-text fs-6">LMS</span> --}}
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
            <li
                class="{{ in_array(Route::currentRouteName(), ['lms.leads', 'lms.lead.add', 'lms.lead.import', 'lms.lead.view', 'lms.lead.edit']) ? 'active-page' : '' }}">
                <a href="{{ route('lms.leads') }}"
                    class="{{ in_array(Route::currentRouteName(), ['lms.leads', 'lms.lead.add', 'lms.lead.import', 'lms.lead.view', 'lms.lead.edit']) ? 'active-page' : '' }}">
                    <iconify-icon icon="solar:phone-calling-outline" class="menu-icon"></iconify-icon>
                    <span>Leads</span>
                </a>
            </li>
            @role('Admin')
            <li
                class="{{ in_array(Route::currentRouteName(), ['lms.lead-fields.list', 'lms.lead-fields.add']) ? 'active-page' : '' }}">
                <a href="{{ route('lms.lead-fields.list') }}"
                    class="{{ in_array(Route::currentRouteName(), ['lms.lead-fields.list', 'lms.lead-fields.add']) ? 'active-page' : '' }}">
                    <iconify-icon icon="solar:checklist-minimalistic-outline" class="menu-icon"></iconify-icon>
                    <span>Fields</span>
                </a>
            </li>
            <li
                class="{{ in_array(Route::currentRouteName(), ['lms.feedbacks.list', 'lms.feedbacks.add']) ? 'active-page' : '' }}">
                <a href="{{ route('lms.feedbacks.list') }}"
                    class="{{ in_array(Route::currentRouteName(), ['lms.feedbacks.list', 'lms.feedbacks.add']) ? 'active-page' : '' }}">
                    <iconify-icon icon="solar:chat-round-dots-outline" class="menu-icon"></iconify-icon>
                    <span>Feedbacks</span>
                </a>
            </li>
            @endrole
            @role('Admin|Manager|Cluster')
            <li
                class="{{ in_array(Route::currentRouteName(), ['lms.users.list', 'lms.users.add', 'lms.users.edit']) ? 'active-page' : '' }}">
                <a href="{{ route('lms.users.list') }}"
                    class="{{ in_array(Route::currentRouteName(), ['lms.users.list', 'lms.users.add', 'lms.users.edit']) ? 'active-page' : '' }}">
                    <iconify-icon icon="solar:users-group-rounded-outline" class="menu-icon"></iconify-icon>
                    <span>Users</span>
                </a>
            </li>
            <li
                class="{{ in_array(Route::currentRouteName(), ['lms.offers.list','lms.offer.add']) ? 'active-page' : '' }}">
                <a href="{{ route('lms.offers.list') }}"
                    class="{{ in_array(Route::currentRouteName(), ['lms.offers.list','lms.offer.add']) ? 'active-page' : '' }}">
                    <iconify-icon icon="solar:tag-price-outline" class="menu-icon"></iconify-icon>
                    <span>Offers</span>
                </a>
            </li>
            @endrole
            @role('Admin')
            <li
                class="dropdown {{ in_array(Route::currentRouteName(), ['lms.dashboard.widgets.list', 'lms.dashboard.widgets.add', 'lms.dashboard.widgets.edit', 'lms.settings.privacy']) ? 'open' : '' }}">
                <a href="javascript:void(0)">
                    <iconify-icon icon="solar:pie-chart-outline" class="menu-icon"></iconify-icon>
                    <span>Setting</span>
                </a>
                <ul class="sidebar-submenu">
                    <li
                        class="{{ in_array(Route::currentRouteName(), ['lms.dashboard.widgets.list', 'lms.dashboard.widgets.add', 'lms.dashboard.widgets.edit']) ? 'active-page' : '' }}">
                        <a href="{{ route('lms.dashboard.widgets.list') }}"
                            class="{{ in_array(Route::currentRouteName(), ['lms.dashboard.widgets.list', 'lms.dashboard.widgets.add']) ? 'active-page' : '' }}"><i
                                class="ri-circle-fill circle-icon text-danger-main w-auto"></i>
                            Dashboard Widgets</a>
                    </li>
                    <li
                        class="{{ in_array(Route::currentRouteName(), ['lms.settings.privacy']) ? 'active-page' : '' }}">
                        <a href="{{ route('lms.settings.privacy') }}"
                            class="{{ in_array(Route::currentRouteName(), ['lms.settings.privacy']) ? 'active-page' : '' }}"><i
                                class="ri-circle-fill circle-icon text-danger-main w-auto"></i>
                            Privacy & Security</a>
                    </li>
                </ul>
            </li>
            @endrole

        </ul>
    </div>
</aside>