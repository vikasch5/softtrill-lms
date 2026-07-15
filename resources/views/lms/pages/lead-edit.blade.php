@extends('lms.common.master')

@section('content')

    <div class="dashboard-main-body">

        <div class="card">

            <div class="card-header d-flex justify-content-between align-items-center">

                <h5 class="mb-0">
                    Edit Lead #{{ $lead->id }}
                </h5>
                <div class="d-flex gap-2 flex-wrap">

                    <a href="{{ route('lms.leads') }}" class="btn btn-primary">

                        <i class="ri-arrow-left-line"></i>
                        All Leads

                    </a>

                    @role('Admin|Manager|Cluster')
                    <a href="{{ route('lms.leads.add') }}" class="btn btn-primary">

                        <i class="ri-add-line"></i>
                        Add Lead

                    </a>
                    @endrole
                </div>

            </div>

            <div class="card-body">

                <form method="POST" action="{{ route('lms.leads.update') }}" class="ajaxForm">
                    @csrf
                    <input type="hidden" name="lead_id" value="{{ $lead->id }}">

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" value="{{ $lead->name }}" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone_number" value="{{ $lead->phone_number }}" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Email</label>
                            <input type="text" name="email" value="{{ $lead->email }}" class="form-control">
                        </div>

                        @foreach($fields as $field)
                            @php
                                $value =
                                    $lead->data[$field->slug]
                                    ?? '';

                                $options =
                                    $field->options
                                    ? json_decode(
                                        $field->options,
                                        true
                                    )
                                    : [];

                            @endphp


                            <div class="col-md-4 mb-3">

                                <label class="form-label">

                                    {{ $field->name }}

                                    @if($field->is_required)
                                        <span class="text-danger">*</span>
                                    @endif

                                </label>

                                {{-- TEXT --}}
                                @if(
                                        in_array(
                                            $field->type,
                                            [
                                                'text',
                                                'email',
                                                'phone',
                                                'number',
                                                'decimal',
                                                'date',
                                                'datetime'
                                            ]
                                        )
                                    )

                                    <input type="{{ $field->type == 'phone' ? 'text' : $field->type }}"
                                        name="data[{{ $field->slug }}]" value="{{ $value }}" class="form-control">

                                    {{-- TEXTAREA --}}
                                @elseif($field->type == 'textarea')

                                    <textarea name="data[{{ $field->slug }}]" class="form-control" rows="3">{{ $value }}</textarea>

                                    {{-- SELECT --}}
                                @elseif($field->type == 'select')

                                    <select name="data[{{ $field->slug }}]" class="form-select">

                                        <option value="">
                                            Select
                                        </option>

                                        @foreach($options as $option)

                                            <option value="{{ $option }}" {{ $value == $option ? 'selected' : '' }}>

                                                {{ $option }}

                                            </option>

                                        @endforeach

                                    </select>

                                    {{-- RADIO --}}
                                @elseif($field->type == 'radio')

                                    @foreach($options as $option)

                                        <div class="form-check">

                                            <input class="form-check-input" type="radio" name="data[{{ $field->slug }}]"
                                                value="{{ $option }}" {{ $value == $option ? 'checked' : '' }}>

                                            <label class="form-check-label">

                                                {{ $option }}

                                            </label>

                                        </div>

                                    @endforeach

                                    {{-- CHECKBOX --}}
                                @elseif($field->type == 'checkbox')

                                    @php

                                        $selected =
                                            is_array($value)
                                            ? $value
                                            : explode(',', $value);

                                    @endphp

                                    @foreach($options as $option)

                                        <div class="form-check">

                                            <input class="form-check-input" type="checkbox" name="data[{{ $field->slug }}][]"
                                                value="{{ $option }}" {{ in_array($option, $selected) ? 'checked' : '' }}>

                                            <label class="form-check-label">

                                                {{ $option }}

                                            </label>

                                        </div>

                                    @endforeach

                                    {{-- BOOLEAN --}}
                                @elseif($field->type == 'boolean')

                                    <select name="data[{{ $field->slug }}]" class="form-select">

                                        <option value="1" {{ $value == 1 ? 'selected' : '' }}>
                                            Yes
                                        </option>

                                        <option value="0" {{ $value == 0 ? 'selected' : '' }}>
                                            No
                                        </option>

                                    </select>

                                @endif

                            </div>

                        @endforeach

                    </div>

                    <div class="text-end">

                        <button type="submit" class="btn btn-primary">

                            Update Lead

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

@endsection