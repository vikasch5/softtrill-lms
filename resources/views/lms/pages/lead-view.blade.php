@extends('lms.common.master')

@section('content')
    @php
        use App\Helpers\LeadHelper;
        
        $leadName = LeadHelper::getLeadName($lead);
        $latestLeadFeedback = $leadFeedback->first();
        $status = LeadHelper::getLeadStatus($lead, $latestLeadFeedback);
        $nextFollowup = LeadHelper::parseNextFollowup($lead->next_followup_at);
        $createdOn = LeadHelper::formatCreatedAt($lead->created_at);
        
        $assignedUser = $users->firstWhere('id', $lead->assigned_to);
        $assignedName = $assignedUser->name ?? ($lead->assigned_to ?: 'Unassigned');
        
        $statusColor = LeadHelper::getStatusColorClass($status);
    @endphp

    <div class="dashboard-main-body">
        <div class="lv-page">

            {{-- ════ LEFT COLUMN ════ --}}
            @php
                $userRole = auth()->user()->getRoleNames()->first();
                $canViewUnmaskedMobile = $privacyService->canRole($userRole, 'unmasked_mobile');
                $displayMobile = $canViewUnmaskedMobile 
                    ? $lead->phone_number 
                    : $privacyService->maskMobile($lead->phone_number, $privacySettings['mobile']);
                    
                $canViewUnmaskedEmail = $privacyService->canRole($userRole, 'unmasked_email');
                $displayEmail = $canViewUnmaskedEmail 
                    ? $lead->email 
                    : $privacyService->maskEmail($lead->email, $privacySettings['email']);
            @endphp
            <div class="lv-left">

                {{-- Lead header card --}}
                <div class="lv-card">
                    <div class="lv-card-head">
                        <div>
                            <div class="lv-lead-name">{{ $leadName }}</div>
                            <div class="lv-lead-id">Lead #{{ $lead->id }} · Added {{ $createdOn }}</div>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="lv-badge {{ $statusColor }}" id="lead-status-badge">
                                <i class="ri-circle-fill" style="font-size:7px;"></i>
                                <span id="lead-status-text">{{ $status }}</span>
                            </span>
                            <button type="button" class="lv-btn border-0 dialer-call" data-phone="{{ $displayMobile }}"
                                data-id="{{ $lead->id }}" {{ blank($lead->phone_number) ? 'disabled' : '' }}>
                                <i class="ri-phone-line"></i> Call lead
                            </button>
                            <button type="button" class="lv-btn border-0" data-bs-toggle="modal"
                                data-bs-target="#editLeadModal">
                                <i class="ri-edit-line"></i> Edit lead
                            </button>
                        </div>
                    </div>

                    <div class="lv-card-body">
                        <div class="lv-info-grid">
                            <div class="lv-info-card">
                                <div class="lv-info-label">Email</div>
                                <div class="lv-info-val">{{ $displayEmail ?: '—' }}</div>
                            </div>
                            <div class="lv-info-card">
                                <div class="lv-info-label">Phone</div>
                                <div class="lv-info-val">{{ $displayMobile ?: '—' }}</div>
                            </div>
                            <div class="lv-info-card">
                                <div class="lv-info-label">Assigned to</div>
                                <div class="lv-info-val">{{ $assignedName }}</div>
                            </div>
                            <div class="lv-info-card">
                                <div class="lv-info-label">Next followup</div>
                                <div class="lv-info-val">
                                    {{ $nextFollowup ? $nextFollowup->format('d M Y h:i A') : '—' }}
                                </div>
                            </div>
                        </div>

                        @if($fields->isNotEmpty())
                            <div class="lv-detail-grid">
                                @foreach($fields as $field)
                                    @php
                                        $value = $lead->data[$field->slug] ?? '—';
                                        if (is_array($value)) {
                                            $value = implode(', ', array_filter($value));
                                        }
                                        $value = ($value !== '' && $value !== null) ? $value : '—';
                                    @endphp
                                    <div class="lv-detail-item">
                                        <div class="lv-info-label">{{ $field->name }}</div>
                                        <div class="lv-info-val">{{ $value }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                {{-- History tabs card --}}
                <div class="lv-card">
                    <div class="lv-tabs">
                        <div class="lv-tab active" data-tab="feedback">
                            Feedback
                            {{-- @if($notes->isNotEmpty())
                            <span class="lv-badge lv-badge--neutral ms-1" style="padding:1px 7px;font-size:11px;">{{
                                $notes->count() }}</span>
                            @endif --}}
                        </div>
                        {{-- <div class="lv-tab" data-tab="followup">
                            Followups
                            @if($followups->isNotEmpty())
                            <span class="lv-badge lv-badge--neutral ms-1" style="padding:1px 7px;font-size:11px;">{{
                                $followups->count() }}</span>
                            @endif
                        </div> --}}
                        <div class="lv-tab" data-tab="activity">Activity logs</div>
                    </div>

                    <div class="lv-tab-panel active" id="panel-feedback">

                        <div class="table-responsive">

                            <table class="table table-hover align-middle mb-0">

                                <thead class="table-light">

                                    <tr>
                                        <th>#</th>
                                        <th>Feedback</th>
                                        <th>Sub Feedback</th>
                                        <th>Remarks</th>
                                        <th>Followup</th>
                                        <th>Status</th>
                                        <th>Added By</th>
                                        <th>Created</th>
                                    </tr>

                                </thead>

                                <tbody>

                                    @forelse($leadFeedback as $item)

                                        <tr>

                                            <td>
                                                {{ $loop->iteration }}
                                            </td>

                                            <td>

                                                <span class="badge bg-primary-subtle text-primary border">

                                                    {{ $item->feedback?->name ?? '-' }}

                                                </span>

                                            </td>

                                            <td>

                                                @if($item->subFeedback)

                                                    <span class="badge bg-info-subtle text-info border">

                                                        {{ $item->subFeedback->name }}

                                                    </span>

                                                @else

                                                    <span class="text-muted">-</span>

                                                @endif

                                            </td>

                                            <td style="max-width:250px">

                                                @if($item->remarks)

                                                    {{ $item->remarks }}

                                                @else

                                                    <span class="text-muted">
                                                        No remarks
                                                    </span>

                                                @endif

                                            </td>

                                            <td>

                                                @if($item->followup_date)

                                                    <div class="small">

                                                        {{ \Carbon\Carbon::parse($item->followup_date)->format('d M Y') }}

                                                        <br>

                                                        <span class="text-muted">

                                                            {{ \Carbon\Carbon::parse($item->followup_date)->format('h:i A') }}

                                                        </span>

                                                    </div>

                                                @else

                                                    <span class="text-muted">-</span>

                                                @endif

                                            </td>

                                            <td>

                                                @php

                                                    $statusClass = match ($item->status) {
                                                        'completed' => 'success',
                                                        'pending' => 'warning',
                                                        'cancelled' => 'danger',
                                                        default => 'secondary'
                                                    };

                                                @endphp

                                                <span class="badge bg-{{ $statusClass }}">

                                                    {{ ucfirst($item->status) }}

                                                </span>

                                            </td>

                                            <td>

                                                {{ $item->user?->name ?? '-' }}

                                            </td>

                                            <td>

                                                <div class="small">

                                                    {{ $item->created_at->format('d M Y') }}

                                                    <br>

                                                    <span class="text-muted">

                                                        {{ $item->created_at->format('h:i A') }}

                                                    </span>

                                                </div>

                                            </td>

                                        </tr>

                                    @empty

                                        <tr>

                                            <td colspan="8" class="text-center py-5">

                                                <div class="text-muted">

                                                    <i class="ri-chat-history-line fs-2 d-block mb-2"></i>

                                                    No feedback history found

                                                </div>

                                            </td>

                                        </tr>

                                    @endforelse

                                </tbody>

                            </table>

                        </div>

                    </div>

                    {{-- <div class="lv-tab-panel" id="panel-followup">
                        <div class="lv-followup-list">
                            @forelse($followups as $followup)
                            @php
                            $followupAt = $followup->followup_at ? \Carbon\Carbon::parse($followup->followup_at) : null;
                            $followupStatus = $followupAt && $followupAt->isPast() ? 'Completed / Past' : 'Upcoming';
                            @endphp
                            <div class="lv-followup-item">
                                <div class="lv-followup-head">
                                    <div class="lv-followup-title">
                                        <span class="lv-followup-icon">
                                            <i class="ri-calendar-schedule-line"></i>
                                        </span>
                                        <div>
                                            <p class="lv-followup-name mb-0">Followup scheduled</p>
                                            <div class="lv-followup-sub">
                                                {{ $followupAt ? $followupAt->format('l, d M Y') : 'Followup date not set'
                                                }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="lv-followup-time">
                                        {{ $followupAt ? $followupAt->format('h:i A') : '--:--' }}
                                    </div>
                                </div>

                                <div class="lv-followup-tags">
                                    <span class="lv-followup-tag">
                                        <i class="ri-time-line"></i> {{ $followupStatus }}
                                    </span>
                                    @if($followupAt)
                                    <span class="lv-followup-tag">
                                        <i class="ri-calendar-check-line"></i> {{ $followupAt->format('d M Y, h:i A') }}
                                    </span>
                                    @endif
                                </div>

                                <div class="lv-followup-note">
                                    <div class="lv-followup-note-label">Remarks</div>
                                    <div class="lv-followup-note-text">{{ $followup->remarks ?: 'No remarks added for this
                                        followup.' }}</div>
                                </div>
                            </div>
                            @empty
                            <div class="lv-empty">No followups recorded.</div>
                            @endforelse
                        </div>
                    </div> --}}

                    <div class="lv-tab-panel" id="panel-activity">
                        <div class="lv-activity-list">
                            @forelse($activities as $activity)
                                @php
                                    $displayData = \App\Helpers\LeadHelper::getActivityDisplayData($activity, $feedbackLookup);
                                    $activityTitle = $displayData['title'];
                                    $activityIcon = $displayData['icon'];
                                    $activityBadge = $displayData['badge'];
                                    $activityDetails = $displayData['details'];
                                @endphp
                                <div class="lv-activity-item">
                                    <div class="lv-activity-head">
                                        <div class="lv-activity-title">
                                            <span class="lv-activity-icon">
                                                <i class="{{ $activityIcon }}"></i>
                                            </span>
                                            <div>
                                                <p class="lv-activity-name mb-0">{{ $activityTitle }}</p>
                                                <div class="lv-activity-sub">
                                                    {{ $activity->user?->name ?? 'System' }} updated this lead
                                                </div>
                                            </div>
                                        </div>
                                        <div class="lv-activity-time">
                                            {{ $activity->created_at->format('d M Y, h:i A') }}
                                        </div>
                                    </div>

                                    <div class="lv-activity-tags">
                                        <span class="lv-activity-tag">
                                            <i class="ri-flashlight-line"></i> {{ $activityBadge }}
                                        </span>
                                        <span class="lv-activity-tag">
                                            <i class="ri-user-line"></i> {{ $activity->user?->name ?? 'System' }}
                                        </span>
                                    </div>

                                    @if(!empty($activityDetails))
                                        <div class="lv-activity-details">
                                            @foreach($activityDetails as $detail)
                                                <div class="lv-activity-detail">
                                                    <div class="lv-activity-detail-label">{{ $detail['label'] }}</div>
                                                    <div class="lv-activity-detail-value">{{ $detail['value'] }}</div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div class="lv-empty">No activity found.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- ════ RIGHT COLUMN (sticky sidebar) ════ --}}
            <div class="lv-right">

                {{-- Quick update --}}
                <div class="lv-card">
                    <div class="lv-card-head">
                        <span class="lv-card-head-title">Quick update</span>
                    </div>
                    <div class="lv-card-body--sm lv-card-body">
                        <form action="{{ route('lms.leads.quick-update', $lead->id) }}" method="POST" class="ajaxForm"
                            data-notify-type="toaster" id="lead-quick-update-form">
                            @csrf
                            <input type="hidden" name="lead_id" value="{{ $lead->id }}">
                            <input type="hidden" name="source" value="{{ request('source') }}">

                            <div class="lv-fgroup">
                                <label class="lv-flabel" for="lv-feedback">Feedbacks</label>
                                <select name="feedback_id" id="lv-feedback" class="lv-finput required">
                                    <option value="">— Select feedback —</option>
                                    @foreach($feedbacks as $feedback)
                                        <option value="{{ $feedback->id }}" {{ (string) optional($latestLeadFeedback)->feedback_id === (string) $feedback->id ? 'selected' : '' }}>
                                            {{ ucfirst(str_replace('_', ' ', $feedback->name)) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="lv-fgroup">
                                <label class="lv-flabel" for="lv-sub-feedback">Sub Feedback</label>
                                <select name="sub_feedback_id" id="lv-sub-feedback" class="lv-finput">
                                    <option value="">— Select sub feedback —</option>
                                </select>
                            </div>

                            <div class="lv-fgroup">
                                <label class="lv-flabel" for="lv-followup">Next followup</label>
                                <input type="datetime-local" name="next_followup_at" id="lv-followup" class="lv-finput"
                                    value="{{ $nextFollowup ? $nextFollowup->format('Y-m-d\TH:i') : '' }}">
                                @if($nextFollowup)
                                    <div class="lv-fw-hint">
                                        <i class="ri-calendar-event-line"></i>
                                        {{ $nextFollowup->format('D, d M Y \a\t h:i A') }}
                                    </div>
                                @endif
                            </div>

                            <div class="lv-fgroup">
                                <label class="lv-flabel" for="lv-remarks">Remarks</label>
                                <textarea name="remarks" id="lv-remarks" class="lv-finput" rows="5"
                                    placeholder="Write a note about this lead…" maxlength="2000"></textarea>
                                <div class="lv-char">
                                    <span id="lv-note-count">0</span> / 2000
                                </div>
                            </div>

                            <button type="submit" class="lv-btn-primary">
                                <i class="ri-save-line"></i> Update lead
                            </button>
                        </form>

                        @if(request('source') === 'dialer')
                            <div class="mt-3" id="dialer-disconnect-wrap">
                                <button type="button" id="btn-dialer-disconnect" class="lv-btn-danger w-100">
                                    <i class="ri-phone-off-line"></i> Disconnect Call
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade lv-edit-modal" id="editLeadModal" tabindex="-1" aria-labelledby="editLeadModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="lv-edit-head d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <h5 class="lv-edit-title" id="editLeadModalLabel">Edit lead details</h5>
                        <div class="lv-edit-subtitle">Update this lead without leaving the dialer view.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form method="POST" action="{{ route('lms.leads.update') }}" class="ajaxForm" data-notify-type="toaster">
                    @csrf
                    <input type="hidden" name="lead_id" value="{{ $lead->id }}">
                    <input type="hidden" name="source" value="{{ request('source') }}">

                    <div class="lv-edit-body">
                        <div class="lv-edit-section">
                            <div class="lv-edit-section-head">
                                <h6 class="lv-edit-section-title">
                                    <i class="ri-user-line"></i> Basic information
                                </h6>
                                <span class="lv-edit-section-note">Main dialer fields</span>
                            </div>
                            <div class="lv-edit-section-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <div class="lv-edit-field">
                                            <label class="lv-edit-label">Name</label>
                                            <input type="text" name="name" value="{{ $lead->name }}" class="lv-edit-input">
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="lv-edit-field">
                                            <label class="lv-edit-label">Phone Number</label>
                                            <input type="text" name="phone_number" value="{{ $displayMobile }}"
                                                class="lv-edit-input" {{ !$canViewUnmaskedMobile ? 'disabled' : '' }}>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="lv-edit-field">
                                            <label class="lv-edit-label">Email</label>
                                            <input type="text" name="email" value="{{ $displayEmail }}"
                                                class="lv-edit-input" {{ !$canViewUnmaskedEmail ? 'disabled' : '' }}>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($fields->isNotEmpty())
                            <div class="lv-edit-section">
                                <div class="lv-edit-section-head">
                                    <h6 class="lv-edit-section-title">
                                        <i class="ri-list-settings-line"></i> List fields
                                    </h6>
                                    <span class="lv-edit-section-note">{{ $fields->count() }} custom field(s)</span>
                                </div>
                                <div class="lv-edit-section-body">
                                    <div class="row g-3">
                                        @foreach($fields as $field)
                                            @php
                                                $value = $lead->data[$field->slug] ?? '';
                                                $options = $field->options ? json_decode($field->options, true) : [];
                                                $inputValue = is_array($value) ? implode(',', $value) : $value;
                                            @endphp

                                            <div class="col-md-4">
                                                <div class="lv-edit-field">
                                                    <label class="lv-edit-label">
                                                        {{ $field->name }}
                                                        @if($field->is_required)
                                                            <span class="text-danger">*</span>
                                                        @endif
                                                    </label>

                                                    @if(in_array($field->type, ['text', 'email', 'phone', 'number', 'decimal', 'date', 'datetime'], true))
                                                        <input
                                                            type="{{ $field->type === 'phone' ? 'text' : ($field->type === 'datetime' ? 'datetime-local' : $field->type) }}"
                                                            name="data[{{ $field->slug }}]" value="{{ $inputValue }}"
                                                            class="lv-edit-input">
                                                    @elseif($field->type === 'textarea')
                                                        <textarea name="data[{{ $field->slug }}]" class="lv-edit-input"
                                                            rows="3">{{ $inputValue }}</textarea>
                                                    @elseif($field->type === 'select')
                                                        <select name="data[{{ $field->slug }}]" class="lv-edit-input">
                                                            <option value="">Select</option>
                                                            @foreach($options as $option)
                                                                <option value="{{ $option }}" {{ $value == $option ? 'selected' : '' }}>
                                                                    {{ $option }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    @elseif($field->type === 'radio')
                                                        <div class="lv-edit-choice-box">
                                                            @foreach($options as $option)
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="radio"
                                                                        name="data[{{ $field->slug }}]" value="{{ $option }}" {{ $value == $option ? 'checked' : '' }}>
                                                                    <label class="form-check-label">{{ $option }}</label>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @elseif($field->type === 'checkbox')
                                                        @php
                                                            $selected = is_array($value) ? $value : explode(',', (string) $value);
                                                        @endphp

                                                        <div class="lv-edit-choice-box">
                                                            @foreach($options as $option)
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox"
                                                                        name="data[{{ $field->slug }}][]" value="{{ $option }}" {{ in_array($option, $selected, true) ? 'checked' : '' }}>
                                                                    <label class="form-check-label">{{ $option }}</label>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @elseif($field->type === 'boolean')
                                                        <select name="data[{{ $field->slug }}]" class="lv-edit-input">
                                                            <option value="1" {{ (string) $value === '1' ? 'selected' : '' }}>Yes</option>
                                                            <option value="0" {{ (string) $value === '0' ? 'selected' : '' }}>No</option>
                                                        </select>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="lv-edit-footer">
                        <span class="lv-edit-footer-hint">
                            <i class="ri-shield-check-line"></i> Changes save to this lead only.
                        </span>
                        <div class="lv-edit-actions">
                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-dark">
                                <i class="ri-save-line"></i> Update Lead
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // ── Tabs ──
        document.querySelectorAll('.lv-tab').forEach(function (tab) {
            tab.addEventListener('click', function () {
                var name = this.dataset.tab;
                document.querySelectorAll('.lv-tab').forEach(function (t) {t.classList.remove('active');});
                document.querySelectorAll('.lv-tab-panel').forEach(function (p) {p.classList.remove('active');});
                this.classList.add('active');
                var panel = document.getElementById('panel-' + name);
                if (panel) panel.classList.add('active');
            });
        });

        // ── Char counter ──
        var noteEl = document.getElementById('lv-remarks');
        var countEl = document.getElementById('lv-note-count');
        if (noteEl && countEl) {
            noteEl.addEventListener('input', function () {
                countEl.textContent = this.value.length;
            });
        }
    </script>

@endsection

@section('scripts')
    <script>
        window.lmsConfig = {
            quickUpdateAction: @json(route('lms.leads.quick-update', $lead->id)),
            dialerCallAction: @json(route('lms.dialer.call')),
            dialerHangupAction: @json(route('lms.dialer.hangup')),
            dialerStatusAction: @json(route('lms.dialer.status')),
            csrfToken: '{{ csrf_token() }}'
        };
    </script>
    <script src="{{ asset('lms/js/lead-view.js') }}"></script>
@endsection