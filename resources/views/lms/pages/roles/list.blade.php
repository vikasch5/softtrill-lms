@extends('lms.common.master')

@section('content')

<div class="dashboard-main-body">
    <div class="row gy-4">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">Roles & Permissions</h5>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table striped-table mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Role Name</th>
                                    <th class="text-center">Total Permissions</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($roles as $key => $role)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>
                                            <strong>{{ $role->name }}</strong>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary rounded-pill">
                                                {{ $role->permissions->count() }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('lms.roles.edit', $role->id) }}" class="btn btn-sm btn-primary">
                                                <i class="ri-edit-line"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">No Roles Found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
