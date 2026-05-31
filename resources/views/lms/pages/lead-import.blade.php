@extends('lms.common.master')

@section('content')

<div class="dashboard-main-body">
    <div class="row">
        <div class="col-lg-12">

            <div class="card shadow-sm border-0">

                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Import Leads</h5>

                    <!-- SAMPLE DOWNLOAD -->
                    <a href="{{ route('lms.leads.sample') }}" class="btn btn-info">
                        <i class="ri-download-line"></i> Download Sample
                    </a>
                </div>

                <div class="card-body">

                    <form method="POST" class="ajaxForm" action="{{ route('lms.leads.import.save') }}"
                        enctype="multipart/form-data">
                        @csrf

                        <div class="row">

                            <div class="col-md-4">
                                <label>Select List</label>
                                <select name="list_id" class="form-control">
                                    <option value="">Create New List</option>
                                    @foreach($lists as $list)
                                    <option value="{{ $list->id }}">{{ $list->list_code }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <!-- FILE INPUT -->
                            <div class="col-md-4">
                                <label class="fw-semibold">Upload File (CSV / Excel)</label>
                                <input type="file" name="file" id="file" class="form-control"
                                    accept=".csv, .xlsx, .xls">
                            </div>

                            <!-- BUTTON -->
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="ri-upload-cloud-line"></i> Import Leads
                                </button>
                            </div>

                        </div>

                    </form>

                    <!-- RESULT -->
                    <div id="importResult" class="mt-3"></div>

                </div>
            </div>

        </div>
    </div>
</div>

@endsection