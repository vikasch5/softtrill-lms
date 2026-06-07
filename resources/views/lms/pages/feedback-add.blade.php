@extends('lms.common.master')

@section('content')

<div class="dashboard-main-body">

    <div class="row gy-4">

        <div class="col-lg-12">

            <div class="card border-0 shadow-sm">

                <div class="card-header d-flex justify-content-between align-items-center">

                    <h5 class="card-title mb-0">
                        {{ isset($feedback->id) ? 'Edit Feedback' : 'Add Feedback' }}
                    </h5>

                    <a href="{{ route('lms.feedbacks.list') }}"
                        class="btn btn-primary btn-sm">
                        Feedback List
                    </a>

                </div>

                <div class="card-body">

                    <form action="{{ route('lms.feedbacks.store') }}"
                        method="POST"
                        class="row g-4 ajaxForm">

                        @csrf

                        <input type="hidden"
                            name="feedback_id"
                            value="{{ $feedback->id ?? '' }}">

                        <div class="col-12">
                            <h6 class="text-muted mb-3">
                                Feedback Information
                            </h6>
                        </div>

                        <div class="col-md-6">

                            <label class="form-label">
                                Parent Feedback
                            </label>

                            <select name="parent_id"
                                class="form-control">

                                <option value="">
                                    Main Feedback
                                </option>

                                @foreach($parents as $parent)

                                    @if(($feedback->id ?? 0) != $parent->id)

                                        <option value="{{ $parent->id }}"
                                            @selected(($feedback->parent_id ?? '') == $parent->id)>
                                            {{ $parent->name }}
                                        </option>

                                    @endif

                                @endforeach

                            </select>

                            <small class="text-muted">
                                Leave blank for main feedback
                            </small>

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">
                                Feedback Name
                            </label>

                            <input type="text"
                                name="name"
                                class="form-control"
                                placeholder="Enter feedback name"
                                value="{{ old('name', $feedback->name ?? '') }}"
                                required>

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">
                                Status
                            </label>

                            <select name="status"
                                class="form-control">

                                <option value="1"
                                    @selected(($feedback->status ?? 1) == 1)>
                                    Active
                                </option>

                                <option value="0"
                                    @selected(($feedback->status ?? 1) == 0)>
                                    Inactive
                                </option>

                            </select>

                        </div>

                        <div class="col-12">

                            <button type="submit"
                                class="btn btn-primary">

                                {{ isset($feedback->id) ? 'Update Feedback' : 'Create Feedback' }}

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