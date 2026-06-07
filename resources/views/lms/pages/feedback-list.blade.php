@extends('lms.common.master')

@section('content')

<div class="dashboard-main-body">

    <div class="row gy-4">
        <div class="col-lg-12">

            <div class="card">

                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">Feedback Management</h5>

                    <a href="{{ route('lms.feedbacks.add') }}" class="btn btn-primary">
                        + Add Feedback
                    </a>
                </div>

                <div class="card-body">

                    <div class="table-responsive">

                        <table class="table striped-table mb-0">

                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Parent Feedback</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>

                            <tbody>

                                <input type="hidden"
                                    id="deleteUrl"
                                    value="{{ route('lms.feedbacks.delete') }}">

                                @forelse($feedbacks as $key => $feedback)

                                    <tr>

                                        <td>
                                            {{ $loop->iteration }}
                                        </td>

                                        <td>
                                            <strong>{{ $feedback->name }}</strong>

                                            @if($feedback->slug)
                                                <br>
                                                <small class="text-muted">
                                                    {{ $feedback->slug }}
                                                </small>
                                            @endif
                                        </td>

                                        <td>
                                            @if(is_null($feedback->parent_id))
                                                <span class="badge bg-primary">
                                                    Main Feedback
                                                </span>
                                            @else
                                                <span class="badge bg-info">
                                                    Sub Feedback
                                                </span>
                                            @endif
                                        </td>

                                        <td>

                                            @if($feedback->parent)

                                                <span class="badge bg-light text-dark border">
                                                    {{ $feedback->parent->name }}
                                                </span>

                                            @else

                                                <span class="text-muted">
                                                    —
                                                </span>

                                            @endif

                                        </td>

                                        <td>

                                            @if($feedback->status)

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
                                            {{ $feedback->created_at->format('d M Y') }}
                                        </td>

                                        <td class="text-center">

                                            <a href="{{ route('lms.feedbacks.add', $feedback->id) }}"
                                                class="w-32-px h-32-px bg-success-focus text-success-main rounded-circle d-inline-flex align-items-center justify-content-center">

                                                <iconify-icon icon="lucide:edit"></iconify-icon>

                                            </a>

                                            <a href="javascript:void(0)"
                                                class="deleteRecord w-32-px h-32-px bg-danger-focus text-danger-main rounded-circle d-inline-flex align-items-center justify-content-center"
                                                data-id="{{ $feedback->id }}">

                                                <iconify-icon icon="mingcute:delete-2-line"></iconify-icon>

                                            </a>

                                        </td>

                                    </tr>

                                @empty

                                    <tr>
                                        <td colspan="7" class="text-center text-muted">
                                            No feedback records found.
                                        </td>
                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                    @if(method_exists($feedbacks, 'links'))
                        <div class="mt-3">
                            {{ $feedbacks->links() }}
                        </div>
                    @endif

                </div>

            </div>

        </div>
    </div>

</div>

@endsection