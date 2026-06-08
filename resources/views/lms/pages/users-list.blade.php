@extends('lms.common.master')

@section('content')

    <div class="dashboard-main-body">

        <div class="row gy-4">
            <div class="col-lg-12">

                <div class="card">

                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">Users List</h5>

                        <div class="btns">
                            <a href="{{ route('lms.users.add') }}" class="btn btn-primary">Add New User</a>
                        </div>

                    </div>


                    <div class="card-body">

                        <div class="table-responsive">

                            <table class="table striped-table mb-0">

                                <thead>

                                    <tr>
                                        <th>ID</th>
                                        <th>Photo</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Cluster</th>
                                        <th>Manager</th>
                                        <th>Team Leader</th>
                                        <th>Status</th>
                                        <th class="text-center">Action</th>
                                    </tr>

                                </thead>


                                <tbody>

                                    <input type="hidden" id="deleteUrl" value="{{ route('lms.users.delete') }}">

                                    @foreach ($users as $key => $user)

                                        <tr>

                                            <td>{{ $key + 1 }}</td>

                                            <td>

                                                <div class="d-flex align-items-center">

                                                    @php
                                                        $photo = optional($user->details)->profile_photo;
                                                    @endphp

                                                    <img style="width:50px;height:50px;object-fit:cover"
                                                        src="{{ $photo ? asset('storage/'. $photo) : asset('images/default-user.png') }}"
                                                        alt="Image" class="radius-8 me-12">

                                                    <div class="flex-grow-1">

                                                        <h6 class="text-md mb-0 fw-normal">
                                                            {{ optional($user->details)->name ?? $user->name }}
                                                        </h6>

                                                        <span class="text-sm text-secondary-light fw-normal">
                                                            {{ optional($user->details)->phone ?? 'N/A' }}
                                                        </span>

                                                    </div>

                                                </div>

                                            </td>

                                            <td>{{ $user->email }}</td>


                                            {{-- ROLE --}}
                                           <td>

@php
$role = $user->getRoleNames()->first();
@endphp

<span class="bg-success-focus text-success-main px-24 py-4 rounded-pill text-sm">
    {{ $role ?? 'N/A' }}
</span>

</td>
                                            <td>{{ optional(optional(optional($user)->details)->cluster)->name }}</td>
                                            <td>{{ optional(optional(optional($user)->details)->manager)->name }}</td>
                                            <td>{{ optional(optional(optional($user)->details)->teamleader)->name }}</td>


                                           
                                            {{-- ACCOUNT STATUS --}}
                                            <td>

                                                @php $status = optional($user->details)->status; @endphp

                                                @if ($status == '1')
                                                    <span
                                                        class="bg-success-focus text-success-main px-24 py-4 rounded-pill text-sm">Active</span>

                                                @elseif ($status == '2')

                                                    <span
                                                        class="bg-warning-focus text-warning-main px-24 py-4 rounded-pill text-sm">
                                                        Temporary
                                                    </span>

                                                    <br>

                                                    <span class="mt-1 bg-danger text-white px-24 py-4 rounded-pill text-sm">

                                                        Expire : {{ optional($user->details)->account_active_until }}

                                                    </span>

                                                @elseif ($status == '0')
                                                    <span
                                                        class="bg-danger-focus text-danger-main px-24 py-4 rounded-pill text-sm">Inactive</span>

                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif

                                            </td>


                                            {{-- ACTION --}}
                                            <td class="text-center">

                                                <a href="{{ route('lms.users.add', $user->id) }}"
                                                    class="w-32-px h-32-px bg-success-focus text-success-main rounded-circle d-inline-flex align-items-center justify-content-center">

                                                    <iconify-icon icon="lucide:edit"></iconify-icon>

                                                </a>


                                                <a href="javascript:void(0)"
                                                    class="deleteRecord w-32-px h-32-px bg-danger-focus text-danger-main rounded-circle d-inline-flex align-items-center justify-content-center"
                                                    data-id="{{ $user->id }}">

                                                    <iconify-icon icon="mingcute:delete-2-line"></iconify-icon>

                                                </a>

                                            </td>

                                        </tr>

                                    @endforeach

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>

            </div>
        </div>

    </div>

    @include('lms.common.footer')

@endsection