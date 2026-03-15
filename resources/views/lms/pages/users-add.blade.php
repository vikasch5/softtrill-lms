@extends('lms.common.master')

@section('content')

    <style>
        .section-label {
            font-size: 11px;
            font-weight: 600;
            color: #6c757d;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .section-label:after {
            content: '';
            display: block;
            width: 35px;
            height: 2px;
            background: #0d6efd;
            margin-top: 4px;
        }

        .form-control,
        .form-select {
            font-size: 13px;
        }
    </style>

    @php
        $details = $user->details ?? null;
    @endphp

    <div class="dashboard-main-body">
        <div class="row gy-4">
            <div class="col-lg-12">

                <div class="card border-0 shadow-sm">

                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">
                            {{ isset($user->id) ? 'Edit User' : 'Add User' }}
                        </h5>

                        <a href="{{ route('lms.users.list') }}" class="btn btn-primary btn-sm">
                            Users List
                        </a>
                    </div>

                    <div class="card-body">

                        <form action="{{ route('lms.users.store') }}" method="POST" enctype="multipart/form-data"
                            class="row g-4 ajaxForm">

                            @csrf

                            <input type="hidden" name="user_id" value="{{ $user->id ?? '' }}">

                            <!-- BASIC -->
                            <div class="col-12">
                                <div class="section-label">Basic Info</div>
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" class="form-control" placeholder="Enter full name"
                                    value="{{ old('name', $user->name ?? '') }}" required>
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" placeholder="Enter email address"
                                    value="{{ old('email', $user->email ?? '') }}" required>
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" placeholder="Enter phone number"
                                    value="{{ old('phone', $details->phone ?? '') }}">
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control"
                                    placeholder="{{ isset($user->id) ? 'Leave blank to keep existing password' : 'Enter password' }}"
                                    {{ empty($user->id) ? 'required' : '' }}>
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <label class="form-label">Profile Photo</label>

                                <div class="d-flex gap-2">

                                    <input type="file" name="profile_photo" class="form-control">

                                    @if(!empty($details->profile_photo))

                                        <a href="{{ asset('storage/' . $details->profile_photo) }}" target="_blank"
                                            class="btn btn-sm btn-primary">

                                            View

                                        </a>

                                    @endif

                                </div>
                            </div>

                            <!-- ROLE -->
                            <div class="col-lg-4 col-md-6">
                                <label class="form-label">Role</label>

                                <select name="role" id="role" class="form-control" required>

                                    <option value="">Select Role</option>

                                    @foreach($roles as $role)

                                        <option value="{{ $role->name }}" @selected(isset($userRole) && $userRole == $role->name)>
                                            {{ $role->name }}
                                        </option>

                                    @endforeach

                                </select>
                            </div>

                            <!-- EMPLOYEE INFO -->
                            <div class="col-12 mt-3">
                                <div class="section-label">Employee Information</div>
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <label class="form-label">Employee ID</label>
                                <input type="text" name="employee_id" class="form-control"
                                    value="{{ old('employee_id', $details->employee_id ?? '') }}">
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <label class="form-label">Department</label>
                                <input type="text" name="department" class="form-control"
                                    value="{{ old('department', $details->department ?? '') }}">
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <label class="form-label">Designation</label>
                                <input type="text" name="designation" class="form-control"
                                    value="{{ old('designation', $details->designation ?? '') }}">
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <label class="form-label">Joining Date</label>
                                <input type="date" name="joining_date" class="form-control"
                                    value="{{ old('joining_date', $details->joining_date ?? '') }}">
                            </div>

                            <!-- HIERARCHY -->
                            <div class="col-12 mt-3">
                                <div class="section-label">Hierarchy</div>
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <label class="form-label">Cluster</label>

                                <select name="cluster_id" id="cluster_id" class="form-control">

                                    <option value="">Select Cluster</option>

                                    @foreach($clusters as $cluster)

                                        <option value="{{ $cluster->id }}"
                                            @selected(optional($details)->cluster_id == $cluster->id)>
                                            {{ $cluster->name }}
                                        </option>

                                    @endforeach

                                </select>

                            </div>


                            <div class="col-lg-4 col-md-6">
                                <label class="form-label">Manager</label>

                                <select name="manager_id" id="manager_id" class="form-control">

                                    <option value="">Select Manager</option>

                                    @foreach($managers as $manager)

                                        <option value="{{ $manager->id }}"
                                            @selected(optional($details)->manager_id == $manager->id)>
                                            {{ $manager->name }}
                                        </option>

                                    @endforeach

                                </select>

                            </div>


                            <div class="col-lg-4 col-md-6">
                                <label class="form-label">Team Leader</label>

                                <select name="teamleader_id" id="teamleader_id" class="form-control">

                                    <option value="">Select Team Leader</option>

                                    @foreach($teamleaders as $tl)

                                        <option value="{{ $tl->id }}" @selected(optional($details)->teamleader_id == $tl->id)>
                                            {{ $tl->name }}
                                        </option>

                                    @endforeach

                                </select>

                            </div>

                            <!-- STATUS -->
                            <div class="col-12 mt-3">
                                <div class="section-label">Account Status</div>
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <label class="form-label">Status</label>

                                <select name="status" class="form-control">

                                    <option value="1" @selected(optional($details)->status == 1)>Active</option>

                                    <option value="0" @selected(optional($details)->status == 0)>Inactive</option>

                                </select>

                            </div>

                            <div class="col-12 mt-3">

                                <button type="submit" class="btn btn-primary px-4">

                                    {{ isset($user->id) ? 'Update User' : 'Create User' }}

                                </button>

                            </div>

                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('lms.common.footer')

@endsection
@section('scripts')

    <script>

        $(document).ready(function () {

            function roleValidation() {

                let role = $('#role').val()

                $('#cluster_id,#manager_id,#teamleader_id').prop('required', false)

                if (role === 'Manager') {
                    $('#cluster_id').prop('required', true)
                }

                if (role === 'TeamLeader') {
                    $('#cluster_id').prop('required', true)
                    $('#manager_id').prop('required', true)
                }

                if (role === 'Agent') {
                    $('#cluster_id').prop('required', true)
                    $('#manager_id').prop('required', true)
                    $('#teamleader_id').prop('required', true)
                }

            }

            $('#role').change(function () {
                roleValidation()
            })

            roleValidation()

        })

    </script>

@endsection