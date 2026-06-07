@extends('lms.common.master')

@section('content')

<div class="dashboard-main-body">
    <div class="row">
        <div class="col-lg-12">

            <div class="card shadow-sm border-0">

                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Import Leads</h5>

                    <!-- SAMPLE DOWNLOAD -->
                    <div class="action-btns">
                        <a href="{{ route('lms.leads') }}" class="btn btn-primary">All Leads</a>
                        <button type="button" class="btn btn-info" data-bs-toggle="modal"
                            data-bs-target="#sampleDownloadModal">

                            <i class="ri-download-line"></i>
                            Download Sample

                        </button>
                    </div>
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
                                    <option value="{{ $list->id }}">{{ $list->name }}</option>
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
<div class="modal fade" id="sampleDownloadModal" tabindex="-1" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content sample-modal">

            <!-- Header -->
            <div class="modal-header border-0">

                <div class="d-flex align-items-center">

                    <div class="sample-icon me-3">
                        <i class="ri-file-download-line"></i>
                    </div>

                    <div>
                        <h5 class="modal-title mb-1">
                            Download Sample
                        </h5>

                        <p class="modal-subtitle mb-0">
                            Generate an import template instantly
                        </p>
                    </div>

                </div>

                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal">
                </button>

            </div>

            <!-- Body -->
            <div class="modal-body pt-0">

                <div class="form-card">

                    <label class="form-label fw-semibold mb-2">
                        Lead List
                    </label>

                    <select class="form-select modern-select" id="sample_list_id">

                        <option value="">
                            Choose a Lead List
                        </option>

                        @foreach($lists as $list)

                        <option value="{{ $list->id }}">
                            {{ $list->name }}
                        </option>

                        @endforeach

                    </select>

                </div>

                <div class="template-note mt-3">

                    <i class="ri-information-line"></i>

                    <span>
                        The downloaded file will include all fields configured
                        for the selected lead list.
                    </span>

                </div>

            </div>

            <!-- Footer -->
            <div class="modal-footer border-0">

                <button type="button" class="btn btn-light cancel-btn" data-bs-dismiss="modal">

                    Cancel

                </button>

                <button type="button" class="btn download-btn" id="downloadSampleBtn">

                    <i class="ri-download-cloud-2-line me-1"></i>

                    Download

                </button>

            </div>

        </div>

    </div>

</div>

@endsection

@section('scripts')

<script>

    $(document).on('click', '#downloadSampleBtn', function () {

        let listId = $('#sample_list_id').val();

        if (!listId) {

            toastr.error('Please select a list');

            return;
        }

        window.location.href =
            "{{ route('lms.leads.sample', ['id' => '__ID__']) }}".replace('__ID__', listId);

    });

</script>

@endsection