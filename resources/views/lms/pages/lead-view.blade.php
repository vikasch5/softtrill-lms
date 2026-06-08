@extends('lms.common.master')

@section('content')
    @php
        $leadName = $lead->data['full_name'] ?? $lead->data['name'] ?? 'Lead #' . $lead->id;
        $status = ucfirst(str_replace('_', ' ', $lead->status ?? 'new'));
        $nextFollowup = $lead->next_followup_at ? \Carbon\Carbon::parse($lead->next_followup_at) : null;
        $createdOn = $lead->created_at ? $lead->created_at->format('d M Y, h:i A') : '-';
        $assignedUser = $users->firstWhere('id', $lead->assigned_to);
        $assignedName = $assignedUser->name ?? ($lead->assigned_to ?: 'Unassigned');

        
        $statusColor = 'lv-badge--success';
    @endphp

    <style>
        /* ── Layout ── */
        .lv-page {
            display: grid;
            grid-template-columns: 1fr 310px;
            gap: 16px;
            align-items: start;
        }

        .lv-left {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .lv-right {
            position: sticky;
            top: 80px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        /* ── Card ── */
        .lv-card {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 14px;
            overflow: hidden;
        }

        .lv-card-head {
            padding: 16px 20px;
            border-bottom: 1px solid #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .lv-card-body {
            padding: 20px;
        }

        .lv-card-body--sm {
            padding: 16px;
        }

        /* ── Lead header ── */
        .lv-lead-name {
            font-size: 1.125rem;
            font-weight: 700;
            color: #212529;
            line-height: 1.2;
        }

        .lv-lead-id {
            font-size: 12px;
            color: #8a939d;
            margin-top: 2px;
        }

        /* ── Status badge ── */
        .lv-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 999px;
            border: 1px solid transparent;
        }

        .lv-badge--neutral {
            background: #f3f4f6;
            color: #374151;
            border-color: #e5e7eb;
        }

        .lv-badge--info {
            background: #eff6ff;
            color: #1d4ed8;
            border-color: #bfdbfe;
        }

        .lv-badge--warning {
            background: #fffbeb;
            color: #b45309;
            border-color: #fde68a;
        }

        .lv-badge--success {
            background: #f0fdf4;
            color: #15803d;
            border-color: #bbf7d0;
        }

        .lv-badge--danger {
            background: #fef2f2;
            color: #b91c1c;
            border-color: #fecaca;
        }

        /* ── Info cards row ── */
        .lv-info-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }

        .lv-info-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 12px 14px;
        }

        .lv-info-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #8a939d;
            margin-bottom: 4px;
        }

        .lv-info-val {
            font-size: 14px;
            font-weight: 600;
            color: #212529;
            word-break: break-all;
        }

        /* ── Detail grid ── */
        .lv-detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-top: 16px;
        }

        .lv-detail-item {
            border: 1px solid #f0f2f5;
            border-radius: 10px;
            padding: 12px 14px;
        }

        /* ── Tabs ── */
        .lv-tabs {
            display: flex;
            gap: 2px;
            border-bottom: 1px solid #f0f2f5;
            padding: 0 20px;
        }

        .lv-tab {
            padding: 10px 14px;
            font-size: 13px;
            font-weight: 600;
            color: #8a939d;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            margin-bottom: -1px;
            transition: color .15s;
            white-space: nowrap;
            user-select: none;
        }

        .lv-tab.active {
            color: #212529;
            border-color: #212529;
        }

        .lv-tab-panel {
            display: none;
            padding: 16px 20px;
        }

        .lv-tab-panel.active {
            display: block;
        }

        /* ── Timeline ── */
        .lv-timeline {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .lv-tl-item {
            border: 1px solid #f0f2f5;
            border-radius: 10px;
            padding: 12px 14px;
        }

        .lv-tl-text {
            font-size: 13px;
            color: #212529;
            line-height: 1.55;
            margin-bottom: 4px;
        }

        .lv-tl-meta {
            font-size: 11px;
            color: #8a939d;
        }

        .lv-activity-list {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 14px;
            padding-left: 18px;
        }

        .lv-activity-list::before {
            content: "";
            position: absolute;
            left: 7px;
            top: 4px;
            bottom: 4px;
            width: 2px;
            background: linear-gradient(180deg, #dbeafe 0%, #e5e7eb 100%);
        }

        .lv-activity-item {
            position: relative;
            background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
            border: 1px solid #e7eef6;
            border-radius: 16px;
            padding: 14px 14px 12px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .04);
        }

        .lv-activity-item::before {
            content: "";
            position: absolute;
            left: -17px;
            top: 22px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #2563eb;
            box-shadow: 0 0 0 4px #eff6ff;
        }

        .lv-activity-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 8px;
            flex-wrap: wrap;
        }

        .lv-activity-title {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .lv-activity-icon {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
            flex-shrink: 0;
        }

        .lv-activity-name {
            font-size: 13px;
            font-weight: 700;
            color: #111827;
            line-height: 1.3;
            margin: 0;
        }

        .lv-activity-sub {
            font-size: 11px;
            color: #6b7280;
            margin-top: 3px;
        }

        .lv-activity-time {
            font-size: 11px;
            color: #6b7280;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            padding: 5px 9px;
            white-space: nowrap;
        }

        .lv-activity-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 8px;
        }

        .lv-activity-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 9px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            background: #f8fafc;
            color: #334155;
            border: 1px solid #e2e8f0;
        }

        .lv-activity-details {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .lv-activity-detail {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 8px 10px;
            min-width: 0;
            flex: 0 1 auto;
            max-width: 100%;
        }

        .lv-activity-detail-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #94a3b8;
            margin-bottom: 4px;
        }

        .lv-activity-detail-value {
            font-size: 12px;
            color: #0f172a;
            line-height: 1.4;
            word-break: break-word;
        }

        .lv-followup-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .lv-followup-item {
            border: 1px solid #e7eef6;
            border-radius: 14px;
            background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
            padding: 14px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .04);
        }

        .lv-followup-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 8px;
            flex-wrap: wrap;
        }

        .lv-followup-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .lv-followup-icon {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            background: #ecfeff;
            color: #0f766e;
            border: 1px solid #a5f3fc;
            flex-shrink: 0;
        }

        .lv-followup-name {
            font-size: 13px;
            font-weight: 700;
            color: #111827;
            line-height: 1.3;
            margin: 0;
        }

        .lv-followup-sub {
            font-size: 11px;
            color: #6b7280;
            margin-top: 3px;
        }

        .lv-followup-time {
            font-size: 11px;
            color: #6b7280;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            padding: 5px 9px;
            white-space: nowrap;
        }

        .lv-followup-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 8px;
        }

        .lv-followup-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 9px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            background: #f8fafc;
            color: #334155;
            border: 1px solid #e2e8f0;
        }

        .lv-followup-note {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 12px;
        }

        .lv-followup-note-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #94a3b8;
            margin-bottom: 4px;
        }

        .lv-followup-note-text {
            font-size: 12px;
            color: #0f172a;
            line-height: 1.5;
            word-break: break-word;
        }

        .lv-empty {
            text-align: center;
            padding: 24px;
            color: #8a939d;
            font-size: 13px;
            border: 1px dashed #dee2e6;
            border-radius: 10px;
            background: #fafbfc;
        }

        /* ── Sidebar forms ── */
        .lv-flabel {
            font-size: 12px;
            font-weight: 600;
            color: #495057;
            margin-bottom: 5px;
            display: block;
        }

        .lv-finput {
            width: 100%;
            padding: 8px 11px;
            font-size: 13px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            background: #fff;
            color: #212529;
            outline: none;
            transition: border-color .15s;
            font-family: inherit;
        }

        .lv-finput:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, .1);
        }

        select.lv-finput {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            padding-right: 28px;
        }

        textarea.lv-finput {
            min-height: 96px;
            resize: vertical;
            line-height: 1.5;
        }

        .lv-fgroup {
            margin-bottom: 12px;
        }

        .lv-fw-hint {
            font-size: 11px;
            color: #8a939d;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* ── Buttons ── */
        .lv-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 600;
            padding: 7px 14px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            background: #fff;
            color: #212529;
            cursor: pointer;
            transition: background .15s;
        }

        .lv-btn:hover {
            background: #f8f9fa;
        }

        .lv-btn-primary {
            width: 100%;
            justify-content: center;
            padding: 9px;
            background: #212529;
            color: #fff;
            border-color: #212529;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity .15s;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .lv-btn-primary:hover {
            opacity: .87;
        }

        .lv-btn-secondary {
            width: 100%;
            justify-content: center;
            padding: 9px;
            background: #f8f9fa;
            color: #212529;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background .15s;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .lv-btn-secondary:hover {
            background: #f0f2f5;
        }

        /* ── Char counter ── */
        .lv-char {
            font-size: 11px;
            color: #8a939d;
            text-align: right;
            margin-top: 3px;
        }

        /* ── Section title in sidebar ── */
        .lv-card-head-title {
            font-size: 14px;
            font-weight: 700;
            color: #212529;
        }

        /* ── Responsive ── */
        @media (max-width: 991.98px) {
            .lv-page {
                grid-template-columns: 1fr;
            }

            .lv-right {
                position: static;
            }

            .lv-info-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 575.98px) {

            .lv-info-grid,
            .lv-detail-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="dashboard-main-body">
        <div class="lv-page">

            {{-- ════ LEFT COLUMN ════ --}}
            <div class="lv-left">

                {{-- Lead header card --}}
                <div class="lv-card">
                    <div class="lv-card-head">
                        <div>
                            <div class="lv-lead-name">{{ $leadName }}</div>
                            <div class="lv-lead-id">Lead #{{ $lead->id }} · Added {{ $createdOn }}</div>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="lv-badge {{ $statusColor }}">
                                <i class="ri-circle-fill" style="font-size:7px;"></i>
                                {{ $status }}
                            </span>
                            <a href="{{ route('lms.lead.edit', $lead->id) }}" class="lv-btn">
                                <i class="ri-edit-line"></i> Edit lead
                            </a>
                        </div>
                    </div>

                    <div class="lv-card-body">
                        <div class="lv-info-grid">
                            <div class="lv-info-card">
                                <div class="lv-info-label">Email</div>
                                <div class="lv-info-val">{{ $lead->email ?: '—' }}</div>
                            </div>
                            <div class="lv-info-card">
                                <div class="lv-info-label">Phone</div>
                                <div class="lv-info-val">{{ $lead->phone_number ?: '—' }}</div>
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
                                <span class="lv-badge lv-badge--neutral ms-1"
                                    style="padding:1px 7px;font-size:11px;">{{ $notes->count() }}</span>
                            @endif --}}
                        </div>
                        <div class="lv-tab" data-tab="followup">
                            Followups
                            @if($followups->isNotEmpty())
                                <span class="lv-badge lv-badge--neutral ms-1"
                                    style="padding:1px 7px;font-size:11px;">{{ $followups->count() }}</span>
                            @endif
                        </div>
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

                                $statusClass = match($item->status) {
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

                    <div class="lv-tab-panel" id="panel-followup">
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
                                                    {{ $followupAt ? $followupAt->format('l, d M Y') : 'Followup date not set' }}
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
                                        <div class="lv-followup-note-text">{{ $followup->remarks ?: 'No remarks added for this followup.' }}</div>
                                    </div>
                                </div>
                            @empty
                                <div class="lv-empty">No followups recorded.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="lv-tab-panel" id="panel-activity">
                        <div class="lv-activity-list">
                            @forelse($activities as $activity)
                                @php
                                    $activityData = is_array($activity->new_value) ? $activity->new_value : [];
                                    $activityTitle = match ($activity->activity) {
                                        'lead_assigned' => 'Lead assigned',
                                        'feedback_added' => 'Feedback added',
                                        default => ucwords(str_replace('_', ' ', $activity->activity)),
                                    };

                                    $activityIcon = match ($activity->activity) {
                                        'lead_assigned' => 'ri-user-shared-line',
                                        'feedback_added' => 'ri-chat-4-line',
                                        default => 'ri-history-line',
                                    };

                                    $activityBadge = match ($activity->activity) {
                                        'lead_assigned' => 'Assignment',
                                        'feedback_added' => 'Feedback',
                                        default => 'Activity',
                                    };

                                    $activityDetails = [];

                                    if ($activity->activity === 'lead_assigned') {
                                        if (!empty($activityData['user_name'])) {
                                            $activityDetails[] = [
                                                'label' => 'Assigned To',
                                                'value' => $activityData['user_name'],
                                            ];
                                        }
                                    }

                                    if ($activity->activity === 'feedback_added') {
                                        $feedbackName = optional($feedbackLookup->firstWhere('id', $activityData['feedback_id'] ?? null))->name;
                                        $subFeedbackName = optional($feedbackLookup->firstWhere('id', $activityData['sub_feedback_id'] ?? null))->name;

                                        if ($feedbackName) {
                                            $activityDetails[] = [
                                                'label' => 'Feedback',
                                                'value' => $feedbackName,
                                            ];
                                        }

                                        if ($subFeedbackName) {
                                            $activityDetails[] = [
                                                'label' => 'Sub Feedback',
                                                'value' => $subFeedbackName,
                                            ];
                                        }

                                        if (!empty($activityData['followup_date'])) {
                                            $activityDetails[] = [
                                                'label' => 'Next Followup',
                                                'value' => \Carbon\Carbon::parse($activityData['followup_date'])->format('d M Y, h:i A'),
                                            ];
                                        }

                                        if (!empty($activityData['remarks'])) {
                                            $activityDetails[] = [
                                                'label' => 'Remarks',
                                                'value' => $activityData['remarks'],
                                            ];
                                        }
                                    }
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
                        <form action="{{ route('lms.leads.quick-update', $lead->id) }}" method="POST" class="ajaxForm">
                            @csrf
                            <input type="hidden" name="lead_id" value="{{ $lead->id }}">

                            <div class="lv-fgroup">
                                <label class="lv-flabel" for="lv-feedback">Feedbacks</label>
                                <select name="feedback_id" id="lv-feedback" class="lv-finput required">
                                    <option value="">— Select feedback —</option>
                                    @foreach($feedbacks as $feedback)
                                        <option value="{{ $feedback->id }}" {{ $lead->status === $feedback->id ? 'selected' : '' }}>
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
                    </div>
                </div>
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
$(document).ready(function() {

    $('#lv-feedback').on('change', function() {

        let feedbackId = $(this).val();

        $('#lv-sub-feedback').html(
            '<option value="">Loading...</option>'
        );

        if (!feedbackId) {

            $('#lv-sub-feedback').html(
                '<option value="">— Select sub feedback —</option>'
            );

            return;
        }

        $.ajax({
            url: '/feedbacks/sub-feedbacks/' + feedbackId,
            type: 'GET',
            success: function(response) {

                let options =
                    '<option value="">— Select sub feedback —</option>';

                $.each(response, function(index, item) {

                    options += `
                        <option value="${item.id}">
                            ${item.name}
                        </option>
                    `;
                });

                $('#lv-sub-feedback').html(options);
            },
            error: function() {

                $('#lv-sub-feedback').html(
                    '<option value="">No sub feedback found</option>'
                );
            }
        });
    });

});
</script>
@endsection
