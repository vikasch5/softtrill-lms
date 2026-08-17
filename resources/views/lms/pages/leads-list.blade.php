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
                    @role('Admin|Cluster')
                        <button type="button" 
                            class="btn btn-outline-success"
                            id="openDownloadModalBtn"
                            data-download-url="{{ route('lms.leads.download') }}">
                            <i class="ri-download-2-line"></i>
                            Download List
                        </button>
                    @endrole

                    @role('Admin|Manager|Cluster')
                    <a href="{{ route('lms.lead.import') }}" class="btn btn-primary">

                        <i class="ri-upload-cloud-line"></i>
                        Import Leads

                    </a>
                    @endrole

                    @role('Admin|Manager|Cluster|TeamLeader|Agent')
                    <a href="{{ route('lms.leads.add') }}" class="btn btn-primary">
                        <i class="ri-upload-cloud-line"></i>
                        Add Leads
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
                            || request()->filled('feedback_id')
                            || request()->filled('followup_status')
                            || request()->filled('followup_from')
                            || request()->filled('followup_to')
                            || request()->filled('created_from')
                            || request()->filled('created_to')
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
                                    <span class="badge rounded-pill bg-light text-primary border border-primary d-inline-flex align-items-center justify-content-center px-3 py-2">
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

                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Phone</label>
                                        <input type="text" name="phone_number" value="{{ request('phone_number') }}" class="form-control"
                                            placeholder="Phone number">
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Email</label>
                                        <input type="text" name="email" value="{{ request('email') }}" class="form-control"
                                            placeholder="Email">
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Feedback</label>
                                        <select name="feedback_id" class="form-select">
                                            <option value="">All Feedbacks</option>
                                            @foreach($feedbacks as $feedback)
                                                <option value="{{ $feedback->id }}" {{ request('feedback_id') == $feedback->id ? 'selected' : '' }}>
                                                    {{ ucfirst($feedback->name) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Followup Status</label>
                                        <select name="followup_status" class="form-select">
                                            <option value="">All Followups</option>
                                            <option value="today" {{ request('followup_status') === 'today' ? 'selected' : '' }}>Today</option>
                                            <option value="pending" {{ request('followup_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="upcoming" {{ request('followup_status') === 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Followup From</label>
                                        <input type="date" name="followup_from" value="{{ request('followup_from') }}" class="form-control">
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Followup To</label>
                                        <input type="date" name="followup_to" value="{{ request('followup_to') }}" class="form-control">
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Created From</label>
                                        <input type="date" name="created_from" value="{{ request('created_from') }}" class="form-control">
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Created To</label>
                                        <input type="date" name="created_to" value="{{ request('created_to') }}" class="form-control">
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
                                <th>Name</th>
                                <th>Feedback</th>
                                <th>Followup Date</th>
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
                                        {{ $lead->lead_id ?: \App\Models\Lead::formatLeadId($lead->id) }}
                                    </td>

                                    <td>
                                        <div class="fw-semibold">
                                            {{ $lead->name }}
                                            
                                        </div>
                                        <div class="fw-semibold">
                                            @php
                                                $userRole = auth()->user()->getRoleNames()->first();
                                                $canViewUnmasked = $privacyService->canRole($userRole, 'unmasked_mobile');
                                                $displayMobile = $canViewUnmasked 
                                                    ? $lead->phone_number 
                                                    : $privacyService->maskMobile($lead->phone_number, $privacySettings['mobile']);
                                            @endphp
                                            {{ $displayMobile }}
                                        </div>
                                        <span class="text-muted">
                                            @php
                                                $canViewUnmaskedEmail = $privacyService->canRole($userRole, 'unmasked_email');
                                                $displayEmail = $canViewUnmaskedEmail 
                                                    ? $lead->email 
                                                    : $privacyService->maskEmail($lead->email, $privacySettings['email']);
                                            @endphp
                                            {{ $displayEmail }}
                                        </span>
                                    </td>

                                    {{-- <td>

                                        {{ $lead->list->name ?? '-' }}

                                    </td> --}}

                                    <td>

                                        <span class="badge bg-primary">

                                            {{ ucfirst($lead->leadFeedback?->feedback?->name ?? 'N/A') }}

                                        </span>

                                    </td>
                                     <td>
@php
    $followupDate = $lead->next_followup_at;

    $badgeClass = match (true) {
        !$followupDate => 'bg-secondary',
        $followupDate->isPast() && !$followupDate->isToday() => 'bg-danger',
        $followupDate->isToday() => 'bg-warning text-dark',
        $followupDate->isFuture() => 'bg-success',
        default => 'bg-secondary',
    };
@endphp

<span class="badge {{ $badgeClass }}">
    {{ $lead->next_followup_formatted }}
</span>
                                    </td>

                                    <td>

                                        {{ $lead->assignedTo->name ?? '-' }}

                                    </td>

                                    <td><a href="#" class="btn btn-success dialer-call" data-id="{{ $lead->id }}"><i class="ri-phone-line"></i></a></td>


                                    <td>

                                        {{ $lead->created_at->format('d M Y') }}

                                    </td>

                                    <td>

                                        <div class="btn-group">

                                            <a href="{{ route('lms.lead.view', $lead->lead_id) }}" class="btn btn-sm btn-info me-1">
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

                                    <td colspan="10" class="text-center py-5">

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
                            Select through the hierarchy. The deepest selected user receives the leads.
                        </small>
                    </div>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-3">
                    <form id="assignLeadForm">
                        @csrf

                        <input type="hidden" id="assign_target_role" name="target_role">
                        <input type="hidden" id="assign_user_id" name="user_id">

                        @if(auth()->user()->hasAnyRole(['Admin', 'Cluster']))
                            <div class="mb-3">
                                <label for="assignment_manager" class="form-label fw-semibold">
                                    <span class="badge bg-dark me-1">1</span> Manager
                                </label>
                                <select id="assignment_manager" class="form-select assignment-level" data-role="Manager">
                                    <option value="">— Select Manager —</option>
                                </select>
                            </div>
                        @endif

                        @if(auth()->user()->hasAnyRole(['Admin', 'Cluster', 'Manager']))
                            <div class="mb-3">
                                <label for="assignment_teamleader" class="form-label fw-semibold">
                                    <span class="badge bg-dark me-1">{{ auth()->user()->hasAnyRole(['Admin', 'Cluster']) ? '2' : '1' }}</span>
                                    Team Leader
                                </label>
                                <select id="assignment_teamleader" class="form-select assignment-level" data-role="TeamLeader" disabled>
                                    <option value="">— Select Manager First —</option>
                                </select>
                            </div>
                        @endif

                        <div class="mb-3">
                            <label for="assignment_agent" class="form-label fw-semibold">
                                <span class="badge bg-dark me-1">
                                    {{ auth()->user()->hasAnyRole(['Admin', 'Cluster']) ? '3' : (auth()->user()->hasRole('Manager') ? '2' : '1') }}
                                </span>
                                Agent
                            </label>
                            <select id="assignment_agent" class="form-select assignment-level" data-role="Agent" disabled>
                                <option value="">— Select Team Leader First —</option>
                            </select>
                        </div>

                        <div id="assignUserLoader" class="d-none text-primary small">
                            <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                            Loading hierarchy...
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

    <style>
        .export-modal-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: none;
            padding: 1.25rem 1.5rem;
            border-radius: calc(0.375rem - 1px) calc(0.375rem - 1px) 0 0;
        }
        .export-modal-title {
            font-weight: 600;
            font-size: 1.15rem;
            color: #2b3445;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0;
        }
        .export-modal-body {
            padding: 1.5rem;
        }
        .export-card {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            padding: 1.25rem;
            border: 2px solid #eef1f5;
            border-radius: 12px;
            background: #ffffff;
            text-align: left;
            transition: all 0.2s ease-in-out;
            cursor: pointer;
            width: 100%;
            text-decoration: none;
        }
        .export-card:hover {
            border-color: #0d6efd;
            background: #f8fbff;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(13, 110, 253, 0.08);
        }
        .export-card.dialer:hover {
            border-color: #198754;
            background: #f8fff9;
            box-shadow: 0 8px 24px rgba(25, 135, 84, 0.08);
        }
        .export-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 52px;
            height: 52px;
            border-radius: 50%;
            font-size: 1.5rem;
            flex-shrink: 0;
        }
        .export-icon.full {
            background: #eef4ff;
            color: #0d6efd;
        }
        .export-icon.dialer {
            background: #eaffe5;
            color: #198754;
        }
        .export-details h6 {
            margin: 0 0 0.25rem 0;
            font-weight: 700;
            font-size: 1.05rem;
            color: #2b3445;
        }
        .export-details p {
            margin: 0;
            font-size: 0.85rem;
            color: #7d879c;
            line-height: 1.4;
        }
    </style>

    <!-- Export Options Modal -->
    <div class="modal fade" id="exportLeadsModal" tabindex="-1" aria-labelledby="exportLeadsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                <div class="modal-header export-modal-header">
                    <h5 class="modal-title export-modal-title" id="exportLeadsModalLabel">
                        <i class="ri-download-cloud-2-line text-primary"></i> Export Leads
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body export-modal-body">
                    <p class="text-muted mb-4" style="font-size: 0.95rem;">Choose your preferred export format. Active filters will be applied automatically.</p>
                    
                    <div class="d-flex flex-column gap-3">
                        <button type="button" class="export-card export-option-btn" data-export-type="full">
                            <div class="export-icon full"><i class="ri-file-excel-2-line"></i></div>
                            <div class="export-details">
                                <h6>Full List Export</h6>
                                <p>Includes all standard fields, custom form data, and your complete followup history.</p>
                            </div>
                        </button>
                        
                        <button type="button" class="export-card dialer export-option-btn" data-export-type="dialer">
                            <div class="export-icon dialer"><i class="ri-phone-line"></i></div>
                            <div class="export-details">
                                <h6>Dialer Export</h6>
                                <p>Streamlined layout optimized for dialers. Excludes custom fields and past/upcoming followups.</p>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <script>
        $(function () {
            const assignLeadModal = new bootstrap.Modal(document.getElementById('assignLeadModal'));

            $('#downloadLeadList').on('click', function (event) {
                event.preventDefault();
                const listId = $('select[name="list_id"]').val();

                if (!listId) {
                    notify_it('error', 'Please select a list from Lead Filters before downloading.');
                    return;
                }

                const form = $('form.mb-4');
                const params = form.serialize();
                window.location.href = $(this).data('download-url') + '?' + params;
            });

            let assignableUsersXhr = null;

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

            function fetchAssignableUsers(role, parentId, $select, placeholder) {
                if (assignableUsersXhr) assignableUsersXhr.abort();

                resetSelect($select, '— Loading... —');
                $('#assignUserLoader').removeClass('d-none');

                assignableUsersXhr = $.ajax({
                    url: '{{ route("lms.api.assignable-users") }}',
                    method: 'GET',
                    data: {role: role, parent_id: parentId || ''},
                    success: function (data) {
                        populateSelect($select, data, placeholder);
                    },
                    error: function (xhr) {
                        if (xhr.statusText !== 'abort') {
                            resetSelect($select, '— Error loading —');
                            notify_it('error', xhr.responseJSON?.message || 'Unable to load users.');
                        }
                    },
                    complete: function () {
                        $('#assignUserLoader').addClass('d-none');
                    }
                });
            }

            function syncAssignmentTarget() {
                const levels = [
                    {select: '#assignment_agent', role: 'Agent'},
                    {select: '#assignment_teamleader', role: 'TeamLeader'},
                    {select: '#assignment_manager', role: 'Manager'}
                ];
                let selected = null;

                levels.some(function (level) {
                    const value = $(level.select).val();
                    if (value) {
                        selected = {id: value, role: level.role};
                        return true;
                    }
                    return false;
                });

                $('#assign_user_id').val(selected ? selected.id : '');
                $('#assign_target_role').val(selected ? selected.role : '');
                $('#submitAssignLead').prop('disabled', !selected);
            }

            function loadInitialAssignmentLevel() {
                @if(auth()->user()->hasAnyRole(['Admin', 'Cluster']))
                    fetchAssignableUsers('Manager', null, $('#assignment_manager'), 'Select Manager');
                @elseif(auth()->user()->hasRole('Manager'))
                    fetchAssignableUsers('TeamLeader', null, $('#assignment_teamleader'), 'Select Team Leader');
                @elseif(auth()->user()->hasRole('TeamLeader'))
                    fetchAssignableUsers('Agent', null, $('#assignment_agent'), 'Select Agent');
                @endif
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
                resetSelect($('#assignment_manager'), '— Select Manager —');
                resetSelect($('#assignment_teamleader'), '— Select Manager First —');
                resetSelect($('#assignment_agent'), '— Select Team Leader First —');
                $('#submitAssignLead').prop('disabled', true);

                assignLeadModal.show();
                loadInitialAssignmentLevel();
            });

            $('#assignment_manager').on('change', function () {
                resetSelect($('#assignment_teamleader'), '— Select Manager First —');
                resetSelect($('#assignment_agent'), '— Select Team Leader First —');
                syncAssignmentTarget();

                if ($(this).val()) {
                    fetchAssignableUsers('TeamLeader', $(this).val(), $('#assignment_teamleader'), 'Select Team Leader');
                }
            });

            $('#assignment_teamleader').on('change', function () {
                resetSelect($('#assignment_agent'), '— Select Team Leader First —');
                syncAssignmentTarget();

                if ($(this).val()) {
                    fetchAssignableUsers('Agent', $(this).val(), $('#assignment_agent'), 'Select Agent');
                }
            });

            $('#assignment_agent').on('change', function () {
                syncAssignmentTarget();
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
                        target_role: $('#assign_target_role').val(),
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

        // ─── Dialer Call ──────────────────────────────────────────────────────────
        $(document).on('click', '.dialer-call', function (e) {
            e.preventDefault();

            const $btn   = $(this);
            const leadId = $btn.data('id');

            if (!leadId) {
                notify_it('error', 'No lead ID found.');
                return;
            }

            // Visual feedback: disable button & show spinner
            $btn.prop('disabled', true)
                .html('<span class="spinner-border spinner-border-sm" role="status"></span>');

            $.ajax({
                url    : '{{ route('lms.dialer.call') }}',
                method : 'POST',
                data   : {
                    _token : '{{ csrf_token() }}',
                    lead_id: leadId,
                },
                success: function (response) {
                    if (response.success) {
                        notify_it('success', 'Call initiated successfully.');
                    } else {
                        notify_it('error', response.message || 'Dialer call failed.');
                    }
                },
                error: function (xhr) {
                    let message = 'An unexpected error occurred while initiating the call.';

                    if (xhr.responseJSON?.message) {
                        message = xhr.responseJSON.message;
                    }

                    notify_it('error', message);
                },
                complete: function () {
                    // Restore button icon
                    $btn.prop('disabled', false)
                        .html('<i class="ri-phone-line"></i>');
                }
            });
        });
        // ─────────────────────────────────────────────────────────────────────────

        // ─── Export Leads Modal ──────────────────────────────────────────────────
        $(document).on('click', '#openDownloadModalBtn', function (e) {
            e.preventDefault();
            
            // Check if list is selected (or if we have a default validation)
            const listId = $('select[name="list_id"]').val();
            if (!listId) {
                notify_it('warning', 'Please select a List from the filters before downloading.');
                return;
            }
            
            $('#exportLeadsModal').modal('show');
        });

        $(document).on('click', '.export-option-btn', function (e) {
            e.preventDefault();
            
            const exportType = $(this).data('export-type');
            const baseUrl = $('#openDownloadModalBtn').data('download-url');
            
            // Gather current form data
            const formData = $('.card-body form').serialize();
            
            // Append export type
            const downloadUrl = baseUrl + '?' + formData + '&export_type=' + exportType;
            
            // Close modal
            $('#exportLeadsModal').modal('hide');
            
            // Trigger download
            window.location.href = downloadUrl;
        });
        // ─────────────────────────────────────────────────────────────────────────
    </script>
@endsection
