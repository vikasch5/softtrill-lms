@extends('lms.common.master')

@section('content')

    <div class="dashboard-main-body">

        <div class="card">

            <div class="card-header d-flex justify-content-between align-items-center">

                <div>
                    <h5 class="mb-1">
                        Leads
                    </h5>

                    <small class="text-muted">
                        Manage imported leads
                    </small>
                </div>

                <div class="d-flex gap-2 flex-wrap">

                    @role('Admin|Manager|Cluster|TeamLeader')
                    <button type="button" class="btn btn-outline-dark" id="openAssignLeadModal">
                        <i class="ri-user-settings-line"></i>
                        Assign Lead
                    </button>
                    @endrole

                    @role('Admin|Manager|Cluster')
                    <a href="{{ route('lms.leads') }}" class="btn btn-primary">

                        <i class="ri-arrow-left-line"></i>
                        All Leads

                    </a>


                    <a href="{{ route('lms.lead.import') }}" class="btn btn-primary">

                        <i class="ri-upload-cloud-line"></i>
                        Import Leads

                    </a>
                    @endrole
                </div>

            </div>

            <div class="card-body">

                <form method="GET" action="{{ route('lms.leads') }}" class="mb-4" novalidate>
                    @php
                        $hasActiveFilters = request()->filled('list_id')
                            || request()->filled('name')
                            || request()->filled('phone_number')
                            || request()->filled('email')
                            || request()->filled('status')
                            || collect(request('filters', []))->flatten()->filter(fn($value) => $value !== null && $value !== '')->isNotEmpty();
                    @endphp

                    <div class="border rounded-3 bg-light-subtle overflow-hidden">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 p-3">
                            <div>
                                <h6 class="mb-1">Lead Filters</h6>
                                <small class="text-muted">Open filters only when needed.</small>
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                @if($hasActiveFilters)
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2">
                                        Filters Active
                                    </span>
                                @endif
                                <button
                                    class="btn btn-outline-dark"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#leadFiltersCollapse"
                                    aria-expanded="{{ $hasActiveFilters ? 'true' : 'false' }}"
                                    aria-controls="leadFiltersCollapse">
                                    <i class="ri-filter-3-line me-1"></i>
                                    {{ $hasActiveFilters ? 'Show / Hide Filters' : 'Open Filters' }}
                                </button>
                            </div>
                        </div>

                        <div class="collapse {{ $hasActiveFilters ? 'show' : '' }}" id="leadFiltersCollapse">
                            <div class="border-top p-3">
                                <div class="d-flex justify-content-end gap-2 flex-wrap mb-3">
                                    <a href="{{ route('lms.leads') }}" class="btn btn-light border">Reset</a>
                                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">List</label>
                                        <select name="list_id" class="form-select">
                                            <option value="">All Lists</option>
                                            @foreach($lists as $list)
                                                <option value="{{ $list->id }}" {{ request('list_id') == $list->id ? 'selected' : '' }}>
                                                    {{ $list->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Lead Name</label>
                                        <input type="text" name="name" value="{{ request('name') }}" class="form-control"
                                            placeholder="Search by name">
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">Phone</label>
                                        <input type="text" name="phone_number" value="{{ request('phone_number') }}" class="form-control"
                                            placeholder="Phone number">
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">Email</label>
                                        <input type="text" name="email" value="{{ request('email') }}" class="form-control"
                                            placeholder="Email">
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">Status</label>
                                        <select name="status" class="form-select">
                                            <option value="">All Status</option>
                                            @foreach(['new', 'contacted', 'qualified', 'interested', 'followup'] as $status)
                                                <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>
                                                    {{ ucfirst($status) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    @foreach($filterableFields as $field)
                                        @php
                                            $selectedFilterValue = request('filters.' . $field->slug);
                                            $fieldOptions = $field->options ? json_decode($field->options, true) : [];
                                        @endphp

                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">{{ $field->name }}</label>

                                            @if(in_array($field->type, ['text', 'textarea', 'email', 'phone'], true))
                                                <input type="text"
                                                    name="filters[{{ $field->slug }}]"
                                                    value="{{ is_array($selectedFilterValue) ? '' : $selectedFilterValue }}"
                                                    class="form-control"
                                                    placeholder="Enter {{ strtolower($field->name) }}">

                                            @elseif(in_array($field->type, ['number', 'decimal'], true))
                                                <input type="number"
                                                    step="any"
                                                    name="filters[{{ $field->slug }}]"
                                                    value="{{ is_array($selectedFilterValue) ? '' : $selectedFilterValue }}"
                                                    class="form-control"
                                                    placeholder="Enter {{ strtolower($field->name) }}">

                                            @elseif(in_array($field->type, ['date', 'datetime'], true))
                                                <input type="{{ $field->type === 'datetime' ? 'datetime-local' : 'date' }}"
                                                    name="filters[{{ $field->slug }}]"
                                                    value="{{ is_array($selectedFilterValue) ? '' : $selectedFilterValue }}"
                                                    class="form-control">

                                            @elseif(in_array($field->type, ['select', 'radio'], true))
                                                <select name="filters[{{ $field->slug }}]" class="form-select">
                                                    <option value="">All</option>
                                                    @foreach($fieldOptions as $option)
                                                        <option value="{{ $option }}" {{ $selectedFilterValue == $option ? 'selected' : '' }}>
                                                            {{ $option }}
                                                        </option>
                                                    @endforeach
                                                </select>

                                            @elseif($field->type === 'boolean')
                                                <select name="filters[{{ $field->slug }}]" class="form-select">
                                                    <option value="">All</option>
                                                    <option value="1" {{ (string) $selectedFilterValue === '1' ? 'selected' : '' }}>Yes</option>
                                                    <option value="0" {{ (string) $selectedFilterValue === '0' ? 'selected' : '' }}>No</option>
                                                </select>

                                            @elseif($field->type === 'checkbox')
                                                <div class="border rounded-2 p-2 bg-white" style="max-height: 120px; overflow-y: auto;">
                                                    @foreach($fieldOptions as $option)
                                                        <div class="form-check mb-1">
                                                            <input class="form-check-input"
                                                                type="checkbox"
                                                                name="filters[{{ $field->slug }}][]"
                                                                value="{{ $option }}"
                                                                id="filter_{{ $field->slug }}_{{ \Illuminate\Support\Str::slug($option, '_') }}"
                                                                {{ in_array($option, (array) $selectedFilterValue, true) ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="filter_{{ $field->slug }}_{{ \Illuminate\Support\Str::slug($option, '_') }}">
                                                                {{ $option }}
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>

                                <div class="d-flex justify-content-end gap-2 flex-wrap mt-3">
                                    <a href="{{ route('lms.leads') }}" class="btn btn-light border">Reset</a>
                                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">

                    <table class="table align-middle">

                        <thead>
                            <tr>
                                <th><input type="checkbox" class="form-check-input" id="select-all"></th>
                                <th>#</th>
                                <th>Lead Info</th>
                                <th>List</th>
                                <th>Status</th>
                                <th>Assigned To</th>
                                <th>Call</th>
                                <th>Created</th>
                                <th width="150">Action</th>
                            </tr>
                        </thead>

                        <tbody>

                            @forelse($leads as $lead)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input lead-checkbox" data-id="{{ $lead->id }}">
                                    </td>

                                    <td>
                                        {{ $lead->id }}
                                    </td>

                                    <td>

                                        <div>

                                            <div class="fw-semibold">

                                                {{ $lead->name }}

                                            </div>
                                            <div class="fw-semibold">
                                                {{ $lead->phone_number }}

                                            </div>

                                            <small class="text-muted">
                                                {{ $lead->email }}

                                            </small>



                                        </div>

                                    </td>

                                    <td>

                                        {{ $lead->list->name ?? '-' }}

                                    </td>

                                    <td>

                                        <span class="badge bg-primary">

                                            {{ ucfirst($lead->status) }}

                                        </span>

                                    </td>

                                    <td>

                                        {{ $lead->assignedTo->name ?? '-' }}

                                    </td>

                                    <td><a href="#" class="btn btn-success"><i class="ri-phone-line"></i></a></td>


                                    <td>

                                        {{ $lead->created_at->format('d M Y') }}

                                    </td>

                                    <td>

                                        <div class="btn-group">

                                            <a href="{{ route('lms.lead.view', $lead->id) }}" class="btn btn-sm btn-info me-1">
                                                <i class="ri-eye-line"></i>
                                            </a>

                                            <a href="{{ route('lms.lead.edit', $lead->id) }}"
                                                class="btn btn-sm btn-primary me-1">
                                                <i class="ri-edit-line"></i>
                                            </a>
                                            @role('Admin|Manager|Cluster')
                                            <input type="hidden" id="deleteUrl" value="{{ route('lms.leads.delete') }}">

                                            <button class="btn btn-sm btn-danger deleteRecord" data-id="{{ $lead->id }}">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                            @endrole

                                        </div>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="9" class="text-center py-5">

                                        No Leads Found

                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

                <div class="mt-3">

                    {{ $leads->links() }}

                </div>

            </div>

        </div>

    </div>

    <div class="modal fade" id="assignLeadModal" tabindex="-1" aria-labelledby="assignLeadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold" id="assignLeadModalLabel">
                            Assign Selected Leads
                        </h5>
                        <small class="text-muted">
                            Select manager → supervisor → user to assign leads.
                        </small>
                    </div>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-3">
                    <form id="assignLeadForm">
                        @csrf

                        {{-- Step 1: Manager --}}
                        <div class="mb-3">
                            <label for="assign_manager_id" class="form-label fw-semibold">
                                <span class="badge bg-dark me-1">1</span> Select Manager
                            </label>
                            <select id="assign_manager_id" name="manager_id" class="form-select">
                                <option value="">— Select Manager —</option>
                                @foreach($managers as $manager)
                                    <option value="{{ $manager->id }}">{{ $manager->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Step 2: Supervisor --}}
                        <div class="mb-3">
                            <label for="assign_supervisor_id" class="form-label fw-semibold">
                                <span class="badge bg-dark me-1">2</span> Select Supervisor
                            </label>
                            <div class="position-relative">
                                <select id="assign_supervisor_id" name="supervisor_id" class="form-select" disabled>
                                    <option value="">— Select Manager First —</option>
                                </select>
                                <div id="supervisorLoader"
                                    class="position-absolute top-50 end-0 translate-middle-y me-4 d-none">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                            <small id="supervisorCount" class="text-muted d-none"></small>
                        </div>

                        {{-- Step 3: User --}}
                        <div class="mb-3">
                            <label for="assign_user_id" class="form-label fw-semibold">
                                <span class="badge bg-dark me-1">3</span> Select User
                            </label>
                            <div class="position-relative">
                                <select id="assign_user_id" name="user_id" class="form-select" disabled required>
                                    <option value="">— Select Supervisor First —</option>
                                </select>
                                <div id="userLoader" class="position-absolute top-50 end-0 translate-middle-y me-4 d-none">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                            <small id="userCount" class="text-muted d-none"></small>
                        </div>
                    </form>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-dark" id="submitAssignLead" disabled>
                        Assign Lead
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <script>
        $(function () {
            const assignLeadModal = new bootstrap.Modal(document.getElementById('assignLeadModal'));

            // Active AJAX requests (for aborting on rapid changes)
            let supervisorXhr = null;
            let userXhr = null;

            function getSelectedLeadIds() {
                return $('.lead-checkbox:checked').map(function () {
                    return $(this).data('id');
                }).get();
            }

            /**
             * Reset a select to its disabled/placeholder state
             */
            function resetSelect($select, placeholder) {
                $select.html(`<option value="">${placeholder}</option>`);
                $select.prop('disabled', true);
            }

            /**
             * Populate a select with fetched data
             */
            function populateSelect($select, data, placeholder) {
                let options = `<option value="">— ${placeholder} —</option>`;

                data.forEach(function (item) {
                    options += `<option value="${item.id}">${item.name}</option>`;
                });

                $select.html(options);
                $select.prop('disabled', data.length === 0);
            }

            /**
             * Fetch supervisors by manager (AJAX)
             */
            function fetchSupervisors(managerId) {
                // Abort any pending request
                if (supervisorXhr) supervisorXhr.abort();

                // Reset supervisor & user dropdowns
                resetSelect($('#assign_supervisor_id'), '— Loading... —');
                resetSelect($('#assign_user_id'), '— Select Supervisor First —');
                $('#supervisorCount').addClass('d-none');
                $('#userCount').addClass('d-none');
                $('#submitAssignLead').prop('disabled', true);

                if (!managerId) {
                    resetSelect($('#assign_supervisor_id'), '— Select Manager First —');
                    return;
                }

                // Show loader
                $('#supervisorLoader').removeClass('d-none');

                supervisorXhr = $.ajax({
                    url: '{{ route("lms.api.supervisors-by-manager") }}',
                    method: 'GET',
                    data: {manager_id: managerId},
                    success: function (data) {
                        populateSelect($('#assign_supervisor_id'), data, 'Select Supervisor');

                        // Show count
                        if (data.length > 0) {
                            $('#supervisorCount').text(data.length + ' supervisor(s) found').removeClass('d-none');
                        } else {
                            $('#supervisorCount').text('No supervisors found under this manager').removeClass('d-none');
                        }
                    },
                    error: function (xhr) {
                        if (xhr.statusText !== 'abort') {
                            resetSelect($('#assign_supervisor_id'), '— Error loading —');
                        }
                    },
                    complete: function () {
                        $('#supervisorLoader').addClass('d-none');
                    }
                });
            }

            /**
             * Fetch users by supervisor (AJAX)
             */
            function fetchUsers(supervisorId, managerId) {
                // Abort any pending request
                if (userXhr) userXhr.abort();

                // Reset user dropdown
                resetSelect($('#assign_user_id'), '— Loading... —');
                $('#userCount').addClass('d-none');
                $('#submitAssignLead').prop('disabled', true);

                if (!supervisorId) {
                    resetSelect($('#assign_user_id'), '— Select Supervisor First —');
                    return;
                }

                // Show loader
                $('#userLoader').removeClass('d-none');

                userXhr = $.ajax({
                    url: '{{ route("lms.api.users-by-supervisor") }}',
                    method: 'GET',
                    data: {
                        supervisor_id: supervisorId,
                        manager_id: managerId
                    },
                    success: function (data) {
                        populateSelect($('#assign_user_id'), data, 'Select User');

                        // Show count
                        if (data.length > 0) {
                            $('#userCount').text(data.length + ' user(s) found').removeClass('d-none');
                        } else {
                            $('#userCount').text('No users found under this supervisor').removeClass('d-none');
                        }
                    },
                    error: function (xhr) {
                        if (xhr.statusText !== 'abort') {
                            resetSelect($('#assign_user_id'), '— Error loading —');
                        }
                    },
                    complete: function () {
                        $('#userLoader').addClass('d-none');
                    }
                });
            }

            // Select All checkbox
            $('#select-all').on('change', function () {
                $('.lead-checkbox').prop('checked', $(this).is(':checked'));
            });

            $(document).on('change', '.lead-checkbox', function () {
                const totalCheckboxes = $('.lead-checkbox').length;
                const checkedCheckboxes = $('.lead-checkbox:checked').length;

                $('#select-all').prop('checked', totalCheckboxes > 0 && totalCheckboxes === checkedCheckboxes);
            });

            // Open Modal
            $('#openAssignLeadModal').on('click', function () {
                const selectedLeadIds = getSelectedLeadIds();

                if (selectedLeadIds.length === 0) {
                    notify_it('error', 'Please select at least one lead to assign.');
                    return;
                }

                // Reset form to initial state
                $('#assignLeadForm')[0].reset();
                resetSelect($('#assign_supervisor_id'), '— Select Manager First —');
                resetSelect($('#assign_user_id'), '— Select Supervisor First —');
                $('#supervisorCount, #userCount').addClass('d-none');
                $('#submitAssignLead').prop('disabled', true);

                assignLeadModal.show();
            });

            // Manager changed → fetch supervisors
            $('#assign_manager_id').on('change', function () {
                fetchSupervisors($(this).val());
            });

            // Supervisor changed → fetch users
            $('#assign_supervisor_id').on('change', function () {
                fetchUsers($(this).val(), $('#assign_manager_id').val());
            });

            // User changed → enable/disable submit button
            $('#assign_user_id').on('change', function () {
                $('#submitAssignLead').prop('disabled', !$(this).val());
            });

            // Submit
            $('#submitAssignLead').on('click', function () {
                const selectedLeadIds = getSelectedLeadIds();
                const userId = $('#assign_user_id').val();

                if (selectedLeadIds.length === 0) {
                    notify_it('error', 'Please select at least one lead to assign.');
                    assignLeadModal.hide();
                    return;
                }

                if (!userId) {
                    notify_it('error', 'Please select a user.');
                    return;
                }

                const $btn = $(this);
                $btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Assigning...'
                );

                $.ajax({
                    url: '{{ route('lms.leads.assign') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        lead_ids: selectedLeadIds,
                        manager_id: $('#assign_manager_id').val(),
                        supervisor_id: $('#assign_supervisor_id').val(),
                        user_id: userId
                    },
                    success: function (response) {
                        if (response.success) {
                            assignLeadModal.hide();
                            notify_it('success', response.message);
                            setTimeout(function () {
                                window.location.reload();
                            }, 1000);
                        } else {
                            notify_it('error', response.message);
                        }
                    },
                    error: function (xhr) {
                        let message = 'An unexpected error occurred.';

                        if (xhr.responseJSON?.message) {
                            message = xhr.responseJSON.message;
                        }

                        if (xhr.responseJSON?.errors) {
                            message = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                        }

                        notify_it('error', message);
                    },
                    complete: function () {
                        $btn.prop('disabled', false).html('Assign Lead');
                    }
                });
            });
        });
    </script>
@endsection
