@extends('lms.common.master')
@section('content')



  <div class="dashboard-main-body">

    <div class="row row-cols-xxxl-5 row-cols-lg-3 row-cols-sm-2 row-cols-1 gy-4">
      <div class="col">
        <div class="card shadow-none border bg-gradient-start-1 h-100">
          <div class="card-body p-20">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
              <div>
                <p class="fw-medium text-primary-light mb-1">Total Leads</p>
                <h6 class="mb-0">{{ indian_number($stats['totalLeads']) }}</h6>
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
      @role('Admin|Manager|Cluster|TeamLeader')
      <div class="col">
        <div class="card shadow-none border bg-gradient-start-2 h-100">
          <div class="card-body p-20">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
              <div>
                <p class="fw-medium text-primary-light mb-1">Total Agents</p>
                <h6 class="mb-0">{{ indian_number($stats['totalAgents']) }}</h6>
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
      @endrole
      {{-- <div class="col">
        <div class="card shadow-none border bg-gradient-start-3 h-100">
          <div class="card-body p-20">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
              <div>
                <p class="fw-medium text-primary-light mb-1">Active Leads</p>
                <h6 class="mb-0">{{ indian_number($stats['activeLeads']) }}</h6>
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
                <h6 class="mb-0">{{ indian_number($stats['convertedLeads']) }}</h6>
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
                <h6 class="mb-0">{{ indian_number($stats['pendingLeads']) }}</h6>
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
      </div> --}}
      @foreach($widgets->where('chart_type', 'card') as $widget)
        @php
          $cardStyleIndex = ($loop->iteration % 5) ?: 5;
          $cardIconColors = ['bg-cyan', 'bg-purple', 'bg-info', 'bg-success-main', 'bg-red'];
          $cardIconColor = $cardIconColors[($loop->iteration - 1) % count($cardIconColors)];
        @endphp

        <div class="col">
          <div class="card shadow-none border bg-gradient-start-{{ $cardStyleIndex }} h-100">
            <div class="card-body p-20">
              <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                  <p class="fw-medium text-primary-light mb-1">{{ $widget->title }}</p>
                  <h6 class="mb-0" id="card-value-{{ $widget->id }}">
                    <span class="spinner-border spinner-border-sm text-primary" role="status">
                      <span class="visually-hidden">Loading...</span>
                    </span>
                  </h6>
                </div>
                <div class="w-50-px h-50-px {{ $cardIconColor }} rounded-circle d-flex justify-content-center align-items-center">
                  <iconify-icon icon="mdi:chart-box" class="text-white text-2xl mb-0"></iconify-icon>
                </div>
              </div>
              <p class="fw-medium text-sm text-primary-light mt-12 mb-0 d-flex align-items-center gap-2">
                <span class="d-inline-flex align-items-center gap-1 text-success-main">
                  <iconify-icon icon="bxs:up-arrow" class="text-xs"></iconify-icon>
                </span>
                {{ ucfirst($widget->aggregate ?? 'count') }} value
              </p>
            </div>
          </div>
        </div>
      @endforeach
    </div>
    <div class="row gy-4 mt-2">

      {{-- ── Bar chart widgets — styled exactly like the demo "Generated Content" card ── --}}
      @foreach($widgets->where('chart_type', 'bar') as $widget)
        <div class="col-xxl-6 col-lg-{{ $widget->width ?? 6 }}">
          <div class="card h-100">
            <div class="card-body">

              {{-- Row 1: Title + select filter (matches demo) --}}
              <div class="d-flex align-items-center flex-wrap gap-2 justify-content-between">
                <h6 class="mb-2 fw-bold text-lg mb-0">{{ $widget->title }}</h6>
                <div class="d-flex align-items-center gap-2">
                  <select id="bar-filter-{{ $widget->id }}" class="form-select form-select-sm w-auto bg-base border text-secondary-light radius-8">
                    @php
                      $groupByMap = ['day' => 'Day', 'week' => 'Weekly', 'month' => 'Monthly', 'year' => 'Yearly'];
                      $activeLabel = $groupByMap[$widget->group_by] ?? 'Monthly';
                    @endphp
                    <option @selected($activeLabel === 'Day')>Day</option>
                    <option @selected($activeLabel === 'Weekly')>Weekly</option>
                    <option @selected($activeLabel === 'Monthly')>Monthly</option>
                    <option @selected($activeLabel === 'Yearly')>Yearly</option>
                  </select>
                  <div class="dropdown">
                    <a href="javascript:void(0)" data-bs-toggle="dropdown">
                      <iconify-icon icon="mdi:dots-vertical"></iconify-icon>
                    </a>
                    <ul class="dropdown-menu">
                      <li>
                        <a class="dropdown-item" href="{{ route('lms.dashboard.widgets.edit', $widget->id) }}">Edit</a>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>

              {{-- Row 2: Legend dots (matches demo ul layout) --}}
              <ul class="d-flex flex-wrap align-items-center mt-3 gap-3">
                <li class="d-flex align-items-center gap-2">
                  <span class="w-12-px h-12-px rounded-circle bg-primary-600"></span>
                  <span class="text-secondary-light text-sm fw-semibold" id="bar-legend-1-{{ $widget->id }}">
                    {{ $widget->title }}
                  </span>
                </li>
              </ul>

              {{-- Row 3: Chart (matches demo mt-40 + margin-16-minus) --}}
              <div class="mt-40">
                <div id="chart-wrap-{{ $widget->id }}" style="min-height:{{ $widget->height ?? 264 }}px; position:relative;">
                  <div id="chart-skeleton-{{ $widget->id }}"
                    style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%);background-size:200% 100%;animation:shimmer 1.4s infinite;">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#aaa" stroke-width="1.5">
                      <rect x="3" y="12" width="4" height="9"/><rect x="9" y="7" width="4" height="14"/><rect x="15" y="4" width="4" height="17"/>
                    </svg>
                  </div>
                  <div id="chart{{ $widget->id }}" class="apexcharts-tooltip-style-1 margin-16-minus"></div>
                </div>
              </div>

            </div>
          </div>
        </div>
      @endforeach


      {{-- ── All other chart widgets (line, area, pie, doughnut, etc.) ── --}}
      @foreach($widgets as $widget)
        @continue(in_array($widget->chart_type, ['card', 'bar']))

        <div class="col-lg-{{ $widget->width ?? 6 }}">
          <div class="card shadow-none border h-100">

            <div class="card-header d-flex justify-content-between align-items-center">
              <h6 class="mb-0">{{ $widget->title }}</h6>
              <div class="dropdown">
                <a href="javascript:void(0)" data-bs-toggle="dropdown">
                  <iconify-icon icon="mdi:dots-vertical"></iconify-icon>
                </a>
                <ul class="dropdown-menu">
                  <li>
                    <a class="dropdown-item" href="{{ route('lms.dashboard.widgets.edit', $widget->id) }}">Edit</a>
                  </li>
                </ul>
              </div>
            </div>

            <div class="card-body">
              {{-- Chart: skeleton shimmer then ApexCharts --}}
              <div id="chart-wrap-{{ $widget->id }}" style="min-height:{{ $widget->height ?? 350 }}px; position:relative;">
                <div id="chart-skeleton-{{ $widget->id }}"
                  style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%);background-size:200% 100%;animation:shimmer 1.4s infinite;">
                  <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#aaa" stroke-width="1.5">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12" />
                  </svg>
                </div>
                <div id="chart{{ $widget->id }}" class="apexcharts-tooltip-style-1"></div>
              </div>
            </div>

          </div>
        </div>

      @endforeach

    </div>
  </div>

  @include('lms.common.footer')
@endsection
@section('scripts')

  <style>
    @keyframes shimmer {
      0% {
        background-position: 200% 0;
      }

      100% {
        background-position: -200% 0;
      }
    }
  </style>

  <script>
    document.addEventListener('DOMContentLoaded', function () {

      // ── chart instance registry (widgetId → ApexCharts instance) ───────────
      var chartInstances = {};

      // ── helpers ────────────────────────────────────────────────────────────

      function showShimmer(id) {
        var wrap = document.getElementById('chart-wrap-' + id);
        if (!wrap) return;
        // ── Remove ALL existing skeletons (including ones baked into HTML) ──
        wrap.querySelectorAll('[id^="chart-skeleton-"]').forEach(function(sk) { sk.remove(); });
        // ── Clear the chart div ────────────────────────────────────────────
        var el = document.getElementById('chart' + id);
        if (el) el.innerHTML = '';
        // ── Add a fresh shimmer ────────────────────────────────────────────
        var sk = document.createElement('div');
        sk.id = 'chart-skeleton-' + id;
        sk.style.cssText = 'position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%);background-size:200% 100%;animation:shimmer 1.4s infinite;';
        sk.innerHTML = '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#aaa" stroke-width="1.5"><rect x="3" y="12" width="4" height="9"/><rect x="9" y="7" width="4" height="14"/><rect x="15" y="4" width="4" height="17"/></svg>';
        wrap.appendChild(sk);
      }

      function hideSkeleton(id) {
        var wrap = document.getElementById('chart-wrap-' + id);
        if (!wrap) return;
        wrap.querySelectorAll('[id^="chart-skeleton-"]').forEach(function(sk) {
          sk.style.transition = 'opacity .3s';
          sk.style.opacity = '0';
          setTimeout(function() { if (sk.parentNode) sk.parentNode.removeChild(sk); }, 320);
        });
      }

      function showError(id, msg) {
        hideSkeleton(id);
        var wrap = document.getElementById('chart-wrap-' + id);
        if (wrap) wrap.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100 text-danger py-5"><small><i class="ri-error-warning-line me-1"></i>' + msg + '</small></div>';
      }

      // ── core fetch + render ────────────────────────────────────────────────

      function loadChart(widgetId, chartType, baseUrl, period) {
        var url = baseUrl + (period ? '?period=' + period : '');

        // Destroy existing chart instance if present
        if (chartInstances[widgetId]) {
          try { chartInstances[widgetId].destroy(); } catch(e) {}
          delete chartInstances[widgetId];
        }

        showShimmer(widgetId);

        fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
          .then(function (res) {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
          })
          .then(function (data) {
            if (chartType === 'card') {
              hideSkeleton(widgetId);
              var valueEl = document.getElementById('card-value-' + widgetId);
              if (valueEl) {
                var val = (data.value !== undefined) ? Number(data.value).toLocaleString('en-IN') : '-';
                valueEl.textContent = val;
              }
            } else {
              hideSkeleton(widgetId);
              var el = document.getElementById('chart' + widgetId);
              if (!el) return;
              delete data.type;

              // ── Inject JS formatters that can't be serialized from PHP ──────
              if (chartType === 'bar') {
                if (!data.yaxis) data.yaxis = {};
                if (!data.yaxis.labels) data.yaxis.labels = {};
                data.yaxis.labels.formatter = function(v) {
                  return v >= 1000 ? (v / 1000).toFixed(0) + 'k' : v;
                };
                if (!data.tooltip) data.tooltip = {};
                if (!data.tooltip.y) data.tooltip.y = {};
                data.tooltip.y.formatter = function(v) {
                  return Number(v).toLocaleString('en-IN');
                };
              }

              var instance = new ApexCharts(el, data);
              instance.render();
              chartInstances[widgetId] = instance;
            }
          })
          .catch(function (err) {
            if (chartType === 'card') {
              var valueEl = document.getElementById('card-value-' + widgetId);
              if (valueEl) valueEl.innerHTML = '<span class="text-danger text-sm">Failed</span>';
            } else {
              showError(widgetId, 'Failed to load chart');
            }
            console.error('Widget ' + widgetId + ' error:', err);
          });
      }

      // ── initial load for every widget ──────────────────────────────────────

      @foreach($widgets as $widget)
        (function () {
          var widgetId  = {{ $widget->id }};
          var chartType = '{{ $widget->chart_type }}';
          var baseUrl   = '{{ route("lms.dashboard.widget.data", $widget->id) }}';

          // Initial load: NO ?period= sent — server uses the widget's configured group_by
          loadChart(widgetId, chartType, baseUrl, null);

          // ── period filter change (bar widgets only) ───────────────────────
          var periodMap = { 'Day': 'day', 'Weekly': 'week', 'Monthly': 'month', 'Yearly': 'year' };
          var sel = document.getElementById('bar-filter-{{ $widget->id }}');
          if (sel) {
            sel.addEventListener('change', function () {
              var period = periodMap[this.value] || 'month';
              loadChart(widgetId, chartType, baseUrl, period);
            });
          }
        })();
      @endforeach

    });
  </script>


@endsection
