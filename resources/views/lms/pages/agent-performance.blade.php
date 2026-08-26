@extends('lms.common.master')

@section('content')


    <style>
        .table-responsive::-webkit-scrollbar {
            height: 8px;
            width: 8px;
        }

        .table-responsive::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }

        .table-responsive::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>

    <div class="dashboard-main-body">

        <!-- 1. Page Header -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
            <div>
                <h5 class="fw-bold mb-1 text-dark">
                    Agent Performance <span class="text-secondary-light fw-medium mx-1">|</span> <span class="text-primary-main">{{ $user->name ?? 'Unknown' }}</span>
                </h5>
                <p class="text-secondary-light mb-0 fs-sm">Detailed view of dialer performance for this agent</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('lms.performance.report') }}"
                    class="btn btn-outline-secondary d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:arrow-left-outline" class="text-xl"></iconify-icon> Back
                </a>
                <button class="btn btn-outline-primary d-flex align-items-center gap-2" type="button"
                    data-bs-toggle="collapse" data-bs-target="#performanceFiltersCollapse" aria-expanded="false"
                    aria-controls="performanceFiltersCollapse">
                    <iconify-icon icon="lucide:filter" class="text-xl"></iconify-icon> Filter
                </button>
                <button class="btn btn-primary d-flex align-items-center gap-2">
                    <iconify-icon icon="file-icons:microsoft-excel" class="text-xl"></iconify-icon> Export
                </button>
            </div>
        </div>

        <!-- Global Filters -->
        <form method="GET" action="#" class="mb-4" novalidate>
            <div class="collapse" id="performanceFiltersCollapse">
                <div class="card card-body border rounded-3 bg-light-subtle shadow-sm mb-4">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Date Range</label>
                            <select name="date_preset" class="form-select">
                                <option value="today" {{ ($datePreset ?? '') == 'today' ? 'selected' : '' }}>Today</option>
                                <option value="yesterday" {{ ($datePreset ?? '') == 'yesterday' ? 'selected' : '' }}>Yesterday
                                </option>
                                <option value="this_week" {{ ($datePreset ?? '') == 'this_week' ? 'selected' : '' }}>This Week
                                </option>
                                <option value="this_month" {{ ($datePreset ?? '') == 'this_month' ? 'selected' : '' }}>This
                                    Month</option>
                                <option value="custom" {{ ($datePreset ?? '') == 'custom' ? 'selected' : '' }}>Custom Range
                                </option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Date From</label>
                            <input type="date" name="date_from" class="form-control" value="{{ $dateFrom ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Date To</label>
                            <input type="date" name="date_to" class="form-control" value="{{ $dateTo ?? '' }}">
                        </div>
                        <div class="col-12 mt-3 d-flex justify-content-end gap-2">
                            <a href="#" class="btn btn-outline-secondary">Clear</a>
                            <button type="button" class="btn btn-primary">Apply Filters</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- 2. Agent Summary (Compact & Aligned) -->
        <div class="card shadow-sm border mb-24 bg-white rounded-3">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-4">
                    <!-- Left Profile Info -->
                    <div class="d-flex align-items-center gap-3">
                        <div class="w-48-px h-48-px rounded-circle bg-primary-transparent text-primary-main d-flex justify-content-center align-items-center fs-4 fw-bold shadow-sm">
                            {{ strtoupper(substr($user->name ?? 'A', 0, 1)) }}
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h6 class="mb-0 fw-bold text-dark">{{ $user->name ?? 'Unknown' }}</h6>
                                <span class="badge bg-success-transparent text-success-main rounded-pill px-2 py-1 fs-xs fw-medium d-flex align-items-center gap-1">
                                    <span class="w-6-px h-6-px rounded-circle bg-success-main d-inline-block"></span> Active
                                </span>
                            </div>
                            <span class="text-secondary-light fs-sm fw-medium d-flex align-items-center gap-1">
                                <iconify-icon icon="solar:user-id-linear"></iconify-icon> Agent #{{ $user->details->employee_id ?? ($user->id ?? '') }}
                            </span>
                        </div>
                    </div>
                    
                    <!-- Right Meta Info -->
                    <div class="d-flex flex-wrap align-items-center gap-4 gap-md-5">
                        @if(optional(optional($user->details)->cluster)->name)
                        <div class="d-flex align-items-center gap-2">
                            <div class="w-32-px h-32-px rounded bg-light-subtle d-flex align-items-center justify-content-center text-primary-main border">
                                <iconify-icon icon="solar:user-bold"></iconify-icon>
                            </div>
                            <div>
                                <p class="text-xs text-secondary-light mb-0 text-uppercase fw-semibold tracking-wide">Cluster</p>
                                <h6 class="mb-0 fw-bold text-dark fs-sm text-truncate" style="max-width: 140px;" title="{{ $user->details->cluster->name }}">{{ $user->details->cluster->name }}</h6>
                            </div>
                        </div>
                        @endif

                        @if(optional(optional($user->details)->manager)->name)
                        <div class="d-flex align-items-center gap-2">
                            <div class="w-32-px h-32-px rounded bg-light-subtle d-flex align-items-center justify-content-center text-info-main border">
                                <iconify-icon icon="solar:user-hands-bold"></iconify-icon>
                            </div>
                            <div>
                                <p class="text-xs text-secondary-light mb-0 text-uppercase fw-semibold tracking-wide">Manager</p>
                                <h6 class="mb-0 fw-bold text-dark fs-sm text-truncate" style="max-width: 140px;" title="{{ $user->details->manager->name }}">{{ $user->details->manager->name }}</h6>
                            </div>
                        </div>
                        @endif
                        
                        @if(optional(optional($user->details)->teamleader)->name)
                        <div class="d-flex align-items-center gap-2">
                            <div class="w-32-px h-32-px rounded bg-light-subtle d-flex align-items-center justify-content-center text-warning-main border">
                                <iconify-icon icon="solar:users-group-rounded-bold"></iconify-icon>
                            </div>
                            <div>
                                <p class="text-xs text-secondary-light mb-0 text-uppercase fw-semibold tracking-wide">Team Leader</p>
                                <h6 class="mb-0 fw-bold text-dark fs-sm text-truncate" style="max-width: 140px;" title="{{ $user->details->teamleader->name }}">{{ $user->details->teamleader->name }}</h6>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. KPI Cards -->
        <div class="row row-cols-xxxl-4 row-cols-lg-4 row-cols-sm-2 row-cols-1 gy-4 mb-24">
            <div class="col">
                <div class="card shadow-none border bg-gradient-start-1 h-100">
                    <div class="card-body p-20">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div>
                                <p class="fw-medium text-primary-light mb-1">Total Calls</p>
                                <h6 class="mb-0">{{ $user->total_calls ?? 0 }}</h6>
                            </div>
                            <div
                                class="w-50-px h-50-px bg-cyan rounded-circle d-flex justify-content-center align-items-center">
                                <iconify-icon icon="solar:phone-calling-rounded-bold"
                                    class="text-white text-2xl mb-0"></iconify-icon>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-none border bg-gradient-start-2 h-100">
                    <div class="card-body p-20">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div>
                                <p class="fw-medium text-primary-light mb-1">Answered Calls</p>
                                <h6 class="mb-0">{{ $user->answered_calls ?? 0 }}</h6>
                            </div>
                            <div
                                class="w-50-px h-50-px bg-success-main rounded-circle d-flex justify-content-center align-items-center">
                                <iconify-icon icon="solar:phone-bold" class="text-white text-2xl mb-0"></iconify-icon>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-none border bg-gradient-start-3 h-100">
                    <div class="card-body p-20">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div>
                                <p class="fw-medium text-primary-light mb-1">Answer Rate</p>
                                <h6 class="mb-0">{{ number_format($user->answer_rate ?? 0, 2) }}%</h6>
                            </div>
                            <div
                                class="w-50-px h-50-px bg-purple rounded-circle d-flex justify-content-center align-items-center">
                                <iconify-icon icon="solar:graph-up-bold" class="text-white text-2xl mb-0"></iconify-icon>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- 
            <div class="col">
                <div class="card shadow-none border bg-gradient-start-2 h-100">
                    <div class="card-body p-20">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div>
                                <p class="fw-medium text-primary-light mb-1">Calls &ge; 2 Minutes</p>
                                <h6 class="mb-0">{{ $user->calls_gt_2min ?? 0 }}</h6>
                            </div>
                            <div
                                class="w-50-px h-50-px bg-success rounded-circle d-flex justify-content-center align-items-center">
                                <iconify-icon icon="solar:cup-star-bold" class="text-white text-2xl mb-0"></iconify-icon>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            --}}
            <div class="col">
                <div class="card shadow-none border bg-gradient-start-4 h-100">
                    <div class="card-body p-20">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div>
                                <p class="fw-medium text-primary-light mb-1">Avg. Duration</p>
                                <h6 class="mb-0">{{ gmdate('i:s', $user->avg_duration ?? 0) }}</h6>
                            </div>
                            <div
                                class="w-50-px h-50-px bg-info rounded-circle d-flex justify-content-center align-items-center">
                                <iconify-icon icon="solar:clock-circle-bold"
                                    class="text-white text-2xl mb-0"></iconify-icon>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-none border bg-gradient-start-5 h-100">
                    <div class="card-body p-20">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div>
                                <p class="fw-medium text-primary-light mb-1">Login Hours</p>
                                <h6 class="mb-0">{{ floor(($user->login_sec ?? 0) / 3600) . 'h ' . gmdate('i\m', $user->login_sec ?? 0) }}</h6>
                            </div>
                            <div
                                class="w-50-px h-50-px bg-primary rounded-circle d-flex justify-content-center align-items-center">
                                <iconify-icon icon="solar:user-circle-bold" class="text-white text-2xl mb-0"></iconify-icon>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-none border bg-gradient-start-1 h-100">
                    <div class="card-body p-20">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div>
                                <p class="fw-medium text-primary-light mb-1">Pause Time</p>
                                <h6 class="mb-0">{{ floor(($user->pause_sec ?? 0) / 3600) . 'h ' . gmdate('i\m', $user->pause_sec ?? 0) }}</h6>
                            </div>
                            <div
                                class="w-50-px h-50-px bg-danger rounded-circle d-flex justify-content-center align-items-center">
                                <iconify-icon icon="solar:pause-circle-bold"
                                    class="text-white text-2xl mb-0"></iconify-icon>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-none border bg-gradient-start-3 h-100">
                    <div class="card-body p-20">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div>
                                <p class="fw-medium text-primary-light mb-1">Total Talk Time</p>
                                <h6 class="mb-0">{{ floor(($user->talk_sec ?? 0) / 3600) . 'h ' . gmdate('i\m', $user->talk_sec ?? 0) }}</h6>
                            </div>
                            <div
                                class="w-50-px h-50-px bg-warning rounded-circle d-flex justify-content-center align-items-center">
                                <iconify-icon icon="solar:history-bold" class="text-white text-2xl mb-0"></iconify-icon>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row gy-4 mb-24">
            <!-- 4. Calling Activity -->
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header border-bottom">
                        <h5 class="card-title mb-0">Calling Activity</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            @forelse($callingActivity as $activity)
                                @php
                                    $startHour = \Carbon\Carbon::createFromFormat('H', $activity->hour);
                                    $endHour = (clone $startHour)->addHour();
                                    $hourFormatted = $startHour->format('hA') . '-' . $endHour->format('hA');
                                @endphp
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span class="text-secondary-light">{{ $hourFormatted }}</span>
                                    <span class="fw-medium">{{ $activity->total_calls }} calls</span>
                                </li>
                            @empty
                                <li class="list-group-item px-0 text-center text-secondary-light border-bottom-0 pt-4">
                                    No activity found for this period.
                                </li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>

            <!-- 5. Call Summary -->
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header border-bottom">
                        <h5 class="card-title mb-0">Call Summary</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-secondary-light">Total Calls</span>
                                <span class="fw-medium">{{ $user->total_calls ?? 0 }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-secondary-light">Answered Calls</span>
                                <span class="fw-medium text-success-main">{{ $user->answered_calls ?? 0 }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-secondary-light">Not Answered</span>
                                <span class="fw-medium text-danger-main">{{ ($user->total_calls ?? 0) - ($user->answered_calls ?? 0) }}</span>
                            </li>
                            {{--
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-secondary-light">Calls &ge; 2 Min</span>
                                <span class="fw-medium">{{ $user->calls_gt_2min ?? 0 }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-secondary-light">Calls &lt; 2 Min</span>
                                <span class="fw-medium">{{ ($user->total_calls ?? 0) - ($user->calls_gt_2min ?? 0) }}</span>
                            </li>
                            --}}
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-secondary-light">Avg. Duration</span>
                                <span class="fw-medium">{{ gmdate('i:s', $user->avg_duration ?? 0) }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 border-bottom-0">
                                <span class="text-secondary-light">Total Talk Time</span>
                                <span class="fw-medium text-primary-main">{{ floor(($user->talk_sec ?? 0) / 3600) . 'h ' . gmdate('i\m', $user->talk_sec ?? 0) }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- 6. Productivity Summary -->
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header border-bottom">
                        <h5 class="card-title mb-0">Productivity</h5>
                    </div>
                    <div class="card-body">
                        @php
                            $availableSec = max(0, ($user->login_sec ?? 0) - ($user->talk_sec ?? 0) - ($user->pause_sec ?? 0));
                            $answeredPerHour = ($user->login_sec ?? 0) > 0 ? number_format(($user->answered_calls ?? 0) / (($user->login_sec ?? 0) / 3600), 1) : 0;
                        @endphp
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-secondary-light">Login Hours</span>
                                <span class="fw-medium">{{ floor(($user->login_sec ?? 0) / 3600) . 'h ' . gmdate('i\m', $user->login_sec ?? 0) }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-secondary-light">Pause Time</span>
                                <span class="fw-medium text-warning-main">{{ floor(($user->pause_sec ?? 0) / 3600) . 'h ' . gmdate('i\m', $user->pause_sec ?? 0) }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-secondary-light">Available Time</span>
                                <span class="fw-medium text-success-main">{{ floor($availableSec / 3600) . 'h ' . gmdate('i\m', $availableSec) }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-secondary-light">Talk Time</span>
                                <span class="fw-medium text-primary-main">{{ floor(($user->talk_sec ?? 0) / 3600) . 'h ' . gmdate('i\m', $user->talk_sec ?? 0) }}</span>
                            </li>
                            {{-- <li class="list-group-item d-flex justify-content-between align-items-center px-0 mt-3 pt-3 border-top">
                                <span class="text-secondary-light">Calls / Hour</span>
                                <span class="fw-bold fs-5">{{ number_format($user->calls_per_hour ?? 0, 1) }}</span>
                            </li> --}}
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 mt-3 pt-3 border-top border-bottom-0">
                                <span class="text-secondary-light">Answered / Hour</span>
                                <span class="fw-bold fs-5 text-success-main">{{ $answeredPerHour }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- 7. Daily Performance Table -->
        <div class="row gy-4 mb-24">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">Daily Performance</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table striped-table mb-0 text-nowrap">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th class="text-end">Calls</th>
                                        <th class="text-end">Answered</th>
                                        <th class="text-end">Answer Rate</th>
                                        {{-- <th class="text-end">Calls &ge; 2 Mins</th> --}}
                                        <th class="text-end">Avg. Duration</th>
                                        <th class="text-end">Login Hours</th>
                                        <th class="text-end">Pause</th>
                                        <th class="text-end">Talk Time</th>
                                        {{-- <th class="text-end">Calls / Hour</th> --}}
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($dailyPerformance as $day)
                                        @php
                                            $dayAnswerRate = $day->total_calls > 0 ? ($day->answered_calls / $day->total_calls) * 100 : 0;
                                            $dayAvgDuration = $day->answered_calls > 0 ? ($day->talk_sec / $day->answered_calls) : 0;
                                            $dayLoginSec = $day->pause_sec + $day->wait_sec + $day->talk_sec + $day->dispo_sec + $day->dead_sec;
                                            $dayCallsPerHour = $dayLoginSec > 0 ? ($day->total_calls / ($dayLoginSec / 3600)) : 0;
                                        @endphp
                                        <tr>
                                            <td class="fw-medium">{{ \Carbon\Carbon::parse($day->date)->format('M d, Y') }}</td>
                                            <td class="text-end">{{ $day->total_calls }}</td>
                                            <td class="text-end">{{ $day->answered_calls }}</td>
                                            <td class="text-end">
                                                <span class="{{ $dayAnswerRate >= 65 ? 'bg-success-focus text-success-main' : ($dayAnswerRate >= 50 ? 'bg-warning-focus text-warning-main' : 'bg-danger-focus text-danger-main') }} px-24 py-4 rounded-pill text-sm">
                                                    {{ number_format($dayAnswerRate, 2) }}%
                                                </span>
                                            </td>
                                            {{-- <td class="text-end">{{ $day->calls_gt_2min }}</td> --}}
                                            <td class="text-end">{{ gmdate('i:s', $dayAvgDuration) }}</td>
                                            <td class="text-end">{{ floor($dayLoginSec / 3600) . 'h ' . gmdate('i\m', $dayLoginSec) }}</td>
                                            <td class="text-end">{{ floor($day->pause_sec / 3600) . 'h ' . gmdate('i\m', $day->pause_sec) }}</td>
                                            <td class="text-end fw-medium text-dark">{{ floor($day->talk_sec / 3600) . 'h ' . gmdate('i\m', $day->talk_sec) }}</td>
                                            {{-- <td class="text-end">{{ number_format($dayCallsPerHour, 1) }}</td> --}}
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center py-4 text-secondary-light">No daily performance data available for the selected period.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 8. Call History -->
        <div class="row gy-4">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <h5 class="card-title mb-0">Call History</h5>
                        <form method="GET" action="{{ route('lms.agent.performance', $user->id) }}" class="d-flex align-items-center gap-2">
                            <!-- Preserve date filters -->
                            @if(request()->has('date_preset')) <input type="hidden" name="date_preset" value="{{ request('date_preset') }}"> @endif
                            @if(request()->has('date_from')) <input type="hidden" name="date_from" value="{{ request('date_from') }}"> @endif
                            @if(request()->has('date_to')) <input type="hidden" name="date_to" value="{{ request('date_to') }}"> @endif
                            
                            <select name="call_status" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                                <option value="">All Statuses</option>
                                <option value="Answered" {{ request('call_status') == 'Answered' ? 'selected' : '' }}>Answered</option>
                                <option value="No Answer" {{ request('call_status') == 'No Answer' ? 'selected' : '' }}>No Answer</option>
                            </select>

                            <select name="call_sort" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                                <option value="desc" {{ request('call_sort') == 'desc' ? 'selected' : '' }}>Newest First</option>
                                <option value="asc" {{ request('call_sort') == 'asc' ? 'selected' : '' }}>Oldest First</option>
                            </select>
                        </form>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table striped-table mb-0 text-nowrap">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>List ID</th>
                                        <th>Call Status</th>
                                        @hasanyrole('Admin|Manager|Cluster|TeamLeader|Supervisor|admin|manager|cluster|teamleader|supervisor')
                                        <th>Duration</th>
                                        <th class="text-center">Recording</th>
                                        @endhasanyrole
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($callHistory as $call)
                                        @php
                                            $callTime = \Carbon\Carbon::parse($call->event_time);
                                            $isAnswered = $call->is_answered;
                                            
                                            if ($isAnswered) {
                                                $badgeClass = 'bg-success-transparent text-success-main';
                                                $statusText = 'Answered';
                                            } else {
                                                $badgeClass = 'bg-warning-transparent text-warning-main';
                                                $statusText = $call->status ?? 'No Answer';
                                            }
                                        @endphp
                                        <tr>
                                            <td>{{ $callTime->format('h:i A') }} <span class="text-secondary-light fs-xs d-block">{{ $callTime->format('M d, Y') }}</span></td>
                                            <td class="text-primary-main fw-medium">List #{{ $call->list_id }}</td>
                                        
                                            <td><span class="badge {{ $badgeClass }} rounded-pill px-24 py-4 text-sm">{{ $statusText }}</span></td>
                                            @hasanyrole('Admin|Manager|Cluster|TeamLeader|Supervisor|admin|manager|cluster|teamleader|supervisor')
                                            <td>{{ gmdate('i:s', $call->talk_sec ?? 0) }}</td>
                                            <td class="text-center">
                                                @if($isAnswered && ($call->talk_sec > 0))
                                                    @if(!empty($call->recording_filename))
                                                        @php
                                                            $recUrl = rtrim(env('RECORDING_DOMAIN', ''), '/') . '/RECORDINGS/' . $callTime->format('M-Y') . '/' . $callTime->format('d-M-Y') . '/' . $call->recording_filename . '-all.mp3';
                                                        @endphp
                                                        <button type="button" class="btn btn-primary-transparent btn-sm rounded-circle w-40-px h-40-px d-inline-flex justify-content-center align-items-center play-recording-btn" 
                                                                data-recording-url="{{ $recUrl }}" 
                                                                data-lead-id="List #{{ $call->list_id }}" 
                                                                data-call-date="{{ $callTime->format('M d, Y') }}"
                                                                title="Play Recording">
                                                            <iconify-icon icon="solar:play-circle-bold" class="text-xl"></iconify-icon>
                                                        </button>
                                                    @else
                                                        <span class="text-secondary-light" title="No Recording Found"> No recording</span>
                                                    @endif
                                                @else
                                                    <span class="text-secondary-light">No recording</span>
                                                @endif
                                            </td>
                                            @endhasanyrole
                                        </tr>
                                    @empty
                                        <tr>
                                            @hasanyrole('Admin|Manager|Cluster|TeamLeader|Supervisor|admin|manager|cluster|teamleader|supervisor')
                                            <td colspan="5" class="text-center py-4 text-secondary-light">No call history available for the selected period.</td>
                                            @else
                                            <td colspan="3" class="text-center py-4 text-secondary-light">No call history available for the selected period.</td>
                                            @endhasanyrole
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-24">
                            {{ $callHistory->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modern Recording Player Modal -->
    <div class="modal fade" id="recordingModal" tabindex="-1" aria-labelledby="recordingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg bg-white" style="border-radius: 1.25rem; overflow: hidden;">
                <div class="modal-header border-bottom-0 pt-4 px-4 pb-0">
                    <div class="w-100 text-center position-relative">
                        <h5 class="modal-title fw-bold text-dark mb-1" id="recordingModalLabel">Call Recording</h5>
                        <p class="text-secondary-light fs-sm mb-0" id="recordingSubtitle">Loading...</p>
                        <button type="button" class="btn-close position-absolute top-0 end-0 mt-1 me-1" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                
                <div class="modal-body px-4 py-4 text-center">
                    <!-- Hidden Audio Element -->
                    <audio id="recordingPlayer" class="d-none" preload="metadata">
                        <source src="" type="audio/mpeg">
                    </audio>

                    <!-- Play Controls -->
                    <div class="d-flex align-items-center justify-content-center gap-4 mb-4 mt-3">
                        <button type="button" class="btn btn-light rounded-circle d-flex align-items-center justify-content-center text-secondary-light transition-2 shadow-sm border-0" id="skipBackBtn" title="-10s" style="width: 48px; height: 48px;">
                            <iconify-icon icon="solar:rewind-10-seconds-back-bold" class="text-2xl"></iconify-icon>
                        </button>
                        
                        <button type="button" class="btn btn-primary rounded-circle d-flex align-items-center justify-content-center shadow-lg transition-2 border-0" id="playPauseBtn" style="width: 72px; height: 72px; background: linear-gradient(135deg, var(--bs-primary), #0056b3);">
                            <iconify-icon icon="solar:play-bold" class="text-white" style="font-size: 32px;" id="playIcon"></iconify-icon>
                        </button>

                        <button type="button" class="btn btn-light rounded-circle d-flex align-items-center justify-content-center text-secondary-light transition-2 shadow-sm border-0" id="skipForwardBtn" title="+10s" style="width: 48px; height: 48px;">
                            <iconify-icon icon="solar:forward-10-seconds-bold" class="text-2xl"></iconify-icon>
                        </button>
                    </div>

                    <!-- Progress Bar & Waveform -->
                    <div class="mb-4 mt-2 px-3">
                        <!-- Simulated Waveform -->
                        <div class="d-flex align-items-end justify-content-between gap-1 mb-2" id="waveformContainer" style="height: 40px;">
                            @for($i=0; $i<48; $i++)
                                <div class="bg-primary rounded-pill wave-bar" data-index="{{ $i }}" style="flex-grow: 1; height: {{ rand(20, 100) }}%; opacity: 0.15; transition: opacity 0.2s;"></div>
                            @endfor
                        </div>

                        <div class="d-flex align-items-center justify-content-between text-secondary-light fs-sm fw-medium mb-2">
                            <span id="currentTimeDisplay">00:00</span>
                            <span id="durationDisplay">00:00</span>
                        </div>
                        <div class="progress bg-primary-transparent rounded-pill" style="cursor: pointer; overflow: visible; height: 6px;" id="progressBarContainer">
                            <div class="progress-bar bg-primary rounded-pill position-relative" id="progressBar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                <span class="position-absolute top-50 start-100 translate-middle bg-white rounded-circle shadow border border-2 border-primary" style="cursor: grab; width: 16px; height: 16px;"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Speed & Volume -->
                    <div class="d-flex align-items-center justify-content-between mt-4 px-3">
                        <div class="d-flex align-items-center gap-2">
                            <iconify-icon icon="solar:volume-loud-bold" class="text-secondary-light text-lg" id="volumeIcon"></iconify-icon>
                            <input type="range" class="form-range" id="volumeControl" min="0" max="1" step="0.05" value="1" style="width: 90px; height: 4px;">
                        </div>
                        
                        <div class="bg-light rounded-pill p-1 d-flex gap-1">
                            <button class="btn btn-sm btn-primary text-white rounded-pill px-3 py-1 fs-xs fw-medium shadow-sm speed-btn" data-speed="1">1x</button>
                            <button class="btn btn-sm btn-light text-secondary-light rounded-pill px-3 py-1 fs-xs fw-medium speed-btn border-0" data-speed="1.5">1.5x</button>
                            <button class="btn btn-sm btn-light text-secondary-light rounded-pill px-3 py-1 fs-xs fw-medium speed-btn border-0" data-speed="2">2x</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const recordingModal = document.getElementById('recordingModal');
        if (!recordingModal) return;
        
        const audio = document.getElementById('recordingPlayer');
        const playPauseBtn = document.getElementById('playPauseBtn');
        const playIcon = document.getElementById('playIcon');
        const skipBackBtn = document.getElementById('skipBackBtn');
        const skipForwardBtn = document.getElementById('skipForwardBtn');
        const progressBarContainer = document.getElementById('progressBarContainer');
        const progressBar = document.getElementById('progressBar');
        const currentTimeDisplay = document.getElementById('currentTimeDisplay');
        const durationDisplay = document.getElementById('durationDisplay');
        const volumeControl = document.getElementById('volumeControl');
        const volumeIcon = document.getElementById('volumeIcon');
        const speedBtns = document.querySelectorAll('.speed-btn');
        const subtitle = document.getElementById('recordingSubtitle');
        const waveBars = document.querySelectorAll('.wave-bar');
        const totalBars = waveBars.length;
        
        let isDragging = false;

        function formatTime(seconds) {
            if (isNaN(seconds) || seconds < 0) return "00:00";
            const m = Math.floor(seconds / 60);
            const s = Math.floor(seconds % 60);
            return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
        }

        function updatePlayState() {
            if (audio.paused) {
                playIcon.setAttribute('icon', 'solar:play-bold');
            } else {
                playIcon.setAttribute('icon', 'solar:pause-bold');
            }
        }

        // Play/Pause toggle
        playPauseBtn.addEventListener('click', () => {
            if (audio.paused) {
                audio.play().catch(e => console.log('Playback prevented', e));
            } else {
                audio.pause();
            }
        });

        audio.addEventListener('play', updatePlayState);
        audio.addEventListener('pause', updatePlayState);

        // Time updates
        audio.addEventListener('timeupdate', () => {
            if(!isDragging) {
                const percent = (audio.currentTime / audio.duration) * 100 || 0;
                progressBar.style.width = percent + '%';
                currentTimeDisplay.textContent = formatTime(audio.currentTime);
                
                // Update waveform opacity
                const activeIndex = Math.floor((audio.currentTime / audio.duration) * totalBars);
                waveBars.forEach((bar, index) => {
                    bar.style.opacity = index <= activeIndex ? '1' : '0.15';
                });
            }
        });

        audio.addEventListener('loadedmetadata', () => {
            durationDisplay.textContent = formatTime(audio.duration);
        });
        
        // Skip buttons
        skipBackBtn.addEventListener('click', () => {
            audio.currentTime = Math.max(0, audio.currentTime - 10);
        });
        
        skipForwardBtn.addEventListener('click', () => {
            audio.currentTime = Math.min(audio.duration, audio.currentTime + 10);
        });

        // Progress Bar Click & Drag
        function updateProgressFromEvent(e) {
            const rect = progressBarContainer.getBoundingClientRect();
            let pos = (e.clientX - rect.left) / rect.width;
            pos = Math.max(0, Math.min(1, pos));
            progressBar.style.width = (pos * 100) + '%';
            if (audio.duration) {
                audio.currentTime = pos * audio.duration;
            }
        }

        progressBarContainer.addEventListener('mousedown', (e) => {
            isDragging = true;
            updateProgressFromEvent(e);
        });

        document.addEventListener('mousemove', (e) => {
            if(isDragging) updateProgressFromEvent(e);
        });

        document.addEventListener('mouseup', () => {
            isDragging = false;
        });

        // Volume
        volumeControl.addEventListener('input', (e) => {
            audio.volume = e.target.value;
            if(audio.volume === 0) {
                volumeIcon.setAttribute('icon', 'solar:volume-cross-bold');
            } else if (audio.volume < 0.5) {
                volumeIcon.setAttribute('icon', 'solar:volume-small-bold');
            } else {
                volumeIcon.setAttribute('icon', 'solar:volume-loud-bold');
            }
        });

        // Playback Speed
        speedBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                speedBtns.forEach(b => {
                    b.classList.remove('btn-primary', 'text-white', 'shadow-sm');
                    b.classList.add('btn-light', 'text-secondary-light', 'border-0');
                });
                this.classList.remove('btn-light', 'text-secondary-light', 'border-0');
                this.classList.add('btn-primary', 'text-white', 'shadow-sm');
                audio.playbackRate = parseFloat(this.getAttribute('data-speed'));
            });
        });

        // Open Modal Event
        document.querySelectorAll('.play-recording-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const url = this.getAttribute('data-recording-url');
                const leadId = this.getAttribute('data-lead-id');
                const date = this.getAttribute('data-call-date');
                
                subtitle.textContent = leadId + ' • ' + date;
                audio.src = url;
                
                const bsModal = new bootstrap.Modal(recordingModal);
                bsModal.show();
                
                recordingModal.addEventListener('shown.bs.modal', function onShown() {
                    audio.play().catch(e => console.log('Autoplay prevented:', e));
                    recordingModal.removeEventListener('shown.bs.modal', onShown);
                });
            });
        });
        
        recordingModal.addEventListener('hidden.bs.modal', function () {
            audio.pause();
            audio.currentTime = 0;
            progressBar.style.width = '0%';
            waveBars.forEach(b => b.style.opacity = '0.15');
            playIcon.setAttribute('icon', 'solar:play-bold');
            
            // Reset playback speed to 1x
            audio.playbackRate = 1;
            speedBtns.forEach(b => {
                b.classList.remove('btn-primary', 'text-white', 'shadow-sm');
                b.classList.add('btn-light', 'text-secondary-light', 'border-0');
            });
            const defaultSpeedBtn = document.querySelector('.speed-btn[data-speed="1"]');
            if (defaultSpeedBtn) {
                defaultSpeedBtn.classList.remove('btn-light', 'text-secondary-light', 'border-0');
                defaultSpeedBtn.classList.add('btn-primary', 'text-white', 'shadow-sm');
            }
        });
    });
</script>
@endsection