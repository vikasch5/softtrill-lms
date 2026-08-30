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
            @can('leads.view')
            <li
                class="{{ in_array(Route::currentRouteName(), ['lms.leads', 'lms.lead.add', 'lms.lead.import', 'lms.lead.view', 'lms.lead.edit']) ? 'active-page' : '' }}">
                <a href="{{ route('lms.leads') }}"
                    class="{{ in_array(Route::currentRouteName(), ['lms.leads', 'lms.lead.add', 'lms.lead.import', 'lms.lead.view', 'lms.lead.edit']) ? 'active-page' : '' }}">
                    <iconify-icon icon="solar:phone-calling-outline" class="menu-icon"></iconify-icon>
                    <span>Leads</span>
                </a>
            </li>
            @endcan
            @can('lead-fields.view')
            <li
                class="{{ in_array(Route::currentRouteName(), ['lms.lead-fields.list', 'lms.lead-fields.add']) ? 'active-page' : '' }}">
                <a href="{{ route('lms.lead-fields.list') }}"
                    class="{{ in_array(Route::currentRouteName(), ['lms.lead-fields.list', 'lms.lead-fields.add']) ? 'active-page' : '' }}">
                    <iconify-icon icon="solar:checklist-minimalistic-outline" class="menu-icon"></iconify-icon>
                    <span>Fields</span>
                </a>
            </li>
            @endcan
            @can('feedbacks.view')
            <li
                class="{{ in_array(Route::currentRouteName(), ['lms.feedbacks.list', 'lms.feedbacks.add']) ? 'active-page' : '' }}">
                <a href="{{ route('lms.feedbacks.list') }}"
                    class="{{ in_array(Route::currentRouteName(), ['lms.feedbacks.list', 'lms.feedbacks.add']) ? 'active-page' : '' }}">
                    <iconify-icon icon="solar:chat-round-dots-outline" class="menu-icon"></iconify-icon>
                    <span>Feedbacks</span>
                </a>
            </li>
            @endcan
            @can('users.view')
            <li
                class="{{ in_array(Route::currentRouteName(), ['lms.users.list', 'lms.users.add', 'lms.users.edit']) ? 'active-page' : '' }}">
                <a href="{{ route('lms.users.list') }}"
                    class="{{ in_array(Route::currentRouteName(), ['lms.users.list', 'lms.users.add', 'lms.users.edit']) ? 'active-page' : '' }}">
                    <iconify-icon icon="solar:users-group-rounded-outline" class="menu-icon"></iconify-icon>
                    <span>Users</span>
                </a>
            </li>
            @endcan
            @can('offers.view')
            <li
                class="{{ in_array(Route::currentRouteName(), ['lms.offers.list','lms.offer.add']) ? 'active-page' : '' }}">
                <a href="{{ route('lms.offers.list') }}"
                    class="{{ in_array(Route::currentRouteName(), ['lms.offers.list','lms.offer.add']) ? 'active-page' : '' }}">
                    <iconify-icon icon="solar:tag-price-outline" class="menu-icon"></iconify-icon>
                    <span>Offers</span>
                </a>
            </li>
            @endcan
            <li
                class="dropdown {{ in_array(Route::currentRouteName(), ['lms.performance.report','lms.agent.performance']) ? 'open' : '' }}">
                <a href="javascript:void(0)">
                    <iconify-icon icon="solar:document-text-outline" class="menu-icon"></iconify-icon>
                    <span>Report</span>
                </a>
                <ul class="sidebar-submenu">
                    @can('reports.performance')
                    <li
                        class="{{ Route::currentRouteName() == 'lms.performance.report' ? 'active-page' : '' }}">
                        <a href="{{ route('lms.performance.report') }}"
                            class="{{ Route::currentRouteName() == 'lms.performance.report' ? 'active-page' : '' }}"><i
                                class="ri-circle-fill circle-icon text-danger-main w-auto"></i>
                            Performance Report</a>
                    </li>
                    @endcan
                    @hasanyrole('TeamLeader|Agent')
                    <li
                        class="{{ Route::currentRouteName() == 'lms.agent.performance' ? 'active-page' : '' }}">
                        <a href="{{ route('lms.agent.performance') }}"
                            class="{{ Route::currentRouteName() == 'lms.agent.performance' ? 'active-page' : '' }}"><i
                                class="ri-circle-fill circle-icon text-danger-main w-auto"></i>
                            My Performance</a>
                    </li>
                    @endhasanyrole

                </ul>
            </li>
            @canany(['settings.widgets', 'settings.privacy', 'roles.manage'])
            <li
                class="dropdown {{ in_array(Route::currentRouteName(), ['lms.dashboard.widgets.list', 'lms.dashboard.widgets.add', 'lms.dashboard.widgets.edit', 'lms.settings.privacy', 'lms.roles.list', 'lms.roles.edit']) ? 'open' : '' }}">
                <a href="javascript:void(0)">
                    <iconify-icon icon="solar:settings-outline" class="menu-icon"></iconify-icon>
                    <span>Setting</span>
                </a>
                <ul class="sidebar-submenu">
                    @can('settings.widgets')
                    <li
                        class="{{ in_array(Route::currentRouteName(), ['lms.dashboard.widgets.list', 'lms.dashboard.widgets.add', 'lms.dashboard.widgets.edit']) ? 'active-page' : '' }}">
                        <a href="{{ route('lms.dashboard.widgets.list') }}"
                            class="{{ in_array(Route::currentRouteName(), ['lms.dashboard.widgets.list', 'lms.dashboard.widgets.add']) ? 'active-page' : '' }}"><i
                                class="ri-circle-fill circle-icon text-danger-main w-auto"></i>
                            Dashboard Widgets</a>
                    </li>
                    @endcan
                    @can('settings.privacy')
                    <li
                        class="{{ in_array(Route::currentRouteName(), ['lms.settings.privacy']) ? 'active-page' : '' }}">
                        <a href="{{ route('lms.settings.privacy') }}"
                            class="{{ in_array(Route::currentRouteName(), ['lms.settings.privacy']) ? 'active-page' : '' }}"><i
                                class="ri-circle-fill circle-icon text-danger-main w-auto"></i>
                            Privacy & Security</a>
                    </li>
                    @endcan
                    @can('roles.manage')
                    <li
                        class="{{ in_array(Route::currentRouteName(), ['lms.roles.list', 'lms.roles.edit']) ? 'active-page' : '' }}">
                        <a href="{{ route('lms.roles.list') }}"
                            class="{{ in_array(Route::currentRouteName(), ['lms.roles.list', 'lms.roles.edit']) ? 'active-page' : '' }}"><i
                                class="ri-circle-fill circle-icon text-danger-main w-auto"></i>
                            Roles & Permissions</a>
                    </li>
                    @endcan
                </ul>
            </li>
            @endcanany

        </ul>
    </div>
</aside>