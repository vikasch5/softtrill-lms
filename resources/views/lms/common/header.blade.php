<style>
    .navbar-header {
        --followup-today-bg: linear-gradient(135deg, #e8fff1 0%, #c9f7dc 100%);
        --followup-today-icon: #0f9f57;
        --followup-today-ring: rgba(15, 159, 87, 0.18);
        --followup-pending-bg: linear-gradient(135deg, #fff2e8 0%, #ffd7bd 100%);
        --followup-pending-icon: #e06a11;
        --followup-pending-ring: rgba(224, 106, 17, 0.18);
        --followup-upcoming-bg: linear-gradient(135deg, #edf4ff 0%, #cfe0ff 100%);
        --followup-upcoming-icon: #2c66f0;
        --followup-upcoming-ring: rgba(44, 102, 240, 0.18);
        --header-soft-border: #dbe5f2;
        --header-surface: #ffffff;
    }


    .navbar-header {
        height: auto;
        min-height: 50px;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.98) 100%);
        border-bottom: 1px solid rgba(219, 229, 242, 0.95);
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
        backdrop-filter: blur(10px);
    }

    .header-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        width: 100%;
    }

    .header-toolbar-start,
    .header-toolbar-end {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-shrink: 0;
    }

    .header-toolbar-center {
        flex: 1 1 auto;
        min-width: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .header-followups {
        width: min(100%, 760px);
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
    }

    .header-followup-card {
        min-width: 0;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 16px;
        border: 1px solid var(--header-soft-border);
        background: var(--header-surface);
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        position: relative;
        overflow: hidden;
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }

    .header-followup-card::before {
        content: "";
        position: absolute;
        inset: 0;
        opacity: 1;
        z-index: 0;
    }

    .header-followup-card>* {
        position: relative;
        z-index: 1;
    }

    .header-followup-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 14px 28px rgba(15, 23, 42, 0.12);
    }

    .header-followup-icon {
        width: 34px;
        height: 34px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 13px;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.35);
    }

    .header-followup-card.today::before {
        background: var(--followup-today-bg);
    }

    .header-followup-card.today .header-followup-icon {
        background: rgba(255, 255, 255, 0.64);
        color: var(--followup-today-icon);
        box-shadow: 0 0 0 6px var(--followup-today-ring);
    }

    .header-followup-card.pending::before {
        background: var(--followup-pending-bg);
    }

    .header-followup-card.pending .header-followup-icon {
        background: rgba(255, 255, 255, 0.64);
        color: var(--followup-pending-icon);
        box-shadow: 0 0 0 6px var(--followup-pending-ring);
    }

    .header-followup-card.upcoming::before {
        background: var(--followup-upcoming-bg);
    }

    .header-followup-card.upcoming .header-followup-icon {
        background: rgba(255, 255, 255, 0.64);
        color: var(--followup-upcoming-icon);
        box-shadow: 0 0 0 6px var(--followup-upcoming-ring);
    }

    .header-followup-content {
        min-width: 0;
        flex: 1 1 auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }

    .header-followup-label {
        font-size: 12px;
        line-height: 1.2;
        color: #43536a;
        margin-bottom: 0;
        font-weight: 700;
        letter-spacing: 0;
        text-transform: none;
        white-space: nowrap;
    }

    .header-followup-value {
        font-size: 18px;
        line-height: 1;
        color: #0f172a;
        font-weight: 800;
        text-shadow: 0 1px 0 rgba(255, 255, 255, 0.45);
        white-space: nowrap;
        flex-shrink: 0;
    }

    .header-followup-separator {
        color: #64748b;
        font-weight: 700;
        margin: 0 1px 0 3px;
    }

    .header-toolbar-end .dropdown>button {
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.08);
    }

    @media (max-width: 1399.98px) {
        .header-followups {
            width: min(100%, 640px);
        }
    }

    @media (max-width: 1199.98px) {
        .navbar-header {
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
        }

        .header-toolbar {
            flex-wrap: wrap;
            align-items: flex-start;
        }

        .header-toolbar-start {
            order: 1;
        }

        .header-toolbar-end {
            order: 2;
            margin-left: auto;
        }

        .header-toolbar-center {
            order: 3;
            flex: 0 0 100%;
            justify-content: flex-start;
        }

        .header-followups {
            width: 100%;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 767.98px) {
        .header-followups {
            grid-template-columns: 1fr;
        }

        .header-toolbar-end {
            flex-wrap: wrap;
            justify-content: flex-end;
        }
    }

    @media (max-width: 575.98px) {
        .wallet-box {
            width: 100%;
            justify-content: center;
        }

        .header-followup-card {
            padding: 8px 10px;
        }

        .header-followup-value {
            font-size: 17px;
        }

        .header-followup-label {
            font-size: 11px;
        }
    }
</style>
<div class="navbar-header">
    <div class="header-toolbar">
        <div class="header-toolbar-start">
            <div class="d-flex align-items-center gap-4">
                <button type="button" class="sidebar-toggle">
                    <iconify-icon icon="heroicons:bars-3-solid" class="icon text-2xl non-active"></iconify-icon>
                    <iconify-icon icon="iconoir:arrow-right" class="icon text-2xl active"></iconify-icon>
                </button>
                <button type="button" class="sidebar-mobile-toggle">
                    <iconify-icon icon="heroicons:bars-3-solid" class="icon"></iconify-icon>
                </button>

            </div>
        </div>
        <div class="header-toolbar-center">
            <div class="header-followups">
                <div class="header-followup-card today">
                    <div class="header-followup-content">
                        <div class="header-followup-label">Today Followups <span
                                class="header-followup-separator">:</span></div>
                        <div class="header-followup-value">{{ $headerFollowupStats['today'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="header-followup-card pending">
                    <div class="header-followup-content">
                        <div class="header-followup-label">Pending Followups <span
                                class="header-followup-separator">:</span></div>
                        <div class="header-followup-value">{{ $headerFollowupStats['pending'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="header-followup-card upcoming">
                    <div class="header-followup-content">
                        <div class="header-followup-label">Upcoming Followups <span
                                class="header-followup-separator">:</span></div>
                        <div class="header-followup-value">{{ $headerFollowupStats['upcoming'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="header-toolbar-end">
            <div class="d-flex flex-wrap align-items-center gap-3">
                @role('user')
                <div class="wallet-box">
                    <i class="fas fa-wallet"></i>
                    <span class="wallet-text">Wallet:</span>
                    <span class="wallet-amount">
                        ₹ <span id="wallet_balance">{{ number_format(auth()->user()->wallet_balance ?? 0, 2) }}</span>
                    </span>
                </div>
                @endrole

                <div class="dropdown">
                    <button
                        class="has-indicator w-40-px h-40-px bg-neutral-200 rounded-circle d-flex justify-content-center align-items-center"
                        type="button" data-bs-toggle="dropdown">
                        <iconify-icon icon="iconoir:bell" class="text-primary-light text-xl"></iconify-icon>
                    </button>
                    <div class="dropdown-menu to-top dropdown-menu-lg p-0">
                        <div
                            class="m-16 py-12 px-16 radius-8 bg-primary-50 mb-16 d-flex align-items-center justify-content-between gap-2">
                            <div>
                                <h6 class="text-lg text-primary-light fw-semibold mb-0">Notifications</h6>
                            </div>
                            <span
                                class="text-primary-600 fw-semibold text-lg w-40-px h-40-px rounded-circle bg-base d-flex justify-content-center align-items-center">0</span>
                        </div>

                        <div class="max-h-400-px overflow-y-auto scroll-sm pe-4">
                            {{-- <a href="javascript:void(0)"
                                class="px-24 py-12 d-flex align-items-start gap-3 mb-2 justify-content-between">
                                <div
                                    class="text-black hover-bg-transparent hover-text-primary d-flex align-items-center gap-3">
                                    <span
                                        class="w-44-px h-44-px bg-success-subtle text-success-main rounded-circle d-flex justify-content-center align-items-center flex-shrink-0">
                                        <iconify-icon icon="bitcoin-icons:verify-outline"
                                            class="icon text-xxl"></iconify-icon>
                                    </span>
                                    <div>
                                        <h6 class="text-md fw-semibold mb-4">Congratulations</h6>
                                        <p class="mb-0 text-sm text-secondary-light text-w-200-px">Your profile has been
                                            Verified. Your
                                            profile has been Verified</p>
                                    </div>
                                </div>
                                <span class="text-sm text-secondary-light flex-shrink-0">23 Mins ago</span>
                            </a> --}}
                            <span
                                class="text-sm text-secondary-light flex-shrink-0 px-24 py-12 d-flex align-items-start gap-3 mb-2 justify-content-between">No
                                notifications found</span>
                        </div>

                        {{-- <div class="text-center py-12 px-16">
                            <a href="javascript:void(0)" class="text-primary-600 fw-semibold text-md">See All
                                Notification</a>
                        </div> --}}

                    </div>
                </div><!-- Notification dropdown end -->

                <div class="dropdown">
                    <button class="d-flex justify-content-center align-items-center rounded-circle" type="button"
                        data-bs-toggle="dropdown">
                        <img src="{{ asset('lms/images/user.png') }}" alt="image"
                            class="w-40-px h-40-px object-fit-cover rounded-circle">
                    </button>
                    <div class="dropdown-menu to-top dropdown-menu-sm">
                        <div
                            class="py-12 px-16 radius-8 bg-primary-50 mb-16 d-flex align-items-center justify-content-between gap-2">
                            <div>
                                <h6 class="text-lg text-primary-light fw-semibold mb-2">{{ auth()->user()->name }}</h6>
                                <span
                                    class="text-secondary-light fw-medium text-sm">{{ auth()->user()->getRoleNames()->first() }}</span>
                            </div>
                            <button type="button" class="hover-text-danger">
                                <iconify-icon icon="radix-icons:cross-1" class="icon text-xl"></iconify-icon>
                            </button>
                        </div>
                        <ul class="to-top-list">
                            {{-- <li>
                                <a class="dropdown-item text-black px-0 py-8 hover-bg-transparent hover-text-primary d-flex align-items-center gap-3"
                                    href="view-profile.html">
                                    <iconify-icon icon="solar:user-linear" class="icon text-xl"></iconify-icon> My
                                    Profile</a>
                            </li>
                            <li>
                                <a class="dropdown-item text-black px-0 py-8 hover-bg-transparent hover-text-primary d-flex align-items-center gap-3"
                                    href="email.html">
                                    <iconify-icon icon="tabler:message-check" class="icon text-xl"></iconify-icon>
                                    Inbox</a>
                            </li>
                            <li>
                                <a class="dropdown-item text-black px-0 py-8 hover-bg-transparent hover-text-primary d-flex align-items-center gap-3"
                                    href="company.html">
                                    <iconify-icon icon="icon-park-outline:setting-two"
                                        class="icon text-xl"></iconify-icon> Setting</a>
                            </li> --}}
                            <li>
                                <a class="dropdown-item text-black px-0 py-8 hover-bg-transparent hover-text-danger d-flex align-items-center gap-3"
                                    href="{{ route('logout') }}">
                                    <iconify-icon icon="lucide:power" class="icon text-xl"></iconify-icon> Log Out</a>
                            </li>
                        </ul>
                    </div>
                </div><!-- Profile dropdown end -->
            </div>
        </div>
    </div>
</div>