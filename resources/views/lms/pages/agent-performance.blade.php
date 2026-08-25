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
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="{{ route('lms.performance.report') }}" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1">
                    <iconify-icon icon="solar:arrow-left-outline"></iconify-icon> Back
                </a>
                <h4 class="fw-bold mb-0">Agent Performance: Rahul Kumar</h4>
            </div>
            <p class="text-secondary-light mb-0">Detailed view of dialer performance for this agent</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-outline-primary d-flex align-items-center gap-2" type="button" data-bs-toggle="collapse" data-bs-target="#performanceFiltersCollapse" aria-expanded="false" aria-controls="performanceFiltersCollapse">
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
                            <option value="today">Today</option>
                            <option value="yesterday">Yesterday</option>
                            <option value="this_week">This Week</option>
                            <option value="this_month">This Month</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Date From</label>
                        <input type="date" name="date_from" class="form-control" value="2026-08-01">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Date To</label>
                        <input type="date" name="date_to" class="form-control" value="2026-08-25">
                    </div>
                    <div class="col-12 mt-3 d-flex justify-content-end gap-2">
                        <a href="#" class="btn btn-outline-secondary">Clear</a>
                        <button type="button" class="btn btn-primary">Apply Filters</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- 2. Agent Summary -->
    <div class="card mb-24">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="w-64-px h-64-px rounded-circle bg-primary-transparent text-primary-main d-flex justify-content-center align-items-center fs-2 fw-semibold">
                        RK
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="mb-0 fw-semibold">Rahul Kumar</h5>
                            <span class="badge bg-success-transparent text-success-main rounded-pill px-2 py-1 fs-xs">Active</span>
                        </div>
                        <span class="text-secondary-light">Agent #1001</span>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-4">
                    <div>
                        <p class="text-sm text-secondary-light mb-1">Campaign</p>
                        <h6 class="mb-0 fw-medium">Course Sales</h6>
                    </div>
                    <div class="border-start ps-4">
                        <p class="text-sm text-secondary-light mb-1">Manager</p>
                        <h6 class="mb-0 fw-medium">Amit Sharma</h6>
                    </div>
                    <div class="border-start ps-4">
                        <p class="text-sm text-secondary-light mb-1">Team Leader</p>
                        <h6 class="mb-0 fw-medium">Suresh Kumar</h6>
                    </div>
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
                            <h6 class="mb-0">420</h6>
                        </div>
                        <div class="w-50-px h-50-px bg-cyan rounded-circle d-flex justify-content-center align-items-center">
                            <iconify-icon icon="solar:phone-calling-rounded-bold" class="text-white text-2xl mb-0"></iconify-icon>
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
                            <h6 class="mb-0">286</h6>
                        </div>
                        <div class="w-50-px h-50-px bg-success-main rounded-circle d-flex justify-content-center align-items-center">
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
                            <h6 class="mb-0">68.10%</h6>
                        </div>
                        <div class="w-50-px h-50-px bg-purple rounded-circle d-flex justify-content-center align-items-center">
                            <iconify-icon icon="solar:graph-up-bold" class="text-white text-2xl mb-0"></iconify-icon>
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
                            <p class="fw-medium text-primary-light mb-1">Calls &ge; 2 Minutes</p>
                            <h6 class="mb-0">182</h6>
                        </div>
                        <div class="w-50-px h-50-px bg-success rounded-circle d-flex justify-content-center align-items-center">
                            <iconify-icon icon="solar:cup-star-bold" class="text-white text-2xl mb-0"></iconify-icon>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-none border bg-gradient-start-4 h-100">
                <div class="card-body p-20">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div>
                            <p class="fw-medium text-primary-light mb-1">Avg. Duration</p>
                            <h6 class="mb-0">04:33</h6>
                        </div>
                        <div class="w-50-px h-50-px bg-info rounded-circle d-flex justify-content-center align-items-center">
                            <iconify-icon icon="solar:clock-circle-bold" class="text-white text-2xl mb-0"></iconify-icon>
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
                            <h6 class="mb-0">08h 14m</h6>
                        </div>
                        <div class="w-50-px h-50-px bg-primary rounded-circle d-flex justify-content-center align-items-center">
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
                            <h6 class="mb-0">01h 02m</h6>
                        </div>
                        <div class="w-50-px h-50-px bg-danger rounded-circle d-flex justify-content-center align-items-center">
                            <iconify-icon icon="solar:pause-circle-bold" class="text-white text-2xl mb-0"></iconify-icon>
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
                            <h6 class="mb-0">21h 42m</h6>
                        </div>
                        <div class="w-50-px h-50-px bg-warning rounded-circle d-flex justify-content-center align-items-center">
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
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-secondary-light">09 AM</span>
                            <span class="fw-medium">62 calls</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-secondary-light">10 AM</span>
                            <span class="fw-medium">84 calls</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-secondary-light">11 AM</span>
                            <span class="fw-medium">96 calls</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-secondary-light">12 PM</span>
                            <span class="fw-medium">38 calls</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-secondary-light">01 PM</span>
                            <span class="fw-medium">48 calls</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-secondary-light">02 PM</span>
                            <span class="fw-medium">72 calls</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-secondary-light">03 PM</span>
                            <span class="fw-medium">50 calls</span>
                        </li>
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
                            <span class="fw-medium">420</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-secondary-light">Answered Calls</span>
                            <span class="fw-medium text-success-main">286</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-secondary-light">Not Answered</span>
                            <span class="fw-medium text-danger-main">134</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-secondary-light">Calls &ge; 2 Min</span>
                            <span class="fw-medium">182</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-secondary-light">Calls &lt; 2 Min</span>
                            <span class="fw-medium">104</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-secondary-light">Avg. Duration</span>
                            <span class="fw-medium">04:33</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-secondary-light">Longest Call</span>
                            <span class="fw-medium">18:42</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 border-bottom-0">
                            <span class="text-secondary-light">Total Talk Time</span>
                            <span class="fw-medium text-primary-main">21h 42m</span>
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
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-secondary-light">Login Hours</span>
                            <span class="fw-medium">08h 14m</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-secondary-light">Pause Time</span>
                            <span class="fw-medium text-warning-main">01h 02m</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-secondary-light">Available Time</span>
                            <span class="fw-medium text-success-main">07h 12m</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-secondary-light">Talk Time</span>
                            <span class="fw-medium text-primary-main">21h 42m</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 mt-3 pt-3 border-top">
                            <span class="text-secondary-light">Calls / Hour</span>
                            <span class="fw-bold fs-5">51.0</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 border-bottom-0">
                            <span class="text-secondary-light">Answered / Hour</span>
                            <span class="fw-bold fs-5 text-success-main">34.7</span>
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
                                    <th class="text-end">Calls &ge; 2 Mins</th>
                                    <th class="text-end">Avg. Duration</th>
                                    <th class="text-end">Login Hours</th>
                                    <th class="text-end">Pause</th>
                                    <th class="text-end">Talk Time</th>
                                    <th class="text-end">Calls / Hour</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="fw-medium">Aug 25, 2026</td>
                                    <td class="text-end">420</td>
                                    <td class="text-end">286</td>
                                    <td class="text-end"><span class="bg-success-focus text-success-main px-24 py-4 rounded-pill text-sm">68.10%</span></td>
                                    <td class="text-end">182</td>
                                    <td class="text-end">04:33</td>
                                    <td class="text-end">08h 14m</td>
                                    <td class="text-end">01h 02m</td>
                                    <td class="text-end fw-medium text-dark">21h 42m</td>
                                    <td class="text-end">51.0</td>
                                </tr>
                                <tr>
                                    <td class="fw-medium">Aug 24, 2026</td>
                                    <td class="text-end">395</td>
                                    <td class="text-end">271</td>
                                    <td class="text-end"><span class="bg-success-focus text-success-main px-24 py-4 rounded-pill text-sm">68.61%</span></td>
                                    <td class="text-end">176</td>
                                    <td class="text-end">04:29</td>
                                    <td class="text-end">08h 02m</td>
                                    <td class="text-end">00h 48m</td>
                                    <td class="text-end fw-medium text-dark">20h 18m</td>
                                    <td class="text-end">49.2</td>
                                </tr>
                                <tr>
                                    <td class="fw-medium">Aug 23, 2026</td>
                                    <td class="text-end">388</td>
                                    <td class="text-end">249</td>
                                    <td class="text-end"><span class="bg-warning-focus text-warning-main px-24 py-4 rounded-pill text-sm">64.18%</span></td>
                                    <td class="text-end">169</td>
                                    <td class="text-end">04:32</td>
                                    <td class="text-end">07h 52m</td>
                                    <td class="text-end">00h 56m</td>
                                    <td class="text-end fw-medium text-dark">18h 51m</td>
                                    <td class="text-end">49.3</td>
                                </tr>
                                <tr>
                                    <td class="fw-medium">Aug 22, 2026</td>
                                    <td class="text-end">410</td>
                                    <td class="text-end">238</td>
                                    <td class="text-end"><span class="bg-warning-focus text-warning-main px-24 py-4 rounded-pill text-sm">58.05%</span></td>
                                    <td class="text-end">151</td>
                                    <td class="text-end">03:52</td>
                                    <td class="text-end">08h 05m</td>
                                    <td class="text-end">01h 10m</td>
                                    <td class="text-end fw-medium text-dark">15h 22m</td>
                                    <td class="text-end">50.7</td>
                                </tr>
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
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">Call History</h5>
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm w-auto">
                            <option value="">All Statuses</option>
                            <option value="Answered">Answered</option>
                            <option value="No Answer">No Answer</option>
                            <option value="Busy">Busy</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table striped-table mb-0 text-nowrap">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Lead</th>
                                    <th>Campaign</th>
                                    <th>Call Status</th>
                                    <th>Duration</th>
                                    <th class="text-center">Recording</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>11:42 AM</td>
                                    <td class="text-primary-main fw-medium">Lead #10284</td>
                                    <td>Course Sales</td>
                                    <td><span class="badge bg-success-transparent text-success-main rounded-pill px-24 py-4 text-sm">Answered</span></td>
                                    <td>05:32</td>
                                    <td class="text-center">
                                        <button class="btn btn-primary-transparent btn-sm rounded-circle w-32-px h-32-px d-inline-flex justify-content-center align-items-center">
                                            <iconify-icon icon="solar:play-circle-bold"></iconify-icon>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>11:36 AM</td>
                                    <td class="text-primary-main fw-medium">Lead #10281</td>
                                    <td>Course Sales</td>
                                    <td><span class="badge bg-success-transparent text-success-main rounded-pill px-24 py-4 text-sm">Answered</span></td>
                                    <td>03:18</td>
                                    <td class="text-center">
                                        <button class="btn btn-primary-transparent btn-sm rounded-circle w-32-px h-32-px d-inline-flex justify-content-center align-items-center">
                                            <iconify-icon icon="solar:play-circle-bold"></iconify-icon>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>11:29 AM</td>
                                    <td class="text-primary-main fw-medium">Lead #10276</td>
                                    <td>Course Sales</td>
                                    <td><span class="badge bg-warning-transparent text-warning-main rounded-pill px-24 py-4 text-sm">No Answer</span></td>
                                    <td>00:00</td>
                                    <td class="text-center">
                                        <span class="text-secondary-light">—</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>11:22 AM</td>
                                    <td class="text-primary-main fw-medium">Lead #10265</td>
                                    <td>Course Sales</td>
                                    <td><span class="badge bg-success-transparent text-success-main rounded-pill px-24 py-4 text-sm">Answered</span></td>
                                    <td>06:41</td>
                                    <td class="text-center">
                                        <button class="btn btn-primary-transparent btn-sm rounded-circle w-32-px h-32-px d-inline-flex justify-content-center align-items-center">
                                            <iconify-icon icon="solar:play-circle-bold"></iconify-icon>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>11:15 AM</td>
                                    <td class="text-primary-main fw-medium">Lead #10254</td>
                                    <td>Course Sales</td>
                                    <td><span class="badge bg-danger-transparent text-danger-main rounded-pill px-24 py-4 text-sm">Busy</span></td>
                                    <td>00:00</td>
                                    <td class="text-center">
                                        <span class="text-secondary-light">—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="d-flex align-items-center justify-content-between mt-24">
                        <span class="text-secondary-light">Showing 1 to 5 of 42 entries</span>
                        <ul class="pagination mb-0">
                            <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                            <li class="page-item"><a class="page-link" href="#">2</a></li>
                            <li class="page-item"><a class="page-link" href="#">3</a></li>
                            <li class="page-item"><a class="page-link" href="#">Next</a></li>
                        </ul>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

@endsection
