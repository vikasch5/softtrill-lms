
<div class="navbar-header">
    <div class="header-toolbar">
        <div class="header-toolbar-start">
            <div class="d-flex align-items-center gap-4">
                @if(($isDialerLeadView ?? false))
                    <div>
                        <a href="{{ route('lms.dashboard') }}" class="sidebar-logo d-flex justify-content-center">
                            <img src="{{ asset('lms/images/logo.png') }}" alt="site logo"
                                class="light-logo d-flex justify-content-center">
                        </a>
                    </div>
                @endif
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
                <a href="{{ route('lms.leads', ['followup_status' => 'today']) }}"
                    style="text-decoration: none; color: inherit;">
                    <div class="header-followup-card today">
                        <div class="header-followup-content">
                            <div class="header-followup-label">Today Followups <span
                                    class="header-followup-separator">:</span></div>
                            <div class="header-followup-value">{{ $headerFollowupStats['today'] ?? 0 }}</div>
                        </div>
                    </div>
                </a>
                <a href="{{ route('lms.leads', ['followup_status' => 'missed']) }}"
                    style="text-decoration: none; color: inherit;">
                    <div class="header-followup-card missed">
                        <div class="header-followup-content">
                            <div class="header-followup-label">Missed Followups <span
                                    class="header-followup-separator">:</span></div>
                            <div class="header-followup-value">{{ $headerFollowupStats['missed'] ?? 0 }}</div>
                        </div>
                    </div>
                </a>
                <a href="{{ route('lms.leads', ['followup_status' => 'upcoming']) }}"
                    style="text-decoration: none; color: inherit;">
                    <div class="header-followup-card upcoming">
                        <div class="header-followup-content">
                            <div class="header-followup-label">Upcoming Followups <span
                                    class="header-followup-separator">:</span></div>
                            <div class="header-followup-value">{{ $headerFollowupStats['upcoming'] ?? 0 }}</div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
        <div class="header-toolbar-end">
            <div class="d-flex flex-wrap align-items-center gap-3">
                @if(isset($activeOffers) && $activeOffers->count() > 0)
                    <button class="btn btn-primary d-flex align-items-center gap-2 rounded-3 px-3 py-2 fw-medium shadow-sm" 
                            data-bs-toggle="modal" data-bs-target="#offersModal" 
                            style="font-size: 14px;">
                        <i class="bi bi-gift"></i>
                        Offers
                        <span class="badge bg-white text-primary rounded-circle ms-1">{{ $activeOffers->count() }}</span>
                    </button>
                @endif
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
                        class="has-indicator w-40-px h-40-px bg-neutral-200 rounded-circle d-flex justify-content-center align-items-center position-relative"
                        type="button" data-bs-toggle="dropdown">
                        <iconify-icon icon="iconoir:bell" class="text-primary-light text-xl"></iconify-icon>
                        <span class="badge bg-danger rounded-pill position-absolute top-0 end-0 notification-count-badge" style="display: none; transform: translate(30%, -30%); font-size: 0.65rem; padding: 0.25em 0.4em;">0</span>
                    </button>
                    <div class="dropdown-menu to-top dropdown-menu-lg p-0 notification-dropdown">
                        <div
                            class="m-16 py-12 px-16 radius-8 bg-primary-50 mb-16 d-flex align-items-center justify-content-between gap-2">
                            <div>
                                <h6 class="text-lg text-primary-light fw-semibold mb-0">Notifications</h6>
                            </div>
                            <span
                                class="text-primary-600 fw-semibold text-lg w-40-px h-40-px rounded-circle bg-base d-flex justify-content-center align-items-center notification-count-badge" style="display: none;">0</span>
                        </div>

                        <div class="max-h-400-px overflow-y-auto scroll-sm pe-4" id="notification-list-container">
                            <span class="text-sm text-secondary-light flex-shrink-0 px-24 py-12 d-flex align-items-start gap-3 mb-2 justify-content-between">Loading...</span>
                        </div>

                        <div class="text-center py-12 px-16 border-top mark-all-read-container" style="display: none;">
                            <a href="#" class="text-primary-600 fw-semibold text-md mark-all-read">Mark All as Read</a>
                        </div>

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
                            <li>
                                <a class="dropdown-item text-black px-0 py-8 hover-bg-transparent hover-text-primary d-flex align-items-center gap-3"
                                    href="#" data-bs-toggle="modal" data-bs-target="#notificationSettingsModal">
                                    <iconify-icon icon="iconoir:bell" class="icon text-xl"></iconify-icon> Notification Settings</a>
                            </li>
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

<!-- Notification Settings Modal -->
<style>
    /* Notification Dropdown Responsive Adjustments */
    @media (max-width: 1366px) {
        .notification-dropdown.dropdown-menu-lg {
            width: 340px; 
        }
        .notification-dropdown #notification-list-container {
            max-height: 280px; 
        }
    }

    #notificationSettingsModal .modal-content {
        border-radius: 12px;
        border: none;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    #notificationSettingsModal .modal-header {
        border-bottom: 1px solid #eaeaea;
        padding: 20px 24px;
    }
    #notificationSettingsModal .modal-title {
        font-weight: 600;
        font-size: 1.25rem;
        color: #333;
        margin: 0;
    }
    #notificationSettingsModal .modal-body {
        padding: 24px;
    }
    #notificationSettingsModal .push-card {
        background-color: #f8f9fa;
        border: 1px solid #eef0f2;
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    #notificationSettingsModal .push-card h6 {
        margin: 0 0 4px 0;
        font-weight: 600;
        font-size: 0.95rem;
        color: #2c3e50;
    }
    #notificationSettingsModal .push-card small {
        color: #6c757d;
        font-size: 0.8rem;
    }
    #notificationSettingsModal .section-title {
        font-weight: 600;
        color: #333;
        margin-bottom: 16px;
        font-size: 1rem;
    }
    #notificationSettingsModal .pref-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }
    #notificationSettingsModal .pref-row:last-child {
        margin-bottom: 0;
    }
    #notificationSettingsModal .pref-row label {
        color: #495057;
        font-weight: 500;
        font-size: 0.95rem;
        margin: 0;
        cursor: pointer;
    }
    #notificationSettingsModal .form-switch {
        padding-left: 0;
        margin: 0;
    }
    /* Strictly force the toggle size */
    #notificationSettingsModal .form-check-input {
        width: 40px !important;
        height: 20px !important;
        margin-top: 0 !important;
        cursor: pointer;
    }
    #notificationSettingsModal .modal-footer {
        border-top: 1px solid #eaeaea;
        padding: 16px 24px;
        display: flex;
        justify-content: flex-end;
        gap: 12px;
    }
    #notificationSettingsModal .btn-cancel {
        background-color: #f1f3f5;
        color: #495057;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: 500;
    }
    #notificationSettingsModal .btn-save {
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: 500;
    }

    /* Responsive styling for small screens */
    @media (max-width: 576px) {
        #notificationSettingsModal .modal-header {
            padding: 16px;
        }
        #notificationSettingsModal .modal-title {
            font-size: 1.1rem;
        }
        #notificationSettingsModal .modal-body {
            padding: 16px;
        }
        #notificationSettingsModal .push-card {
            padding: 12px;
            margin-bottom: 16px;
        }
        #notificationSettingsModal .push-card h6 {
            font-size: 0.9rem;
        }
        #notificationSettingsModal .push-card small {
            font-size: 0.75rem;
        }
        #notificationSettingsModal .section-title {
            font-size: 0.95rem;
            margin-bottom: 12px;
        }
        #notificationSettingsModal .pref-row {
            margin-bottom: 12px;
        }
        #notificationSettingsModal .pref-row label {
            font-size: 0.85rem;
        }
        #notificationSettingsModal .form-check-input {
            width: 36px !important;
            height: 18px !important;
        }
        #notificationSettingsModal .modal-footer {
            padding: 12px 16px;
            gap: 8px;
        }
        #notificationSettingsModal .btn-cancel,
        #notificationSettingsModal .btn-save {
            padding: 6px 12px;
            font-size: 0.85rem;
        }
    }
</style>
<div class="modal fade" id="notificationSettingsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 460px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Notification Settings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                
                <div class="push-card">
                    <div>
                        <h6>Browser Push Notifications</h6>
                        <small>Receive alerts when outside the app.</small>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="pref_browser_notifications" {{ env('VAPID_PUBLIC_KEY') ? '' : 'disabled' }}>
                    </div>
                </div>
                
                <h6 class="section-title">In-App Notifications</h6>
                <div class="pref-list">
                    <div class="pref-row">
                        <label for="pref_followup_due">Follow-up Due</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input pref-toggle" type="checkbox" id="pref_followup_due" data-key="followup_due" {{ (auth()->user()->notification_preferences['followup_due'] ?? true) ? 'checked' : '' }}>
                        </div>
                    </div>
                    
                    <div class="pref-row">
                        <label for="pref_upcoming_followup">Upcoming Follow-up</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input pref-toggle" type="checkbox" id="pref_upcoming_followup" data-key="upcoming_followup" {{ (auth()->user()->notification_preferences['upcoming_followup'] ?? true) ? 'checked' : '' }}>
                        </div>
                    </div>

                    <div class="pref-row">
                        <label for="pref_overdue_followup">Overdue Follow-up</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input pref-toggle" type="checkbox" id="pref_overdue_followup" data-key="overdue_followup" {{ (auth()->user()->notification_preferences['overdue_followup'] ?? true) ? 'checked' : '' }}>
                        </div>
                    </div>

                    <div class="pref-row">
                        <label for="pref_lead_assigned">New Lead Assigned</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input pref-toggle" type="checkbox" id="pref_lead_assigned" data-key="lead_assigned" {{ (auth()->user()->notification_preferences['lead_assigned'] ?? true) ? 'checked' : '' }}>
                        </div>
                    </div>

                    <div class="pref-row">
                        <label for="pref_lead_reassigned">Lead Reassigned</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input pref-toggle" type="checkbox" id="pref_lead_reassigned" data-key="lead_reassigned" {{ (auth()->user()->notification_preferences['lead_reassigned'] ?? true) ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-save" id="save-notification-prefs-btn">Save Preferences</button>
            </div>
        </div>
    </div>
</div>

@if(isset($activeOffers) && $activeOffers->isNotEmpty())
<!-- Clean Professional Offers Modal -->

<div class="modal fade" id="offersModal" tabindex="-1" aria-labelledby="offersModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 600px;">
    <div class="modal-content border-0 shadow" style="border-radius: 12px; background: #ffffff;">
      
      <button type="button" class="btn-close position-absolute top-0 end-0 m-3 z-3 bg-white shadow-sm" 
              data-bs-dismiss="modal" aria-label="Close" 
              style="padding: 8px; border-radius: 50%; opacity: 1; font-size: 10px;"></button>

      <div class="modal-body p-0">
        <div id="offersCarousel" class="carousel slide" data-bs-ride="carousel">

          <div class="carousel-inner" style="border-radius: 12px; overflow: hidden;">
            @foreach($activeOffers as $index => $offer)
            <div class="carousel-item {{ $index == 0 ? 'active' : '' }}">
                <div class="d-flex flex-column">
                    
                    {{-- Image Section --}}
                    <div class="w-100 bg-light d-flex align-items-center justify-content-center position-relative" style="min-height: 200px;">
                        @if($offer->image)
                            <img src="{{ asset('storage/' . $offer->image) }}" alt="Offer" class="w-100" style="height: auto; max-height: 400px; object-fit: contain; background-color: #f8fafc;">
                        @else
                            <i class="bi bi-image text-muted" style="font-size: 4rem; opacity: 0.3; padding: 4rem 0;"></i>
                        @endif
                        

                    </div>

                    {{-- Content Section --}}
                    <div class="p-4 bg-white text-center">
                        <h5 class="fw-semibold mb-2 text-dark" style="font-size: 18px;">{{ $offer->heading }}</h5>
                        
                        <p class="mb-4 text-secondary" style="font-size: 14px; line-height: 1.5; margin: 0 auto; max-width: 90%;">
                            {{ $offer->description }}
                        </p>
                        
                        @if($offer->url)
                            <a href="{{ $offer->url }}" target="_blank" class="btn btn-primary w-100 py-2 rounded-2 d-flex justify-content-center align-items-center gap-2" style="font-size: 14px; font-weight: 500;">
                                Claim Offer <i class="bi bi-box-arrow-up-right"></i>
                            </a>
                        @endif
                    </div>
                    
                </div>
            </div>
            @endforeach
          </div>

          @if($activeOffers->count() > 1)
          <button class="carousel-control-prev" type="button" data-bs-target="#offersCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon shadow-sm rounded-circle bg-dark" aria-hidden="true" style="padding: 15px; background-size: 12px; opacity: 0.7;"></span>
            <span class="visually-hidden">Previous</span>
          </button>
          
          <button class="carousel-control-next" type="button" data-bs-target="#offersCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon shadow-sm rounded-circle bg-dark" aria-hidden="true" style="padding: 15px; background-size: 12px; opacity: 0.7;"></span>
            <span class="visually-hidden">Next</span>
          </button>

          <div class="carousel-indicators">
            @foreach($activeOffers as $index => $offer)
                <button type="button" data-bs-target="#offersCarousel" data-bs-slide-to="{{ $index }}" class="{{ $index == 0 ? 'active' : '' }}" aria-current="{{ $index == 0 ? 'true' : 'false' }}" aria-label="Slide {{ $index + 1 }}"></button>
            @endforeach
          </div>
          @endif
          
        </div>
      </div>
    </div>
  </div>
</div>
@endif

<script>
    window.NotificationSystem = window.NotificationSystem || {};
    window.NotificationSystem.vapidPublicKey = "{{ config('services.webpush.vapid_public_key') }}";
</script>