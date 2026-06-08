@extends('lms.common.master')

@section('content')

    @php
        $supervisorsJson = $supervisors->map(function ($supervisor) {
            return [
                'id' => $supervisor->id,
                'name' => $supervisor->name,
                'manager_id' => optional($supervisor->details)->manager_id,
            ];
        })->values();

        $usersJson = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'manager_id' => optional($user->details)->manager_id,
                'supervisor_id' => optional($user->details)->teamleader_id,
            ];
        })->values();
    @endphp

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
                    <button type="button" class="btn btn-outline-dark" id="openAssignLeadModal">
                        <i class="ri-user-settings-line"></i>
                        Assign Lead
                    </button>

                    <a href="{{ route('lms.lead.import') }}" class="btn btn-primary">

                        <i class="ri-upload-cloud-line"></i>
                        Import Leads

                    </a>
                </div>

            </div>

            <div class="card-body">

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
                                            <input type="hidden" id="deleteUrl" value="{{ route('lms.leads.delete') }}">

                                            <button class="btn btn-sm btn-danger deleteRecord" data-id="{{ $lead->id }}">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>

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
                            Select manager, supervisor, and user for the chosen leads.
                        </small>
                    </div>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-3">
                    <form id="assignLeadForm">
                        @csrf

                        <div class="mb-3">
                            <label for="assign_manager_id" class="form-label fw-semibold">Select Manager</label>
                            <select id="assign_manager_id" name="manager_id" class="form-select">
                                <option value="">Select Manager</option>
                                @foreach($managers as $manager)
                                    <option value="{{ $manager->id }}">{{ $manager->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="assign_supervisor_id" class="form-label fw-semibold">Select Supervisor</label>
                            <select id="assign_supervisor_id" name="supervisor_id" class="form-select">
                                <option value="">Select Supervisor</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="assign_user_id" class="form-label fw-semibold">Select User</label>
                            <select id="assign_user_id" name="user_id" class="form-select" required>
                                <option value="">Select User</option>
                            </select>
                        </div>
                    </form>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-dark" id="submitAssignLead">
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
            const supervisors = @json($supervisorsJson);
            const users = @json($usersJson);

            function getSelectedLeadIds() {
                return $('.lead-checkbox:checked').map(function () {
                    return $(this).data('id');
                }).get();
            }

            function fillSupervisorOptions(managerId) {
                const filteredSupervisors = managerId
                    ? supervisors.filter(item => String(item.manager_id) === String(managerId))
                    : supervisors;

                let options = '<option value="">Select Supervisor</option>';

                filteredSupervisors.forEach(function (item) {
                    options += `<option value="${item.id}">${item.name}</option>`;
                });

                $('#assign_supervisor_id').html(options);
            }

            function fillUserOptions(managerId, supervisorId) {
                const filteredUsers = users.filter(function (item) {
                    const managerMatch = !managerId || String(item.manager_id) === String(managerId);
                    const supervisorMatch = !supervisorId || String(item.supervisor_id) === String(supervisorId);

                    return managerMatch && supervisorMatch;
                });

                let options = '<option value="">Select User</option>';

                filteredUsers.forEach(function (item) {
                    options += `<option value="${item.id}">${item.name}</option>`;
                });

                $('#assign_user_id').html(options);
            }

            $('#select-all').on('change', function () {
                $('.lead-checkbox').prop('checked', $(this).is(':checked'));
            });

            $(document).on('change', '.lead-checkbox', function () {
                const totalCheckboxes = $('.lead-checkbox').length;
                const checkedCheckboxes = $('.lead-checkbox:checked').length;

                $('#select-all').prop('checked', totalCheckboxes > 0 && totalCheckboxes === checkedCheckboxes);
            });

            $('#openAssignLeadModal').on('click', function () {
                const selectedLeadIds = getSelectedLeadIds();

                if (selectedLeadIds.length === 0) {
                    notify_it('error', 'Please select at least one lead to assign.');
                    return;
                }

                $('#assignLeadForm')[0].reset();
                fillSupervisorOptions('');
                fillUserOptions('', '');
                assignLeadModal.show();
            });

            $('#assign_manager_id').on('change', function () {
                const managerId = $(this).val();
                fillSupervisorOptions(managerId);
                fillUserOptions(managerId, '');
            });

            $('#assign_supervisor_id').on('change', function () {
                fillUserOptions($('#assign_manager_id').val(), $(this).val());
            });

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
                    }
                });
            });
        });
    </script>
@endsection
