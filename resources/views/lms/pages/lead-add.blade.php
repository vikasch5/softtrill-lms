@extends('lms.common.master')

@section('content')

<div class="dashboard-main-body">
    <div class="row">
        <div class="col-lg-12">

            <div class="card shadow-sm border-0">

                <!-- HEADER -->
                <div class="card-header">
                    <h5 class="mb-0">Create Lead</h5>
                </div>

                <!-- BODY -->
                <div class="card-body">

                    <form method="POST" action="{{ route('lms.leads.store') }}" class="ajaxForm">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                    <label>Select List</label>
                                    <select name="list_id" class="form-control">
                                        <option value="">Create New List</option>
                                        @foreach($lists as $list)
                                            <option value="{{ $list->id }}">{{ $list->list_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            <div class="col-md-4">
                                <label>Name</label>
                                <input type="text" name="name" class="form-control">
                            </div>

                            <div class="col-md-4">
                                <label>Phone *</label>
                                <input type="text" name="phone" class="form-control" required>
                            </div>

                            <div class="col-md-4">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control">
                            </div>

                            <div class="col-md-4">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="new">New</option>
                                    <option value="contacted">Contacted</option>
                                    <option value="qualified">Qualified</option>
                                </select>
                            </div>

                            <!-- DYNAMIC CUSTOM FIELDS -->

                            @foreach($leadFields as $field)

                                <div class="col-md-4">
                                    <label>
                                        {{ $field->name }}
                                        @if($field->is_required) *
                                        @endif
                                    </label>

                                    @if($field->type == 'text')
                                        <input type="text"
                                               name="custom[{{ $field->slug }}]"
                                               class="form-control"
                                               {{ $field->is_required ? 'required' : '' }}>

                                    @elseif($field->type == 'number')
                                        <input type="number"
                                               name="custom[{{ $field->slug }}]"
                                               class="form-control">

                                    @elseif($field->type == 'date')
                                        <input type="date"
                                               name="custom[{{ $field->slug }}]"
                                               class="form-control">

                                    @elseif($field->type == 'textarea')
                                        <textarea name="custom[{{ $field->slug }}]"
                                                  class="form-control"></textarea>

                                    @elseif($field->type == 'select')
                                        <select name="custom[{{ $field->slug }}]" class="form-control">
                                            <option value="">Select</option>
                                            @foreach(json_decode($field->options, true) ?? [] as $opt)
                                                <option value="{{ $opt }}">{{ $opt }}</option>
                                            @endforeach
                                        </select>

                                    @elseif($field->type == 'radio')
                                        @foreach(json_decode($field->options, true) ?? [] as $opt)
                                            <div>
                                                <input type="radio"
                                                       name="custom[{{ $field->slug }}]"
                                                       value="{{ $opt }}"> {{ $opt }}
                                            </div>
                                        @endforeach

                                    @elseif($field->type == 'checkbox')
                                        @foreach(json_decode($field->options, true) ?? [] as $opt)
                                            <div>
                                                <input type="checkbox"
                                                       name="custom[{{ $field->slug }}][]"
                                                       value="{{ $opt }}"> {{ $opt }}
                                            </div>
                                        @endforeach
                                    @endif

                                </div>

                            @endforeach

                        </div>

                        <!-- SUBMIT -->
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                Save Lead
                            </button>
                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>
</div>

@endsection