@extends('lms.common.master')

@section('content')
<div class="dashboard-main-body">
    <div class="row gy-4">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">Edit Permissions: {{ $role->name }}</h5>
                    <a href="{{ route('lms.roles.list') }}" class="btn btn-secondary btn-sm">Back</a>
                </div>

                <div class="card-body">
                    <form action="{{ route('lms.roles.update', $role->id) }}" method="POST" class="ajaxForm">
                        @csrf
                        
                        <div class="row gy-4">
                            @foreach($groupedPermissions as $groupName => $permissions)
                                <div class="col-xxl-3 col-xl-4 col-md-6">
                                    <div class="card h-100 border shadow-none">
                                        <div class="card-header bg-transparent border-bottom pb-3 pt-4">
                                            <h6 class="mb-0 text-capitalize fw-semibold text-primary-light">
                                                <i class="ri-shield-keyhole-line me-1"></i> {{ str_replace('-', ' ', $groupName) }}
                                            </h6>
                                        </div>
                                        <div class="card-body p-20">
                                            <div class="d-flex flex-column gap-3">
                                                @foreach($permissions as $permission)
                                                    @php
                                                        $permParts = explode('.', $permission->name);
                                                        $permLabel = count($permParts) > 1 ? $permParts[1] : $permission->name;
                                                    @endphp
                                                    <div class="form-switch switch-primary d-flex align-items-center gap-3">
                                                        <input 
                                                            class="form-check-input" 
                                                            type="checkbox" 
                                                            role="switch"
                                                            id="perm_{{ $permission->id }}" 
                                                            name="permissions[]" 
                                                            value="{{ $permission->name }}"
                                                            {{ in_array($permission->name, $rolePermissions) ? 'checked' : '' }}
                                                        >
                                                        <label class="form-check-label text-capitalize mb-0 fw-medium text-secondary" for="perm_{{ $permission->id }}">
                                                            {{ str_replace('-', ' ', $permLabel) }}
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" class="btn btn-primary">Save Permissions</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
