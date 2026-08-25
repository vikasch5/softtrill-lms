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
            <button class="btn btn-outline-primary d-flex align-items-center gap-2" type="button" data-bs-toggle="collapse" data-bs-target="#performanceFiltersCollapse" aria-expanded="false" aria-controls="performanceFiltersCollapse">
                <iconify-icon icon="lucide:filter" class="text-xl"></iconify-icon> Filter
            </button>
            <button class="btn btn-primary d-flex align-items-center gap-2">
                <iconify-icon icon="file-icons:microsoft-excel" class="text-xl"></iconify-icon> Export
            </button>
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
                        <label class="form-label fw-semibold">Date From</label>
                        <input type="date" name="date_from" class="form-control" value="2026-08-01">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Date To</label>
                        <input type="date" name="date_to" class="form-control" value="2026-08-25">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Campaign</label>
                        <select name="campaign" class="form-select">
                            {{-- <option value="">All Campaigns</option> --}}
                            <option value="Course Sales">Course Sales</option>
                            <option value="Admission">Admission</option>
                            <option value="Renewal">Renewal</option>
                        </select>
                    </div>
                    
                    <div class="col-12 mt-3 d-flex justify-content-end gap-2">
                        <a href="#" class="btn btn-outline-secondary">Clear</a>
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
                            <h6 class="mb-0">12,840</h6>
                        </div>
                        <div class="w-50-px h-50-px bg-cyan rounded-circle d-flex justify-content-center align-items-center">
                            <iconify-icon icon="solar:phone-calling-rounded-bold" class="text-white text-2xl mb-0"></iconify-icon>
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
                            <h6 class="mb-0">7,942</h6>
                        </div>
                        <div class="w-50-px h-50-px bg-success-main rounded-circle d-flex justify-content-center align-items-center">
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
                            <h6 class="mb-0">61.85%</h6>
                        </div>
                        <div class="w-50-px h-50-px bg-purple rounded-circle d-flex justify-content-center align-items-center">
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
                        <div>
                            <p class="fw-medium text-primary-light mb-1">Avg. Duration</p>
                            <h6 class="mb-0">04:32</h6>
                        </div>
                        <div class="w-50-px h-50-px bg-info rounded-circle d-flex justify-content-center align-items-center">
                            <iconify-icon icon="solar:clock-circle-bold" class="text-white text-2xl mb-0"></iconify-icon>
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
                            <h6 class="mb-0">42</h6>
                        </div>
                        <div class="w-50-px h-50-px bg-primary rounded-circle d-flex justify-content-center align-items-center">
                            <iconify-icon icon="solar:users-group-rounded-bold" class="text-white text-2xl mb-0"></iconify-icon>
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
                            <h6 class="mb-0">428h 32m</h6>
                        </div>
                        <div class="w-50-px h-50-px bg-warning rounded-circle d-flex justify-content-center align-items-center">
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
                                    <th class="text-end">Calls</th>
                                    <th class="text-end">Answered</th>
                                    <th class="text-end">Answer Rate</th>
                                    <th class="text-end">Avg. Duration</th>
                                    <th class="text-end">Login Hours</th>
                                    <th class="text-end">Pause</th>
                                    <th class="text-end">Talk Time</th>
                                    <th class="text-end">Calls / Hour</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Row 1 -->
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1">
                                                <h6 class="text-md mb-0 fw-normal">Rahul Kumar</h6>
                                                <span class="text-sm text-secondary-light fw-normal">Agent #1001</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column text-sm text-secondary-light">
                                            <span><strong>TL:</strong> Suresh Kumar</span>
                                            <span><strong>Mgr:</strong> Amit Sharma</span>
                                        </div>
                                    </td>
                                    {{-- <td>Course Sales</td> --}}
                                    <td class="text-end">420</td>
                                    <td class="text-end">286</td>
                                    <td class="text-end">
                                        <span class="bg-success-focus text-success-main px-24 py-4 rounded-pill text-sm">68.10%</span>
                                    </td>
                                    <td class="text-end">04:33</td>
                                    <td class="text-end">08:14</td>
                                    <td class="text-end">01:02</td>
                                    <td class="text-end fw-medium text-dark">21:42</td>
                                    <td class="text-end">51.0</td>
                                    <td class="text-center">
                                        <a href="{{ route('lms.agent.performance', ['id' => 1001]) }}" class="btn btn-primary-transparent btn-sm px-12 py-4 rounded-pill fw-medium d-inline-flex align-items-center gap-1">
                                            View Details <iconify-icon icon="solar:arrow-right-outline"></iconify-icon>
                                        </a>
                                    </td>
                                </tr>
                                
                                <!-- Row 2 -->
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1">
                                                <h6 class="text-md mb-0 fw-normal">Amit Sharma</h6>
                                                <span class="text-sm text-secondary-light fw-normal">Agent #1002</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column text-sm text-secondary-light">
                                            <span><strong>TL:</strong> Suresh Kumar</span>
                                            <span><strong>Mgr:</strong> Amit Sharma</span>
                                        </div>
                                    </td>
                                    {{-- <td>Course Sales</td> --}}
                                    <td class="text-end">395</td>
                                    <td class="text-end">271</td>
                                    <td class="text-end">
                                        <span class="bg-success-focus text-success-main px-24 py-4 rounded-pill text-sm">68.61%</span>
                                    </td>
                                    <td class="text-end">04:29</td>
                                    <td class="text-end">08:02</td>
                                    <td class="text-end">00:48</td>
                                    <td class="text-end fw-medium text-dark">20:18</td>
                                    <td class="text-end">49.2</td>
                                    <td class="text-center">
                                        <a href="{{ route('lms.agent.performance', ['id' => 1002]) }}" class="btn btn-primary-transparent btn-sm px-12 py-4 rounded-pill fw-medium d-inline-flex align-items-center gap-1">
                                            View Details <iconify-icon icon="solar:arrow-right-outline"></iconify-icon>
                                        </a>
                                    </td>
                                </tr>

                                <!-- Row 3 -->
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1">
                                                <h6 class="text-md mb-0 fw-normal">Neha Singh</h6>
                                                <span class="text-sm text-secondary-light fw-normal">Agent #1003</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column text-sm text-secondary-light">
                                            <span><strong>TL:</strong> Raj Kumar</span>
                                            <span><strong>Mgr:</strong> Priya Sharma</span>
                                        </div>
                                    </td>
                                    {{-- <td>Admission</td> --}}
                                    <td class="text-end">388</td>
                                    <td class="text-end">169</td>
                                    <td class="text-end">
                                        <span class="bg-warning-focus text-warning-main px-24 py-4 rounded-pill text-sm">64.18%</span>
                                    </td>
                                    <td class="text-end">04:32</td>
                                    <td class="text-end">07:52</td>
                                    <td class="text-end">00:56</td>
                                    <td class="text-end fw-medium text-dark">18:51</td>
                                    <td class="text-end">49.3</td>
                                    <td class="text-center">
                                        <a href="{{ route('lms.agent.performance', ['id' => 1003]) }}" class="btn btn-primary-transparent btn-sm px-12 py-4 rounded-pill fw-medium d-inline-flex align-items-center gap-1">
                                            View Details <iconify-icon icon="solar:arrow-right-outline"></iconify-icon>
                                        </a>
                                    </td>
                                </tr>

                                <!-- Row 4 -->
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1">
                                                <h6 class="text-md mb-0 fw-normal">Rohit Kumar</h6>
                                                <span class="text-sm text-secondary-light fw-normal">Agent #1004</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column text-sm text-secondary-light">
                                            <span><strong>TL:</strong> Raj Kumar</span>
                                            <span><strong>Mgr:</strong> Priya Sharma</span>
                                        </div>
                                    </td>
                                    {{-- <td>Admission</td> --}}
                                    <td class="text-end">410</td>
                                    <td class="text-end">151</td>
                                    <td class="text-end">
                                        <span class="bg-warning-focus text-warning-main px-24 py-4 rounded-pill text-sm">58.05%</span>
                                    </td>
                                    <td class="text-end">03:52</td>
                                    <td class="text-end">08:05</td>
                                    <td class="text-end">01:10</td>
                                    <td class="text-end fw-medium text-dark">15:22</td>
                                    <td class="text-end">50.7</td>
                                    <td class="text-center">
                                        <a href="{{ route('lms.agent.performance', ['id' => 1004]) }}" class="btn btn-primary-transparent btn-sm px-12 py-4 rounded-pill fw-medium d-inline-flex align-items-center gap-1">
                                            View Details <iconify-icon icon="solar:arrow-right-outline"></iconify-icon>
                                        </a>
                                    </td>
                                </tr>

                                <!-- Row 5 -->
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1">
                                                <h6 class="text-md mb-0 fw-normal">Pooja Verma</h6>
                                                <span class="text-sm text-secondary-light fw-normal">Agent #1005</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column text-sm text-secondary-light">
                                            <span><strong>TL:</strong> Suresh Kumar</span>
                                            <span><strong>Mgr:</strong> Amit Sharma</span>
                                        </div>
                                    </td>
                                    {{-- <td>Renewal</td> --}}
                                    <td class="text-end">365</td>
                                    <td class="text-end">198</td>
                                    <td class="text-end">
                                        <span class="bg-danger-focus text-danger-main px-24 py-4 rounded-pill text-sm">54.25%</span>
                                    </td>
                                    <td class="text-end">03:52</td>
                                    <td class="text-end">07:45</td>
                                    <td class="text-end">00:52</td>
                                    <td class="text-end fw-medium text-dark">12:47</td>
                                    <td class="text-end">47.1</td>
                                    <td class="text-center">
                                        <a href="{{ route('lms.agent.performance', ['id' => 1005]) }}" class="btn btn-primary-transparent btn-sm px-12 py-4 rounded-pill fw-medium d-inline-flex align-items-center gap-1">
                                            View Details <iconify-icon icon="solar:arrow-right-outline"></iconify-icon>
                                        </a>
                                    </td>
                                </tr>

                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination mock -->
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
