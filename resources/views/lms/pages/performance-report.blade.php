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
        <form id="globalFiltersForm" method="GET" action="{{ route('lms.performance.report') }}" class="mb-4" novalidate>
            <div class="collapse" id="performanceFiltersCollapse">
                <div class="card card-body border rounded-3 bg-light-subtle shadow-sm mb-4">
                    <div class="row g-3">
                        @if(auth()->user()->hasAnyRole(['Admin', 'admin']))
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Cluster</label>
                                <select id="filter_cluster" name="cluster_id" class="form-select hierarchy-filter" data-role="Cluster">
                                    <option value="">All Clusters</option>
                                </select>
                            </div>
                        @endif

                        @if(auth()->user()->hasAnyRole(['Admin', 'admin', 'Cluster', 'cluster']))
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Manager</label>
                                <select id="filter_manager" name="manager_id" class="form-select hierarchy-filter" data-role="Manager" {{ auth()->user()->hasAnyRole(['Admin', 'admin']) ? 'disabled' : '' }}>
                                    <option value="">All Managers</option>
                                </select>
                            </div>
                        @endif

                        @if(auth()->user()->hasAnyRole(['Admin', 'admin', 'Cluster', 'cluster', 'Manager', 'manager']))
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Team Leader</label>
                                <select id="filter_teamleader" name="teamleader_id" class="form-select hierarchy-filter" data-role="TeamLeader" {{ auth()->user()->hasAnyRole(['Admin', 'admin', 'Cluster', 'cluster']) ? 'disabled' : '' }}>
                                    <option value="">All Team Leaders</option>
                                </select>
                            </div>
                        @endif

                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Agent</label>
                            <select id="filter_agent" name="agent_id" class="form-select hierarchy-filter" data-role="Agent" {{ auth()->user()->hasAnyRole(['Admin', 'admin', 'Cluster', 'cluster', 'Manager', 'manager']) ? 'disabled' : '' }}>
                                <option value="">All Agents</option>
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


        <div id="performanceReportContent">
            @include('lms.pages.partials.performance-report-content')
        </div>

    </div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // --- Hierarchy Filters Logic ---
        let hierarchyXhr = null;

        function resetSelect($select, placeholder) {
            if ($select.length) {
                $select.html(`<option value="">${placeholder}</option>`);
                $select.prop('disabled', true);
            }
        }

        function populateSelect($select, data, placeholder) {
            if ($select.length) {
                let options = `<option value="">${placeholder}</option>`;
                data.forEach(function (item) {
                    options += `<option value="${item.id}">${item.name}</option>`;
                });
                $select.html(options);
                $select.prop('disabled', false);
            }
        }

        function fetchHierarchyUsers(role, parentId, $select, placeholder) {
            if (!$select.length) return; // if the select doesn't exist for this user role

            if (hierarchyXhr) hierarchyXhr.abort();
            
            resetSelect($select, 'Loading...');

            hierarchyXhr = $.ajax({
                url: '{{ route("lms.api.hierarchy-users") }}',
                method: 'GET',
                data: { role: role, parent_id: parentId || '' },
                success: function (data) {
                    populateSelect($select, data, placeholder);
                },
                error: function (xhr) {
                    if (xhr.statusText !== 'abort') {
                        resetSelect($select, 'Error loading');
                    }
                }
            });
        }

        // Initialize top level
        @if(auth()->user()->hasAnyRole(['Admin', 'admin']))
            fetchHierarchyUsers('Cluster', null, $('#filter_cluster'), 'All Clusters');
        @elseif(auth()->user()->hasAnyRole(['Cluster', 'cluster']))
            fetchHierarchyUsers('Manager', null, $('#filter_manager'), 'All Managers');
        @elseif(auth()->user()->hasAnyRole(['Manager', 'manager']))
            fetchHierarchyUsers('TeamLeader', null, $('#filter_teamleader'), 'All Team Leaders');
        @elseif(auth()->user()->hasAnyRole(['TeamLeader', 'teamleader', 'Supervisor', 'supervisor']))
            fetchHierarchyUsers('Agent', null, $('#filter_agent'), 'All Agents');
        @endif

        // Cascade Events
        $('#filter_cluster').on('change', function () {
            resetSelect($('#filter_manager'), 'All Managers');
            resetSelect($('#filter_teamleader'), 'All Team Leaders');
            resetSelect($('#filter_agent'), 'All Agents');

            if ($(this).val()) {
                fetchHierarchyUsers('Manager', $(this).val(), $('#filter_manager'), 'All Managers');
            }
        });

        $('#filter_manager').on('change', function () {
            resetSelect($('#filter_teamleader'), 'All Team Leaders');
            resetSelect($('#filter_agent'), 'All Agents');

            if ($(this).val()) {
                fetchHierarchyUsers('TeamLeader', $(this).val(), $('#filter_teamleader'), 'All Team Leaders');
            }
        });

        $('#filter_teamleader').on('change', function () {
            resetSelect($('#filter_agent'), 'All Agents');

            if ($(this).val()) {
                fetchHierarchyUsers('Agent', $(this).val(), $('#filter_agent'), 'All Agents');
            }
        });

    });

    // --- Dynamic Content Logic ---
    function fetchAjaxContent(url, data = {}) {
        const container = $('#performanceReportContent');
        container.css('opacity', '0.5'); // Visual loading indicator

        $.ajax({
            url: url,
            type: 'GET',
            data: data,
            success: function (response) {
                container.html(response);
                container.css('opacity', '1');
            },
            error: function () {
                alert('An error occurred while fetching data. Please try again.');
                container.css('opacity', '1');
            }
        });
    }

    // Handle Global Filters Submit
    $(document).on('submit', '#globalFiltersForm', function (e) {
        e.preventDefault();
        fetchAjaxContent($(this).attr('action'), $(this).serialize());
    });

    // Handle Pagination Clicks inside the dynamic content
    $(document).on('click', '#performanceReportContent .pagination a', function (e) {
        e.preventDefault();
        fetchAjaxContent($(this).attr('href'), $('#globalFiltersForm').serialize());
    });
</script>
@endsection