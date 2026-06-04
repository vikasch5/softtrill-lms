@extends('lms.common.master')

@section('content')

    <div class="dashboard-main-body">
        <div class="row">
            <div class="col-lg-12">

                <div class="card shadow-sm border-0">

                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Custom Lead Fields</h5>

                        <div class="top-btn">
                            <a href="{{ route('lms.lead-fields.list') }}" class="btn btn-outline-primary">
                                <i class="ri-eye-line"></i> View Fields
                            </a>
                            <button type="button" class="btn btn-success" id="addRow">
                                <i class="ri-add-line"></i> Add Field
                            </button>
                        </div>
                    </div>

                    <div class="card-body">

                        <form class="ajaxForm" action="{{ route('lms.lead-fields.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="list_id" value="{{ $list->id ?? '' }}">
                            <div id="fieldsContainer">


@foreach($fieldsData as $index => $field)

<div class="field-row border rounded p-3 mb-3 bg-light">

    <input type="hidden"
        name="fields[{{ $index }}][id]"
        value="{{ $field->id }}">

    <div class="row g-3">

        <div class="col-md-3">
            <label>Field Label</label>

            <input type="text"
                name="fields[{{ $index }}][name]"
                value="{{ $field->name }}"
                class="form-control required">
        </div>

        <div class="col-md-2">

            <label>Type</label>

            <select
                name="fields[{{ $index }}][type]"
                class="form-control field-type">

                @foreach([
                    'text',
                    'textarea',
                    'email',
                    'phone',
                    'number',
                    'decimal',
                    'date',
                    'datetime',
                    'select',
                    'checkbox',
                    'radio',
                    'boolean'
                ] as $type)

                    <option
                        value="{{ $type }}"
                        {{ $field->type == $type ? 'selected' : '' }}>
                        {{ ucfirst($type) }}
                    </option>

                @endforeach

            </select>

        </div>

        <div class="col-md-3 options-wrapper
            {{ in_array($field->type,['select','radio','checkbox']) ? '' : 'd-none' }}">

            <label>Options</label>

            <input
                type="text"
                class="form-control"
                name="fields[{{ $index }}][options]"
                value="{{ $field->options ? implode(',', json_decode($field->options,true)) : '' }}">
        </div>

        <div class="col-md-1">

            <label>Order</label>

            <input
                type="number"
                name="fields[{{ $index }}][sort_order]"
                value="{{ $field->sort_order }}"
                class="form-control">
        </div>

        <div class="col-md-5">

            <div class="row">

                <div class="col-3">
                    <label>Req.</label>

                    <select
                        name="fields[{{ $index }}][is_required]"
                        class="form-control">

                        <option value="0" {{ !$field->is_required ? 'selected' : '' }}>No</option>
                        <option value="1" {{ $field->is_required ? 'selected' : '' }}>Yes</option>

                    </select>
                </div>

                <div class="col-3">
                    <label>Filter</label>

                    <select
                        name="fields[{{ $index }}][is_filterable]"
                        class="form-control">

                        <option value="0" {{ !$field->is_filterable ? 'selected' : '' }}>No</option>
                        <option value="1" {{ $field->is_filterable ? 'selected' : '' }}>Yes</option>

                    </select>
                </div>

                <div class="col-3">
                    <label>Search</label>

                    <select
                        name="fields[{{ $index }}][is_searchable]"
                        class="form-control">

                        <option value="0" {{ !$field->is_searchable ? 'selected' : '' }}>No</option>
                        <option value="1" {{ $field->is_searchable ? 'selected' : '' }}>Yes</option>

                    </select>
                </div>

                <div class="col-3">
                    <label>Unique</label>

                    <select
                        name="fields[{{ $index }}][is_unique]"
                        class="form-control">

                        <option value="0" {{ !$field->is_unique ? 'selected' : '' }}>No</option>
                        <option value="1" {{ $field->is_unique ? 'selected' : '' }}>Yes</option>

                    </select>
                </div>

            </div>

        </div>

        <div class="col-md-1 text-end mt-5">

            <button
                type="button"
                class="btn btn-danger removeRow">

                Remove

            </button>

        </div>

    </div>

</div>

@endforeach

                            </div>

                            <button type="submit" class="btn btn-primary mt-2">
                                Save Fields
                            </button>

                        </form>

                    </div>
                </div>

            </div>
        </div>
    </div>

@endsection
@section('scripts')
    <script>
        $(document).ready(function () {

            let rowIndex = {{ isset($fields) ? count($fields) : 1 }};

            $('#addRow').click(function () {

                let html = $('.field-row:first').clone();

                html.find('input, select').each(function () {

                    let name = $(this).attr('name');
                    name = name.replace(/\[\d+\]/, '[' + rowIndex + ']');

                    $(this).attr('name', name).val('');
                });

                html.find('input[type=checkbox]').prop('checked', false);
                html.find('.options-wrapper').addClass('d-none');

                $('#fieldsContainer').append(html);

                rowIndex++;
            });

            $(document).on('click', '.removeRow', function () {
                $(this).closest('.field-row').remove();
            });

            $('[data-bs-toggle="tooltip"]').tooltip();

        });

        $(document).on('change', 'select[name*="[type]"]', function () {

            let type = $(this).val();

            let row = $(this).closest('.field-row');

            if (type === 'select') {
                row.find('.options-wrapper').removeClass('d-none');
            } else {
                row.find('.options-wrapper').addClass('d-none');
            }

        });
    </script>
@endsection