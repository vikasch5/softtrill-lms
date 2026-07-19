@extends('lms.common.master')

@section('content')

<div class="dashboard-main-body">

    <div class="row gy-4">
        <div class="col-lg-12">

            <div class="card">

                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">Dashboard Widgets</h5>

                    <a href="{{ route('lms.dashboard.widgets') }}" class="btn btn-primary btn-sm">
                        + Add Widget
                    </a>
                </div>

                <div class="card-body">

                    <div class="table-responsive">

                        <table class="table striped-table mb-0">

                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Title</th>
                                    <th>Chart Type</th>
                                    <th>Aggregate</th>
                                    <th>Width</th>
                                    <th>Sort Order</th>
                                    <th>Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>

                            <tbody>

                                @forelse($widgets as $key => $widget)

                                    <tr>

                                        <td>{{ $key + 1 }}</td>

                                        <td><strong>{{ $widget->title }}</strong></td>

                                        <td>
                                            <span class="badge bg-info text-capitalize">
                                                {{ $widget->chart_type }}
                                            </span>
                                        </td>

                                        <td class="text-capitalize">{{ $widget->aggregate }}</td>

                                        <td>{{ $widget->width }} col</td>

                                        <td>{{ $widget->sort_order }}</td>

                                        <td>
                                            @if($widget->is_active)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-danger">Inactive</span>
                                            @endif
                                        </td>

                                        <td class="text-center">
                                            <a href="{{ route('lms.dashboard.widgets.edit', $widget->id) }}"
                                               class="btn btn-sm btn-primary">
                                                Edit
                                            </a>
                                        </td>

                                    </tr>

                                @empty

                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            No widgets found. <a href="{{ route('lms.dashboard.widgets') }}">Add one</a>.
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

@include('lms.common.footer')

@endsection
