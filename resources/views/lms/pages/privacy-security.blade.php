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

    .preview-box {
        background: #f8f9fa;
        border: 1px dashed #dee2e6;
        border-radius: 6px;
        padding: 15px;
        margin-top: 15px;
        font-family: monospace;
    }
    
    .role-table th {
        font-weight: 600;
        background-color: #f8f9fa;
    }
</style>

<div class="dashboard-main-body">

    <div class="row gy-4">
        <div class="col-lg-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">
                        Privacy & Security Settings
                    </h5>
                    <p class="text-muted mb-0 small">Control how sensitive lead data is displayed and accessed.</p>
                </div>

                <div class="card-body">
                    <form action="{{ route('lms.settings.privacy.update') }}" method="POST" class="row g-4 ajaxForm" data-notify-type="toast">
                        @csrf

                        <!-- Card 1: Lead Data Privacy (Mobile) -->
                        <div class="col-12 mt-4">
                            <div class="section-label">Mobile Number Privacy</div>
                            <p class="text-muted small mb-3">Control how mobile numbers are displayed to users.</p>
                        </div>

                        <div class="col-lg-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input mask-trigger" type="radio" name="privacy_mobile_visibility" id="mobile_show_full" value="full" {{ ($settings['mobile']['visibility'] ?? 'full') == 'full' ? 'checked' : '' }}>
                                <label class="form-check-label" for="mobile_show_full">Show Full Number</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input mask-trigger" type="radio" name="privacy_mobile_visibility" id="mobile_mask" value="mask" {{ ($settings['mobile']['visibility'] ?? '') == 'mask' ? 'checked' : '' }}>
                                <label class="form-check-label" for="mobile_mask">Mask Number</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input mask-trigger" type="radio" name="privacy_mobile_visibility" id="mobile_mask_last" value="mask_last" {{ ($settings['mobile']['visibility'] ?? '') == 'mask_last' ? 'checked' : '' }}>
                                <label class="form-check-label" for="mobile_mask_last">Show Only Last N Digits</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input mask-trigger" type="radio" name="privacy_mobile_visibility" id="mobile_mask_first" value="mask_first" {{ ($settings['mobile']['visibility'] ?? '') == 'mask_first' ? 'checked' : '' }}>
                                <label class="form-check-label" for="mobile_mask_first">Show Only First N Digits</label>
                            </div>
                        </div>

                        <div class="col-lg-3 digit-config" style="{{ in_array(($settings['mobile']['visibility'] ?? 'full'), ['mask_last', 'mask_first', 'mask']) ? '' : 'display:none;' }}">
                            <label class="form-label">Visible Digits (N)</label>
                            <input type="number" class="form-control mask-trigger" name="privacy_mobile_visible_digits" value="{{ $settings['mobile']['visible_digits'] ?? 5 }}" min="0" max="15">
                        </div>

                        <div class="col-lg-3 digit-config" style="{{ in_array(($settings['mobile']['visibility'] ?? 'full'), ['mask_last', 'mask_first', 'mask']) ? '' : 'display:none;' }}">
                            <label class="form-label">Mask Character</label>
                            <select class="form-select mask-trigger" name="privacy_mobile_mask_character">
                                <option value="X" {{ ($settings['mobile']['mask_character'] ?? 'X') == 'X' ? 'selected' : '' }}>X</option>
                                <option value="*" {{ ($settings['mobile']['mask_character'] ?? '') == '*' ? 'selected' : '' }}>*</option>
                                <option value="•" {{ ($settings['mobile']['mask_character'] ?? '') == '•' ? 'selected' : '' }}>•</option>
                            </select>
                        </div>
                        
                        <div class="col-lg-3">
                            <div class="preview-box">
                                <strong>Mobile Preview:</strong><br>
                                Original: <span id="preview_orig_mobile">9876543210</span><br>
                                Displayed: <span id="preview_masked_mobile" class="text-primary fw-bold">9876543210</span>
                            </div>
                        </div>


                        <!-- Card 2: Email Privacy -->
                        <div class="col-12 mt-5">
                            <div class="section-label">Email Privacy</div>
                            <p class="text-muted small mb-3">Control how email addresses are displayed to users.</p>
                        </div>
                        
                        <div class="col-lg-4">
                            <div class="form-check mb-2">
                                <input class="form-check-input mask-trigger" type="radio" name="privacy_email_visibility" id="email_show_full" value="full" {{ ($settings['email']['visibility'] ?? 'full') == 'full' ? 'checked' : '' }}>
                                <label class="form-check-label" for="email_show_full">Show Full Email</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input mask-trigger" type="radio" name="privacy_email_visibility" id="email_mask" value="mask" {{ ($settings['email']['visibility'] ?? '') == 'mask' ? 'checked' : '' }}>
                                <label class="form-check-label" for="email_mask">Mask Email</label>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="preview-box">
                                <strong>Email Preview:</strong><br>
                                Original: <span id="preview_orig_email">john.doe@gmail.com</span><br>
                                Displayed: <span id="preview_masked_email" class="text-primary fw-bold">john.doe@gmail.com</span>
                            </div>
                        </div>


                        <!-- Card 3: Sensitive Field Visibility -->
                        <div class="col-12 mt-5">
                            <div class="section-label">Field Visibility</div>
                            <p class="text-muted small mb-3">Control overall visibility and masking for specific lead fields.</p>
                        </div>

                        <div class="col-12">
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm role-table">
                                    <thead>
                                        <tr>
                                            <th>Field</th>
                                            <th class="text-center" style="width: 150px;">Visible</th>
                                            <th class="text-center" style="width: 150px;">Masked</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $fixedFields = [
                                                'mobile' => 'Mobile Number', 
                                                'email' => 'Email', 
                                                'alternate_number' => 'Alternate Number', 
                                                'address' => 'Address'
                                            ];
                                        @endphp

                                        @foreach($fixedFields as $slug => $name)
                                        <tr>
                                            <td>{{ $name }} <span class="badge bg-light text-dark ms-2">System</span></td>
                                            <td class="text-center">
                                                <div class="form-check form-switch d-flex justify-content-center">
                                                    <input class="form-check-input" type="checkbox" name="privacy_fields_{{ $slug }}_visible" value="1" {{ ($settings['fields'][$slug]['visible'] ?? 1) ? 'checked' : '' }}>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check form-switch d-flex justify-content-center">
                                                    <input class="form-check-input" type="checkbox" name="privacy_fields_{{ $slug }}_masked" value="1" {{ ($settings['fields'][$slug]['masked'] ?? 0) ? 'checked' : '' }}>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach

                                        @foreach($leadFields as $field)
                                        <tr>
                                            <td>{{ $field->name }} <span class="badge bg-light text-dark ms-2">Dynamic</span></td>
                                            <td class="text-center">
                                                <div class="form-check form-switch d-flex justify-content-center">
                                                    <input class="form-check-input" type="checkbox" name="privacy_fields[{{ $field->slug }}][visible]" value="1" {{ ($settings['fields'][$field->slug]['visible'] ?? 1) ? 'checked' : '' }}>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check form-switch d-flex justify-content-center">
                                                    <input class="form-check-input" type="checkbox" name="privacy_fields[{{ $field->slug }}][masked]" value="1" {{ ($settings['fields'][$field->slug]['masked'] ?? 0) ? 'checked' : '' }}>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>


                        <!-- Card 4: Lead Data Access -->
                        <div class="col-12 mt-5">
                            <div class="section-label">Sensitive Data Actions</div>
                            <p class="text-muted small mb-3">Control what actions users can perform with sensitive lead information.</p>
                        </div>

                        <div class="col-md-6">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="privacy_actions_copy_mobile" id="action_copy_mobile" value="1" {{ ($settings['actions']['copy_mobile'] ?? 1) ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="action_copy_mobile">Allow copying mobile numbers</label>
                                <div class="text-muted small">Users with access to the lead can copy mobile numbers to their clipboard.</div>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="privacy_actions_copy_email" id="action_copy_email" value="1" {{ ($settings['actions']['copy_email'] ?? 1) ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="action_copy_email">Allow copying email addresses</label>
                                <div class="text-muted small">Users with access to the lead can copy email addresses to their clipboard.</div>
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="privacy_actions_api_access" id="action_api_access" value="1" {{ ($settings['actions']['api_access'] ?? 0) ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="action_api_access">Allow API access to sensitive fields</label>
                                <div class="text-muted small">If enabled, API responses will include unmasked sensitive data.</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="privacy_actions_export" id="action_export" value="1" {{ ($settings['actions']['export'] ?? 0) ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="action_export">Allow exporting sensitive data</label>
                                <div class="text-muted small">Allow authorized users to export lead data containing sensitive fields.</div>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="privacy_actions_download" id="action_download" value="1" {{ ($settings['actions']['download'] ?? 0) ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="action_download">Allow downloading sensitive data</label>
                                <div class="text-muted small">Allow authorized users to download lead attachments/documents.</div>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="privacy_actions_print" id="action_print" value="1" {{ ($settings['actions']['print'] ?? 0) ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="action_print">Allow printing lead details</label>
                                <div class="text-muted small">Allow authorized users to print lead profiles containing sensitive data.</div>
                            </div>
                        </div>

                        <!-- Card 5: Role-Based Unmasked Access -->
                        <div class="col-12 mt-5">
                            <div class="section-label">Role-Based Data Access</div>
                            <p class="text-muted small mb-3">Control which roles can see unmasked sensitive data and perform sensitive actions.</p>
                        </div>
                        
                        <div class="col-12">
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm role-table">
                                    <thead>
                                        <tr>
                                            <th>Role</th>
                                            <th class="text-center">View Data</th>
                                            <th class="text-center">View Unmasked Mobile</th>
                                            <th class="text-center">View Unmasked Email</th>
                                            <th class="text-center">Export Data</th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                        @foreach($roles as $role)
                                        <tr>
                                            <td>{{ $role->name }}</td>
                                            <td class="text-center">
                                                <div class="form-check d-flex justify-content-center">
                                                    <input class="form-check-input" type="checkbox" name="privacy_roles_{{ $role->name }}_view_data" value="1" {{ ($settings['roles'][$role->name]['view_data'] ?? 1) ? 'checked' : '' }}>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check d-flex justify-content-center">
                                                    <input class="form-check-input" type="checkbox" name="privacy_roles_{{ $role->name }}_unmasked_mobile" value="1" {{ ($settings['roles'][$role->name]['unmasked_mobile'] ?? 0) ? 'checked' : '' }}>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check d-flex justify-content-center">
                                                    <input class="form-check-input" type="checkbox" name="privacy_roles_{{ $role->name }}_unmasked_email" value="1" {{ ($settings['roles'][$role->name]['unmasked_email'] ?? 0) ? 'checked' : '' }}>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check d-flex justify-content-center">
                                                    <input class="form-check-input" type="checkbox" name="privacy_roles_{{ $role->name }}_export" value="1" {{ ($settings['roles'][$role->name]['export'] ?? 0) ? 'checked' : '' }}>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="col-12 mt-5 text-end">
                            <button type="submit" class="btn btn-primary px-4">
                                Save Changes
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
    
    function updatePreview() {
        let mobileVis = $('input[name="privacy_mobile_visibility"]:checked').val();
        let emailVis = $('input[name="privacy_email_visibility"]:checked').val();
        
        if (mobileVis === 'full') {
            $('.digit-config').hide();
        } else {
            $('.digit-config').show();
        }

        let formData = {
            _token: $('meta[name="csrf-token"]').attr('content'),
            visibility: mobileVis,
            visible_digits: $('input[name="privacy_mobile_visible_digits"]').val(),
            mask_character: $('select[name="privacy_mobile_mask_character"]').val(),
            email_visibility: emailVis
        };

        $.post("{{ route('lms.settings.privacy.preview') }}", formData, function(res) {
            $('#preview_orig_mobile').text(res.original_mobile);
            $('#preview_masked_mobile').text(res.masked_mobile);
            
            $('#preview_orig_email').text(res.original_email);
            $('#preview_masked_email').text(res.masked_email);
        });
    }

    $('.mask-trigger').on('change input', function() {
        updatePreview();
    });

    // Run preview once on load
    updatePreview();
});
</script>
@endsection
