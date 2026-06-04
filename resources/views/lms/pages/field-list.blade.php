@extends('lms.common.master')

@section('content')

    <div class="dashboard-main-body">

        <div class="row gy-4">
            <div class="col-lg-12">

                <div class="card">

                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">Lead Fields</h5>

                        <a href="{{ route('lms.lead-fields.add') }}" class="btn btn-primary">
                            + Add Field
                        </a>
                    </div>

                    <div class="card-body">

                        <div class="table-responsive">

                            <table class="table striped-table mb-0">

                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>List Name</th>
                                        <th>Total Fields</th>
                                        <th>Status</th>
                                        <th>Created On</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    @forelse($lists as $key => $list)

                                        <tr>

                                            <td>{{ $key + 1 }}</td>

                                            <td>
                                                <strong>{{ $list->name }}</strong>
                                            </td>

                                            <td>
                                                <span class="badge bg-primary">
                                                    {{ $list->total_fields }}
                                                </span>
                                            </td>

                                            <td>
                                                @if($list->is_active)
                                                    <span class="badge bg-success">
                                                        Active
                                                    </span>
                                                @else
                                                    <span class="badge bg-danger">
                                                        Inactive
                                                    </span>
                                                @endif
                                            </td>

                                            <td>
                                                {{ \Carbon\Carbon::parse($list->created_at)->format('d M Y') }}
                                            </td>

                                            <td class="text-center">

                                                <a href="{{ route('lms.lead-fields.add', $list->id) }}"
                                                    class="btn btn-sm btn-primary">
                                                    Manage Fields
                                                </a>
                                                <a href="" class="btn btn-sm btn-danger deleteItem">
                                                    <i class="ri-delete-bin-line"></i>
                                                </a>

                                            </td>

                                        </tr>

                                    @empty

                                        <tr>
                                            <td colspan="6" class="text-center">
                                                No Lists Found
                                            </td>
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