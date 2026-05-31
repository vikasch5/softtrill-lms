@extends('lms.common.master')

@section('content')

<div class="dashboard-main-body">

    <div class="row gy-4">
        <div class="col-lg-12">

            <div class="card">

                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">Lead Fields</h5>

                    <a href="{{ route('lms.lead-fields.create') }}" class="btn btn-primary">
                        + Add Field
                    </a>
                </div>

                <div class="card-body">

                    <div class="table-responsive">

                        <table class="table striped-table mb-0">

                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Field Label</th>
                                    <th>Type</th>
                                    <th>Mandatory</th>
                                    <th>Searchable</th>
                                    <th>Fast Access</th>
                                    <th>Options</th>
                                    <th>Order</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>

                            <tbody>

                                <input type="hidden" id="deleteUrl" value="{{ route('lms.lead-fields.delete') }}">

                                @foreach ($fields as $key => $field)

                                    <tr>

                                        <td>{{ $key + 1 }}</td>

                                        <!-- Label -->
                                        <td>
                                            <strong>{{ $field->name }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $field->slug }}</small>
                                        </td>

                                        <!-- Type -->
                                        <td>
                                            <span class="badge bg-info">
                                                {{ ucfirst($field->type) }}
                                            </span>
                                        </td>

                                        <!-- Required -->
                                        <td>
                                            @if ($field->is_required)
                                                <span class="badge bg-danger">Yes</span>
                                            @else
                                                <span class="badge bg-secondary">No</span>
                                            @endif
                                        </td>

                                        <!-- Filterable -->
                                        <td>
                                            @if ($field->is_filterable)
                                                <span class="badge bg-success">Yes</span>
                                            @else
                                                <span class="badge bg-secondary">No</span>
                                            @endif
                                        </td>

                                        <!-- Promoted -->
                                        <td>
                                            @if ($field->is_promoted)
                                                <span class="badge bg-primary">Yes</span>
                                            @else
                                                <span class="badge bg-secondary">No</span>
                                            @endif
                                        </td>

                                        <!-- Options -->
                                        <td>
                                            @if ($field->type == 'select' && $field->options)
                                                @php $opts = json_decode($field->options, true); @endphp

                                                @foreach ($opts as $opt)
                                                    <span class="badge bg-light text-dark border">
                                                        {{ $opt }}
                                                    </span>
                                                @endforeach
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>

                                        <!-- Sort -->
                                        <td>{{ $field->sort_order }}</td>

                                        <!-- Action -->
                                        <td class="text-center">

                                            <a href="{{ route('lms.lead-fields.edit', $field->id) }}"
                                                class="w-32-px h-32-px bg-success-focus text-success-main rounded-circle d-inline-flex align-items-center justify-content-center">

                                                <iconify-icon icon="lucide:edit"></iconify-icon>
                                            </a>

                                            <a href="javascript:void(0)"
                                                class="deleteRecord w-32-px h-32-px bg-danger-focus text-danger-main rounded-circle d-inline-flex align-items-center justify-content-center"
                                                data-id="{{ $field->id }}">

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

@endsection