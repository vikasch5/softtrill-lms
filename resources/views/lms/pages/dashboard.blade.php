@extends('lms.common.master')
@section('content')



  <div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
      <h6 class="fw-semibold mb-0">Dashboard</h6>
      <ul class="d-flex align-items-center gap-2">
        <li class="fw-medium">
          <a href="index.html" class="d-flex align-items-center gap-1 hover-text-primary">
            <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
            Dashboard
          </a>
        </li>
      </ul>
    </div>

    <div class="row row-cols-xxxl-5 row-cols-lg-3 row-cols-sm-2 row-cols-1 gy-4">
      <div class="col">
        <div class="card shadow-none border bg-gradient-start-1 h-100">
          <div class="card-body p-20">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
              <div>
                <p class="fw-medium text-primary-light mb-1">Total Leads</p>
                <h6 class="mb-0">{{ number_format($totalLeads) }}</h6>
              </div>
              <div class="w-50-px h-50-px bg-cyan rounded-circle d-flex justify-content-center align-items-center">
                <iconify-icon icon="fluent:people-20-filled" class="text-white text-2xl mb-0"></iconify-icon>
              </div>
            </div>
            <p class="fw-medium text-sm text-primary-light mt-12 mb-0 d-flex align-items-center gap-2">
              <span class="d-inline-flex align-items-center gap-1 text-success-main"><iconify-icon icon="bxs:up-arrow"
                  class="text-xs"></iconify-icon> +5000</span>
              Last 30 days leads
            </p>
          </div>
        </div><!-- card end -->
      </div>
      <div class="col">
        <div class="card shadow-none border bg-gradient-start-2 h-100">
          <div class="card-body p-20">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
              <div>
                <p class="fw-medium text-primary-light mb-1">Total Agents</p>
                <h6 class="mb-0">{{ number_format($totalAgents) }}</h6>
              </div>
              <div class="w-50-px h-50-px bg-purple rounded-circle d-flex justify-content-center align-items-center">
                <iconify-icon icon="bxs:user-badge" class="text-white text-2xl mb-0"></iconify-icon>
              </div>
            </div>
            <p class="fw-medium text-sm text-primary-light mt-12 mb-0 d-flex align-items-center gap-2">
              <span class="d-inline-flex align-items-center gap-1 text-success-main"><iconify-icon icon="bxs:up-arrow"
                  class="text-xs"></iconify-icon> +10</span>
              Last 30 days agents
            </p>
          </div>
        </div><!-- card end -->
      </div>
      <div class="col">
        <div class="card shadow-none border bg-gradient-start-3 h-100">
          <div class="card-body p-20">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
              <div>
                <p class="fw-medium text-primary-light mb-1">Active Leads</p>
                <h6 class="mb-0">{{ number_format($activeLeads) }}</h6>
              </div>
              <div class="w-50-px h-50-px bg-info rounded-circle d-flex justify-content-center align-items-center">
                <iconify-icon icon="mdi:account-star" class="text-white text-2xl mb-0"></iconify-icon>
              </div>
            </div>
            <p class="fw-medium text-sm text-primary-light mt-12 mb-0 d-flex align-items-center gap-2">
              <span class="d-inline-flex align-items-center gap-1 text-success-main"><iconify-icon icon="bxs:up-arrow"
                  class="text-xs"></iconify-icon> +200</span>
              Last 30 days leads
            </p>
          </div>
        </div><!-- card end -->
      </div>
      <div class="col">
        <div class="card shadow-none border bg-gradient-start-4 h-100">
          <div class="card-body p-20">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
              <div>
                <p class="fw-medium text-primary-light mb-1">Converted Leads</p>
                <h6 class="mb-0">{{ number_format($convertedLeads) }}</h6>
              </div>
              <div
                class="w-50-px h-50-px bg-success-main rounded-circle d-flex justify-content-center align-items-center">
                <iconify-icon icon="mdi:handshake" class="text-white text-2xl mb-0"></iconify-icon>
              </div>
            </div>
            <p class="fw-medium text-sm text-primary-light mt-12 mb-0 d-flex align-items-center gap-2">
              <span class="d-inline-flex align-items-center gap-1 text-success-main"><iconify-icon icon="bxs:up-arrow"
                  class="text-xs"></iconify-icon> +50</span>
              Last 30 days converted
            </p>
          </div>
        </div><!-- card end -->
      </div>
      <div class="col">
        <div class="card shadow-none border bg-gradient-start-5 h-100">
          <div class="card-body p-20">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
              <div>
                <p class="fw-medium text-primary-light mb-1">Pending Leads</p>
                <h6 class="mb-0">{{ number_format($pendingLeads) }}</h6>
              </div>
              <div class="w-50-px h-50-px bg-red rounded-circle d-flex justify-content-center align-items-center">
                <iconify-icon icon="mdi:account-clock" class="text-white text-2xl mb-0"></iconify-icon>
              </div>
            </div>
            <p class="fw-medium text-sm text-primary-light mt-12 mb-0 d-flex align-items-center gap-2">
              <span class="d-inline-flex align-items-center gap-1 text-danger-main"><iconify-icon icon="bxs:down-arrow"
                  class="text-xs"></iconify-icon> -20</span>
              Last 30 days pending
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>

  @include('lms.common.footer')
@endsection