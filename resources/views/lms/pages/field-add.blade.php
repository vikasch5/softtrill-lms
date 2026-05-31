@extends('lms.common.master')

@section('content')

    <div class="dashboard-main-body">
        <div class="row">
            <div class="col-lg-12">

                <div class="card shadow-sm border-0">

                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Custom Lead Fields</h5>

                        <button type="button" class="btn btn-success" id="addRow">
                            <i class="ri-add-line"></i> Add Field
                        </button>
                    </div>

                    <div class="card-body">

                        <form class="ajaxForm" action="{{ route('lms.lead-fields.store') }}" method="POST">
                            @csrf
                            <div id="fieldsContainer">

                                @php
                                    $fieldsData = $fields ?? [null]; // for create → 1 empty row
                                @endphp

                                @foreach($fieldsData as $index => $field)
                                    @php
                                    var_dump($field);
                                    @endphp

                                    <div class="field-row border rounded p-3 mb-3 bg-light">

                                        <input type="hidden" name="fields[{{ $index }}][id]" value="{{ $field->id ?? '' }}">

                                        <div class="row g-3 align-items-end">

                                            <!-- LABEL -->
                                            <div class="col-md-3">
                                                <label class="fw-semibold">Field Label</label>
                                                <input type="text" name="fields[{{ $index }}][name]"
                                                    value="{{ $field->name ?? '' }}"
                                                    class="form-control form-control-sm required" placeholder="e.g. PAN Number">
                                            </div>

                                            <!-- TYPE -->
                                            <div class="col-md-3">
                                                <label class="fw-semibold">Field Type</label>
                                                <select name="fields[{{ $index }}][type]"
                                                    class="form-control form-control-sm required">

                                                    <option value="text" {{ ($field->type ?? '') == 'text' ? 'selected' : '' }}>
                                                        Text</option>
                                                    <option value="number" {{ ($field->type ?? '') == 'number' ? 'selected' : '' }}>Number</option>
                                                    <option value="date" {{ ($field->type ?? '') == 'date' ? 'selected' : '' }}>
                                                        Date</option>
                                                    <option value="select" {{ ($field->type ?? '') == 'select' ? 'selected' : '' }}>Dropdown</option>

                                                </select>
                                            </div>

                                            <!-- OPTIONS -->
                                            <div
                                                class="col-md-4 options-wrapper {{ ($field->type ?? '') == 'select' ? '' : 'd-none' }}">
                                                <label class="fw-semibold">Dropdown Options</label>

                                                <input type="text" name="fields[{{ $index }}][options]"
                                                    value="{{ isset($field->options) ? implode(',', json_decode($field->options, true)) : '' }}"
                                                    class="form-control form-control-sm"
                                                    placeholder="e.g. Delhi, Mumbai, Noida">
                                            </div>

                                            <!-- SORT -->
                                            <div class="col-md-2">
                                                <label class="fw-semibold">Order</label>
                                                <input type="number" name="fields[{{ $index }}][sort_order]"
                                                    value="{{ $field->sort_order ?? 0 }}" class="form-control form-control-sm">
                                            </div>

                                            <!-- SWITCHES -->
                                            <div class="col-md-3">
                                                <div class="row g-2">

                                                    <!-- Required -->
                                                    <div class="col-4">
                                                        <label class="small fw-semibold">Required</label>
                                                        <select name="fields[{{ $index }}][is_required]"
                                                            class="form-select form-control-sm">
                                                            <option value="0" {{ ($field->is_required ?? 0) == 0 ? 'selected' : '' }}>No</option>
                                                            <option value="1" {{ ($field->is_required ?? 0) == 1 ? 'selected' : '' }}>Yes</option>
                                                        </select>
                                                    </div>

                                                    <!-- Filter -->
                                                    <div class="col-4">
                                                        <label class="small fw-semibold">Filter</label>
                                                        <select name="fields[{{ $index }}][is_filterable]"
                                                            class="form-select form-control-sm">
                                                            <option value="0" {{ ($field->is_filterable ?? 0) == 0 ? 'selected' : '' }}>No</option>
                                                            <option value="1" {{ ($field->is_filterable ?? 0) == 1 ? 'selected' : '' }}>Yes</option>
                                                        </select>
                                                    </div>

                                                    <!-- Promote -->
                                                    <div class="col-4">
                                                        <label class="small fw-semibold">Promote</label>
                                                        <select name="fields[{{ $index }}][is_promoted]"
                                                            class="form-select form-control-sm">
                                                            <option value="0" {{ ($field->is_promoted ?? 0) == 0 ? 'selected' : '' }}>No</option>
                                                            <option value="1" {{ ($field->is_promoted ?? 0) == 1 ? 'selected' : '' }}>Yes</option>
                                                        </select>
                                                    </div>

                                                </div>
                                            </div>

                                            <!-- DELETE -->
                                            <div class="col-md-1">
                                                <button type="button" class="btn btn-danger btn-sm w-100 removeRow">
                                                    <i class="ri-delete-bin-line"></i>
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