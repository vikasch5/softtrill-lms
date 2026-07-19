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

<div class="dashboard-main-body">

    <div class="row gy-4">

        <div class="col-lg-12">

            <div class="card border-0 shadow-sm">

                <div class="card-header d-flex align-items-center justify-content-between">

                    <h5 class="card-title mb-0">
                        {{ isset($widget->id) ? 'Edit Dashboard Widget' : 'Add Dashboard Widget' }}
                    </h5>

                    <a href="{{ route('lms.dashboard.widgets.list') }}"
                        class="btn btn-primary btn-sm">

                        Widget List

                    </a>

                </div>

                <div class="card-body">

                    <form action="{{ route('lms.dashboard.widgets.store') }}"
                        method="POST"
                        class="row g-4 ajaxForm">

                        @csrf

                        <input type="hidden"
                            name="widget_id"
                            value="{{ $widget->id ?? '' }}">

                        <!-- Basic -->

                        <div class="col-12">
                            <div class="section-label">
                                Widget Information
                            </div>
                        </div>

                        <div class="col-lg-4">

                            <label class="form-label">
                                Widget Title
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                name="title"
                                value="{{ old('title',$widget->title ?? '') }}"
                                required>

                        </div>

                        <div class="col-lg-4">

                            <label class="form-label">
                                Lead List
                            </label>

                            <select
                                class="form-control"
                                name="list_id"
                                id="list_id"
                                required>

                                <option value="">
                                    Select List
                                </option>

                                @foreach($lists as $list)

                                <option
                                    value="{{ $list->id }}"
                                    @selected(old('list_id',$widget->list_id ?? '')==$list->id)>

                                    {{ $list->name }}

                                </option>

                                @endforeach

                            </select>

                        </div>

                        <div class="col-lg-4">

                            <label class="form-label">
                                Field
                            </label>

                            <select
                                class="form-control"
                                name="field_id"
                                id="field_id">

                                <option value="">
                                    Select Field
                                </option>

                            </select>

                        </div>

                        <!-- Chart -->

                        <div class="col-12 mt-3">
                            <div class="section-label">
                                Chart Settings
                            </div>
                        </div>

                        <div class="col-lg-4">

                            <label class="form-label">
                                Chart Type
                            </label>

                            <select
                                class="form-control"
                                name="chart_type">

                                <option value="card" @selected(old('chart_type', $widget->chart_type ?? '') == 'card')>Card</option>
                                <option value="bar" @selected(old('chart_type', $widget->chart_type ?? '') == 'bar')>Bar</option>
                                <option value="line" @selected(old('chart_type', $widget->chart_type ?? '') == 'line')>Line</option>
                                <option value="pie" @selected(old('chart_type', $widget->chart_type ?? '') == 'pie')>Pie</option>
                                <option value="doughnut" @selected(old('chart_type', $widget->chart_type ?? '') == 'doughnut')>Doughnut</option>
                                <option value="area" @selected(old('chart_type', $widget->chart_type ?? '') == 'area')>Area</option>

                            </select>

                        </div>

                        <div class="col-lg-4">

                            <label class="form-label">
                                Aggregate
                            </label>

                            <select
                                class="form-control"
                                name="aggregate">

                                <option value="count" @selected(old('aggregate', $widget->aggregate ?? '') == 'count')>Count</option>
                                <option value="sum" @selected(old('aggregate', $widget->aggregate ?? '') == 'sum')>Sum</option>
                                <option value="avg" @selected(old('aggregate', $widget->aggregate ?? '') == 'avg')>Average</option>
                                <option value="min" @selected(old('aggregate', $widget->aggregate ?? '') == 'min')>Minimum</option>
                                <option value="max" @selected(old('aggregate', $widget->aggregate ?? '') == 'max')>Maximum</option>

                            </select>

                        </div>

                        <div class="col-lg-4">

                            <label class="form-label">
                                Group By
                            </label>

                            <select
                                class="form-control"
                                name="group_by">

                                <option value="" @selected(old('group_by', $widget->group_by ?? '') == '')>
                                    None
                                </option>

                                <option value="day" @selected(old('group_by', $widget->group_by ?? '') == 'day')>
                                    Day
                                </option>

                                <option value="week" @selected(old('group_by', $widget->group_by ?? '') == 'week')>
                                    Week
                                </option>

                                <option value="month" @selected(old('group_by', $widget->group_by ?? '') == 'month')>
                                    Month
                                </option>

                                <option value="year" @selected(old('group_by', $widget->group_by ?? '') == 'year')>
                                    Year
                                </option>

                            </select>

                        </div>

                        <!-- Layout -->

                        <div class="col-12 mt-3">
                            <div class="section-label">
                                Layout
                            </div>
                        </div>

                        <div class="col-lg-4">

                            <label class="form-label">
                                Width
                            </label>

                            <select
                                class="form-control"
                                name="width">

                                <option value="3" @selected(old('width', $widget->width ?? '') == '3')>3 Columns</option>
                                <option value="4" @selected(old('width', $widget->width ?? '') == '4')>4 Columns</option>
                                <option value="6" @selected(old('width', $widget->width ?? '6') == '6')>6 Columns</option>
                                <option value="12" @selected(old('width', $widget->width ?? '') == '12')>Full Width</option>

                            </select>

                        </div>

                        <div class="col-lg-4">

                            <label class="form-label">
                                Height
                            </label>

                            <input
                                type="number"
                                class="form-control"
                                name="height"
                                value="{{ old('height', $widget->height ?? 350) }}">

                        </div>

                        <div class="col-lg-4">

                            <label class="form-label">
                                Sort Order
                            </label>

                            <input
                                type="number"
                                class="form-control"
                                name="sort_order"
                                value="{{ old('sort_order', $widget->sort_order ?? 1) }}">

                        </div>

                        <!-- Status -->

                        <div class="col-12 mt-3">
                            <div class="section-label">
                                Status
                            </div>
                        </div>

                        <div class="col-lg-4">

                            <label class="form-label">
                                Active
                            </label>

                            <select
                                class="form-control"
                                name="is_active">

                                <option value="1" @selected(old('is_active', $widget->is_active ?? 1) == 1)>
                                    Yes
                                </option>

                                <option value="0" @selected(old('is_active', $widget->is_active ?? 1) == 0)>
                                    No
                                </option>

                            </select>

                        </div>

                        <div class="col-12">

                            <button
                                type="submit"
                                class="btn btn-primary px-4">

                                {{ isset($widget->id) ? 'Update Widget' : 'Create Widget' }}

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
$(document).ready(function() {

    let preselectedFieldId = "{{ old('field_id', $widget->field_id ?? '') }}";

    function loadFields(listId, selectedFieldId) {
        if (!listId) {
            $('#field_id').html('<option value="">Select Field</option>');
            return;
        }

        let url = "{{ route('lms.dashboard.widgets.fields', ':id') }}";
        url = url.replace(':id', listId);

        $.get(url, function (response) {
            $('#field_id').html(response);
            if (selectedFieldId) {
                $('#field_id').val(selectedFieldId);
            }
        });
    }

    $('#list_id').change(function () {
        loadFields($(this).val(), null);
    });

    // On initial load (e.g., when editing), fetch fields if list_id is selected
    let initialListId = $('#list_id').val();
    if (initialListId) {
        loadFields(initialListId, preselectedFieldId);
    }

});
</script>

@endsection