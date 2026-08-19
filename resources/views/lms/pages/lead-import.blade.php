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

                            <div class="col-md-3">
                                <label>Select List</label>
                                <select name="list_id" class="form-control" id="list_id_select">
                                    <option value="">Create New List</option>
                                    @foreach($lists as $list)
                                    <option value="{{ $list->id }}">{{ $list->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <!-- NEW LIST NAME -->
                            <div class="col-md-3" id="new_list_name_container">
                                <label>New List Name</label>
                                <input type="text" name="list_name" class="form-control" placeholder="Auto-generate if blank">
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
    
    // Toggle new list name field
    $('#list_id_select').on('change', function() {
        if ($(this).val() === '') {
            $('#new_list_name_container').show();
        } else {
            $('#new_list_name_container').hide();
        }
    });
    
    // Handle import response
    $(document).ajaxSuccess(function(event, xhr, settings, data) {
        if (settings.url === "{{ route('lms.leads.import.save') }}" && data && data.success) {
            if (data.stats) {
                let total = data.stats.total || 1; // Prevent divide by zero
                let pctImported = ((data.stats.imported / total) * 100).toFixed(1);
                let pctDuplicates = ((data.stats.duplicates / total) * 100).toFixed(1);
                let pctFailed = ((data.stats.failed / total) * 100).toFixed(1);

                let html = `
                    <style>
                    .import-report-card {
                        background: #ffffff;
                        border-radius: 16px;
                        box-shadow: 0 12px 36px rgba(0, 0, 0, 0.08);
                        border: 1px solid rgba(0,0,0,0.05);
                        overflow: hidden;
                        animation: slideUpFade 0.5s ease-out forwards;
                    }
                    @keyframes slideUpFade {
                        0% { opacity: 0; transform: translateY(20px); }
                        100% { opacity: 1; transform: translateY(0); }
                    }
                    .import-header {
                        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
                        border-bottom: 1px solid rgba(0,0,0,0.04);
                    }
                    .stat-pill {
                        background: #fbfbfc;
                        border-radius: 12px;
                        transition: transform 0.2s ease, box-shadow 0.2s ease;
                        border: 1px solid rgba(0,0,0,0.03);
                    }
                    .stat-pill:hover {
                        transform: translateY(-3px);
                        box-shadow: 0 8px 20px rgba(0,0,0,0.06);
                    }
                    .progress-multi {
                        height: 10px;
                        border-radius: 5px;
                        background: #f1f3f5;
                        overflow: hidden;
                        display: flex;
                        box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
                    }
                    .progress-multi .imported { background: var(--bs-primary, #6f42c1); transition: width 1s ease-out; }
                    .progress-multi .duplicates { background: #f59e0b; transition: width 1s ease-out; }
                    .progress-multi .failed { background: #ef4444; transition: width 1s ease-out; }
                    </style>

                    <div class="import-report-card mt-4">
                        <div class="import-header p-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-primary bg-opacity-10 d-flex justify-content-center align-items-center me-3" style="width:56px;height:56px;">
                                    <i class="ri-checkbox-circle-fill text-primary" style="font-size: 32px;"></i>
                                </div>
                                <div>
                                    <h4 class="mb-1 fw-bolder text-dark" style="letter-spacing: -0.5px;">Import Successful</h4>
                                    <p class="text-muted mb-0" style="font-size: 14px;">Your data has been successfully processed.</p>
                                </div>
                            </div>
                            <div class="text-md-end text-start">
                                <div class="text-muted text-uppercase fw-bold" style="font-size:11px; letter-spacing:1px;">Total Processed</div>
                                <h2 class="mb-0 fw-bold text-dark" style="letter-spacing: -1px;">${data.stats.total} <span class="fs-6 text-muted fw-normal">rows</span></h2>
                            </div>
                        </div>
                        
                        <div class="p-4 pt-4">
                            <!-- Progress Bar -->
                            <div class="progress-multi mb-4">
                                <div class="imported" style="width: ${pctImported}%" title="Imported: ${pctImported}%"></div>
                                <div class="duplicates" style="width: ${pctDuplicates}%" title="Duplicates: ${pctDuplicates}%"></div>
                                <div class="failed" style="width: ${pctFailed}%" title="Failed: ${pctFailed}%"></div>
                            </div>
                            
                            <!-- Stats Grid -->
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="stat-pill p-3 d-flex align-items-center">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 44px; height: 44px; flex-shrink: 0;">
                                            <i class="ri-check-double-line fs-4"></i>
                                        </div>
                                        <div>
                                            <div class="text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Imported</div>
                                            <div class="d-flex align-items-baseline gap-2 mt-1">
                                                <h4 class="mb-0 fw-bold text-dark">${data.stats.imported}</h4>
                                                <span class="text-primary fw-bold" style="font-size: 12px;">${pctImported}%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="stat-pill p-3 d-flex align-items-center">
                                        <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 44px; height: 44px; flex-shrink: 0;">
                                            <i class="ri-file-copy-line fs-4"></i>
                                        </div>
                                        <div>
                                            <div class="text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Duplicates</div>
                                            <div class="d-flex align-items-baseline gap-2 mt-1">
                                                <h4 class="mb-0 fw-bold text-dark">${data.stats.duplicates}</h4>
                                                <span class="text-warning fw-bold" style="font-size: 12px;">${pctDuplicates}%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="stat-pill p-3 d-flex align-items-center">
                                        <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 44px; height: 44px; flex-shrink: 0;">
                                            <i class="ri-error-warning-line fs-4"></i>
                                        </div>
                                        <div>
                                            <div class="text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Failed</div>
                                            <div class="d-flex align-items-baseline gap-2 mt-1">
                                                <h4 class="mb-0 fw-bold text-dark">${data.stats.failed}</h4>
                                                <span class="text-danger fw-bold" style="font-size: 12px;">${pctFailed}%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                $('#importResult').html(html);
                
                // Clear the form
                $('#file').val('');
                $('input[name="list_name"]').val('');
            }
        }
    });

</script>

@endsection