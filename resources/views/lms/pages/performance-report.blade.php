@extends('lms.common.master')

@section('content')

    <style>
        /* Sleek Custom Scrollbar for horizontal scrolling tables */
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

        <!-- Page Header -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
            <div>
                <h4 class="fw-bold mb-1">Agent Performance</h4>
                <p class="text-secondary-light mb-0">Monitor agent calling activity and productivity</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <!-- Reuse existing button styles if applicable -->
                <button class="btn btn-outline-primary d-flex align-items-center gap-2" type="button"
                    data-bs-toggle="collapse" data-bs-target="#performanceFiltersCollapse" aria-expanded="false"
                    aria-controls="performanceFiltersCollapse">
                    <iconify-icon icon="lucide:filter" class="text-xl"></iconify-icon> Filter
                </button>
                {{-- <button class="btn btn-primary d-flex align-items-center gap-2">
                    <iconify-icon icon="file-icons:microsoft-excel" class="text-xl"></iconify-icon> Export
                </button> --}}
            </div>
        </div>

        <!-- Global Filters -->
        <form method="GET" action="{{ route('lms.performance.report') }}" class="mb-4" novalidate>
            <div class="collapse" id="performanceFiltersCollapse">
                <div class="card card-body border rounded-3 bg-light-subtle shadow-sm mb-4">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Agent</label>
                            <select name="agent_id" class="form-select">
                                <option value="">All Agents</option>
                                <option value="1001">Rahul Kumar</option>
                                <option value="1002">Amit Sharma</option>
                                <option value="1003">Neha Singh</option>
                                <option value="1004">Rohit Kumar</option>
                                <option value="1005">Pooja Verma</option>
                            </select>
                        </div>
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
                            <input type="date" name="date_from" class="form-control" value="">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Date To</label>
                            <input type="date" name="date_to" class="form-control" value="">
                        </div>

                        <div class="col-12 mt-3 d-flex justify-content-end gap-2">
                            <a href="{{ route('lms.performance.report') }}" class="btn btn-outline-secondary">Clear</a>
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- KPI Cards -->
        <div class="row row-cols-xxxl-4 row-cols-lg-4 row-cols-sm-2 row-cols-1 gy-4 mb-24">

            <!-- Card 1 -->
            <div class="col">
                <div class="card shadow-none border bg-gradient-start-1 h-100">
                    <div class="card-body p-20">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div>
                                <p class="fw-medium text-primary-light mb-1">Total Calls</p>
                                <h6 class="mb-0">{{ number_format($aggregate['total_calls'] ?? 0) }}</h6>
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

            <!-- Card 2 -->
            <div class="col">
                <div class="card shadow-none border bg-gradient-start-2 h-100">
                    <div class="card-body p-20">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div>
                                <p class="fw-medium text-primary-light mb-1">Answered Calls</p>
                                <h6 class="mb-0">{{ number_format($aggregate['answered_calls'] ?? 0) }}</h6>
                            </div>
                            <div
                                class="w-50-px h-50-px bg-success-main rounded-circle d-flex justify-content-center align-items-center">
                                <iconify-icon icon="solar:phone-bold" class="text-white text-2xl mb-0"></iconify-icon>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 3 -->
            <div class="col">
                <div class="card shadow-none border bg-gradient-start-3 h-100">
                    <div class="card-body p-20">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div>
                                <p class="fw-medium text-primary-light mb-1">Answer Rate</p>
                                <h6 class="mb-0">
                                    {{ number_format($aggregate['answer_rate'] ?? 0, 2) }}%
                                </h6>
                            </div>
                            <div
                                class="w-50-px h-50-px bg-purple rounded-circle d-flex justify-content-center align-items-center">
                                <iconify-icon icon="solar:graph-up-bold" class="text-white text-2xl mb-0"></iconify-icon>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 4 -->
            <div class="col">
                <div class="card shadow-none border bg-gradient-start-4 h-100">
                    <div class="card-body p-20">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            @php
                                $totalTalk = $aggregate['talk_sec'] ?? 0;
                                $avgDurSec = $aggregate['avg_duration'] ?? 0;
                            @endphp
                            <div>
                                <p class="fw-medium text-primary-light mb-1">Avg. Duration</p>
                                <h6 class="mb-0">{{ gmdate('i:s', $avgDurSec) }}</h6>
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

            <!-- Card 5 -->
            <div class="col">
                <div class="card shadow-none border bg-gradient-start-5 h-100">
                    <div class="card-body p-20">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div>
                                <p class="fw-medium text-primary-light mb-1">Total Agents</p>
                                <h6 class="mb-0">{{ $aggregate['total_agents'] ?? $users->total() }}</h6>
                            </div>
                            <div
                                class="w-50-px h-50-px bg-primary rounded-circle d-flex justify-content-center align-items-center">
                                <iconify-icon icon="solar:users-group-rounded-bold"
                                    class="text-white text-2xl mb-0"></iconify-icon>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Card 7 -->
            <div class="col">
                <div class="card shadow-none border bg-gradient-start-3 h-100">
                    <div class="card-body p-20">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div>
                                <p class="fw-medium text-primary-light mb-1">Total Talk Time</p>
                                <h6 class="mb-0">{{ floor($totalTalk / 3600) . 'h ' . gmdate('i\m', $totalTalk) }}</h6>
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

        <!-- Agent Performance Table -->
        <div class="row gy-4">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">Agent Performance</h5>
                    </div>

                    <div class="card-body">


                        <div class="table-responsive">
                            <table class="table striped-table mb-0 text-nowrap">
                                <thead>
                                    <tr>
                                        <th>Agent Name</th>
                                        <th>Parents</th>
                                        <th class="text-end">Total Calls</th>
                                        <th class="text-end">Answered</th>
                                        <th class="text-end">Answer Rate</th>
                                        <th class="text-end">Avg. Duration</th>
                                        <th class="text-end">Login Hours</th>
                                        <th class="text-end">Pause</th>
                                        <th class="text-end">Talk Time</th>
                                        {{-- <th class="text-end">Calls / Hour</th> --}}
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($users as $user)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-grow-1">
                                                        <h6 class="text-md mb-0 fw-normal">{{ $user->name }}</h6>
                                                        <span class="text-sm text-secondary-light fw-normal">Agent
                                                            #{{ $user->details->employee_id ?? $user->id }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column text-sm text-secondary-light">
                                                    <span><strong>TL:</strong>
                                                        {{ $user->details->teamleader->name ?? 'N/A' }}</span>
                                                    <span><strong>Mgr:</strong>
                                                        {{ $user->details->manager->name ?? 'N/A' }}</span>
                                                </div>
                                            </td>
                                            <td class="text-end fw-bold">{{ $user->total_calls }}</td>
                                            <td class="text-end fw-bold text-success-main">{{ $user->answered_calls }}</td>
                                            <td class="text-end">
                                                <span
                                                    class="bg-secondary-focus text-secondary-main px-24 py-4 rounded-pill text-sm">{{ number_format($user->answer_rate, 2) }}%</span>
                                            </td>
                                            <td class="text-end">{{ gmdate('i:s', $user->avg_duration) }}</td>
                                            <td class="text-end">
                                                {{ floor($user->login_sec / 3600) . 'h ' . gmdate('i\m', $user->login_sec) }}
                                            </td>
                                            <td class="text-end">
                                                {{ floor($user->pause_sec / 3600) . 'h ' . gmdate('i\m', $user->pause_sec) }}
                                            </td>
                                            <td class="text-end fw-medium text-dark">
                                                {{ floor($user->talk_sec / 3600) . 'h ' . gmdate('i\m', $user->talk_sec) }}</td>
                                            {{-- <td class="text-end">{{ number_format($user->calls_per_hour, 1) }}</td> --}}
                                            <td class="text-center">
                                                <a href="{{ route('lms.agent.performance', ['id' => $user->id]) }}"
                                                    class="w-32-px h-32-px bg-primary-light text-primary-main rounded-circle d-inline-flex align-items-center justify-content-center"
                                                    title="View Details">
                                                    <iconify-icon icon="solar:eye-bold" class="text-xl"></iconify-icon>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="11" class="text-center">No agents found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-24">
                            {{ $users->links() }}
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection