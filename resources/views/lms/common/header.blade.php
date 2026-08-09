
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
                <a href="{{ route('lms.leads', ['followup_status' => 'pending']) }}"
                    style="text-decoration: none; color: inherit;">
                    <div class="header-followup-card pending">
                        <div class="header-followup-content">
                            <div class="header-followup-label">Pending Followups <span
                                    class="header-followup-separator">:</span></div>
                            <div class="header-followup-value">{{ $headerFollowupStats['pending'] ?? 0 }}</div>
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

@if(isset($activeOffers) && $activeOffers->isNotEmpty())
<!-- Clean Professional Offers Modal -->
<style>
    #offersCarousel .carousel-indicators {
        bottom: -35px;
        margin-bottom: 0;
    }
    #offersCarousel .carousel-indicators button {
        width: 8px; 
        height: 8px; 
        border-radius: 50%; 
        background-color: #cbd5e1; 
        opacity: 1; 
        margin: 0 4px; 
        border: none; 
        transition: background-color 0.3s ease;
    }
    #offersCarousel .carousel-indicators button.active {
        background-color: #0d6efd;
    }
    #offersCarousel .carousel-control-prev,
    #offersCarousel .carousel-control-next {
        width: 40px;
        opacity: 0.8;
    }
    #offersCarousel .carousel-control-prev:hover,
    #offersCarousel .carousel-control-next:hover {
        opacity: 1;
    }
</style>

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