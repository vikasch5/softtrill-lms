<!-- 3. KPI Cards -->
        <div class="row row-cols-xxxl-4 row-cols-lg-4 row-cols-sm-2 row-cols-1 gy-4 mb-24">
            <div class="col">
                <div class="card shadow-none border bg-gradient-start-1 h-100">
                    <div class="card-body p-20">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div>
                                <p class="fw-medium text-primary-light mb-1">Total Calls</p>
                                <h6 class="mb-0">{{ $user->total_calls ?? 0 }}</h6>
                            </div>
                            <div
                                class="w-50-px h-50-px bg-cyan rounded-circle d-flex justify-content-center align-items-center">
                                <iconify-icon icon="solar:phone-calling-rounded-bold"
                                    class="text-white text-2xl mb-0"></iconify-icon>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-none border bg-gradient-start-2 h-100">
                    <div class="card-body p-20">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div>
                                <p class="fw-medium text-primary-light mb-1">Answered Calls</p>
                                <h6 class="mb-0">{{ $user->answered_calls ?? 0 }}</h6>
                            </div>
                            <div
                                class="w-50-px h-50-px bg-success-main rounded-circle d-flex justify-content-center align-items-center">
                                <iconify-icon icon="solar:phone-bold" class="text-white text-2xl mb-0"></iconify-icon>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-none border bg-gradient-start-3 h-100">
                    <div class="card-body p-20">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div>
                                <p class="fw-medium text-primary-light mb-1">Answer Rate</p>
                                <h6 class="mb-0">{{ number_format($user->answer_rate ?? 0, 2) }}%</h6>
                            </div>
                            <div
                                class="w-50-px h-50-px bg-purple rounded-circle d-flex justify-content-center align-items-center">
                                <iconify-icon icon="solar:graph-up-bold" class="text-white text-2xl mb-0"></iconify-icon>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- 
            <div class="col">
                <div class="card shadow-none border bg-gradient-start-2 h-100">
                    <div class="card-body p-20">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div>
                                <p class="fw-medium text-primary-light mb-1">Calls &ge; 2 Minutes</p>
                                <h6 class="mb-0">{{ $user->calls_gt_2min ?? 0 }}</h6>
                            </div>
                            <div
                                class="w-50-px h-50-px bg-success rounded-circle d-flex justify-content-center align-items-center">
                                <iconify-icon icon="solar:cup-star-bold" class="text-white text-2xl mb-0"></iconify-icon>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            --}}
            <div class="col">
                <div class="card shadow-none border bg-gradient-start-4 h-100">
                    <div class="card-body p-20">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div>
                                <p class="fw-medium text-primary-light mb-1">Avg. Duration</p>
                                <h6 class="mb-0">{{ gmdate('i:s', $user->avg_duration ?? 0) }}</h6>
                            </div>
                            <div
                                class="w-50-px h-50-px bg-info rounded-circle d-flex justify-content-center align-items-center">
                                <iconify-icon icon="solar:clock-circle-bold"
                                    class="text-white text-2xl mb-0"></iconify-icon>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-none border bg-gradient-start-5 h-100">
                    <div class="card-body p-20">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div>
                                <p class="fw-medium text-primary-light mb-1">Login Hours</p>
                                <h6 class="mb-0">{{ floor(($user->login_sec ?? 0) / 3600) . 'h ' . gmdate('i\m', $user->login_sec ?? 0) }}</h6>
                            </div>
                            <div
                                class="w-50-px h-50-px bg-primary rounded-circle d-flex justify-content-center align-items-center">
                                <iconify-icon icon="solar:user-circle-bold" class="text-white text-2xl mb-0"></iconify-icon>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-none border bg-gradient-start-1 h-100">
                    <div class="card-body p-20">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div>
                                <p class="fw-medium text-primary-light mb-1">Pause Time</p>
                                <h6 class="mb-0">{{ floor(($user->pause_sec ?? 0) / 3600) . 'h ' . gmdate('i\m', $user->pause_sec ?? 0) }}</h6>
                            </div>
                            <div
                                class="w-50-px h-50-px bg-danger rounded-circle d-flex justify-content-center align-items-center">
                                <iconify-icon icon="solar:pause-circle-bold"
                                    class="text-white text-2xl mb-0"></iconify-icon>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-none border bg-gradient-start-3 h-100">
                    <div class="card-body p-20">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div>
                                <p class="fw-medium text-primary-light mb-1">Total Talk Time</p>
                                <h6 class="mb-0">{{ floor(($user->talk_sec ?? 0) / 3600) . 'h ' . gmdate('i\m', $user->talk_sec ?? 0) }}</h6>
                            </div>
                            <div
                                class="w-50-px h-50-px bg-warning rounded-circle d-flex justify-content-center align-items-center">
                                <iconify-icon icon="solar:history-bold" class="text-white text-2xl mb-0"></iconify-icon>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row gy-4 mb-24">
            <!-- 4. Calling Activity -->
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header border-bottom">
                        <h5 class="card-title mb-0">Calling Activity</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            @forelse($callingActivity as $activity)
                                @php
                                    $startHour = \Carbon\Carbon::createFromFormat('H', $activity->hour);
                                    $endHour = (clone $startHour)->addHour();
                                    $hourFormatted = $startHour->format('hA') . '-' . $endHour->format('hA');
                                @endphp
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span class="text-secondary-light">{{ $hourFormatted }}</span>
                                    <span class="fw-medium">{{ $activity->total_calls }} calls</span>
                                </li>
                            @empty
                                <li class="list-group-item px-0 text-center text-secondary-light border-bottom-0 pt-4">
                                    No activity found for this period.
                                </li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>

            <!-- 5. Call Summary -->
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header border-bottom">
                        <h5 class="card-title mb-0">Call Summary</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-secondary-light">Total Calls</span>
                                <span class="fw-medium">{{ $user->total_calls ?? 0 }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-secondary-light">Answered Calls</span>
                                <span class="fw-medium text-success-main">{{ $user->answered_calls ?? 0 }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-secondary-light">Not Answered</span>
                                <span class="fw-medium text-danger-main">{{ ($user->total_calls ?? 0) - ($user->answered_calls ?? 0) }}</span>
                            </li>
                            {{--
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-secondary-light">Calls &ge; 2 Min</span>
                                <span class="fw-medium">{{ $user->calls_gt_2min ?? 0 }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-secondary-light">Calls &lt; 2 Min</span>
                                <span class="fw-medium">{{ ($user->total_calls ?? 0) - ($user->calls_gt_2min ?? 0) }}</span>
                            </li>
                            --}}
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-secondary-light">Avg. Duration</span>
                                <span class="fw-medium">{{ gmdate('i:s', $user->avg_duration ?? 0) }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 border-bottom-0">
                                <span class="text-secondary-light">Total Talk Time</span>
                                <span class="fw-medium text-primary-main">{{ floor(($user->talk_sec ?? 0) / 3600) . 'h ' . gmdate('i\m', $user->talk_sec ?? 0) }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- 6. Productivity Summary -->
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header border-bottom">
                        <h5 class="card-title mb-0">Productivity</h5>
                    </div>
                    <div class="card-body">
                        @php
                            $availableSec = max(0, ($user->login_sec ?? 0) - ($user->talk_sec ?? 0) - ($user->pause_sec ?? 0));
                            $answeredPerHour = ($user->login_sec ?? 0) > 0 ? number_format(($user->answered_calls ?? 0) / (($user->login_sec ?? 0) / 3600), 1) : 0;
                        @endphp
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-secondary-light">Login Hours</span>
                                <span class="fw-medium">{{ floor(($user->login_sec ?? 0) / 3600) . 'h ' . gmdate('i\m', $user->login_sec ?? 0) }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-secondary-light">Pause Time</span>
                                <span class="fw-medium text-warning-main">{{ floor(($user->pause_sec ?? 0) / 3600) . 'h ' . gmdate('i\m', $user->pause_sec ?? 0) }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-secondary-light">Available Time</span>
                                <span class="fw-medium text-success-main">{{ floor($availableSec / 3600) . 'h ' . gmdate('i\m', $availableSec) }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-secondary-light">Talk Time</span>
                                <span class="fw-medium text-primary-main">{{ floor(($user->talk_sec ?? 0) / 3600) . 'h ' . gmdate('i\m', $user->talk_sec ?? 0) }}</span>
                            </li>
                            {{-- <li class="list-group-item d-flex justify-content-between align-items-center px-0 mt-3 pt-3 border-top">
                                <span class="text-secondary-light">Calls / Hour</span>
                                <span class="fw-bold fs-5">{{ number_format($user->calls_per_hour ?? 0, 1) }}</span>
                            </li> --}}
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 mt-3 pt-3 border-top border-bottom-0">
                                <span class="text-secondary-light">Answered / Hour</span>
                                <span class="fw-bold fs-5 text-success-main">{{ $answeredPerHour }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- 7. Daily Performance Table -->
        <div class="row gy-4 mb-24">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">Daily Performance</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table striped-table mb-0 text-nowrap">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th class="text-end">Calls</th>
                                        <th class="text-end">Answered</th>
                                        <th class="text-end">Answer Rate</th>
                                        {{-- <th class="text-end">Calls &ge; 2 Mins</th> --}}
                                        <th class="text-end">Avg. Duration</th>
                                        <th class="text-end">Login Hours</th>
                                        <th class="text-end">Pause</th>
                                        <th class="text-end">Talk Time</th>
                                        {{-- <th class="text-end">Calls / Hour</th> --}}
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($dailyPerformance as $day)
                                        @php
                                            $dayAnswerRate = $day->total_calls > 0 ? ($day->answered_calls / $day->total_calls) * 100 : 0;
                                            $dayAvgDuration = $day->answered_calls > 0 ? ($day->talk_sec / $day->answered_calls) : 0;
                                            $dayLoginSec = $day->pause_sec + $day->wait_sec + $day->talk_sec + $day->dispo_sec + $day->dead_sec;
                                            $dayCallsPerHour = $dayLoginSec > 0 ? ($day->total_calls / ($dayLoginSec / 3600)) : 0;
                                        @endphp
                                        <tr>
                                            <td class="fw-medium">{{ \Carbon\Carbon::parse($day->date)->format('M d, Y') }}</td>
                                            <td class="text-end">{{ $day->total_calls }}</td>
                                            <td class="text-end">{{ $day->answered_calls }}</td>
                                            <td class="text-end">
                                                <span class="{{ $dayAnswerRate >= 65 ? 'bg-success-focus text-success-main' : ($dayAnswerRate >= 50 ? 'bg-warning-focus text-warning-main' : 'bg-danger-focus text-danger-main') }} px-24 py-4 rounded-pill text-sm">
                                                    {{ number_format($dayAnswerRate, 2) }}%
                                                </span>
                                            </td>
                                            {{-- <td class="text-end">{{ $day->calls_gt_2min }}</td> --}}
                                            <td class="text-end">{{ gmdate('i:s', $dayAvgDuration) }}</td>
                                            <td class="text-end">{{ floor($dayLoginSec / 3600) . 'h ' . gmdate('i\m', $dayLoginSec) }}</td>
                                            <td class="text-end">{{ floor($day->pause_sec / 3600) . 'h ' . gmdate('i\m', $day->pause_sec) }}</td>
                                            <td class="text-end fw-medium text-dark">{{ floor($day->talk_sec / 3600) . 'h ' . gmdate('i\m', $day->talk_sec) }}</td>
                                            {{-- <td class="text-end">{{ number_format($dayCallsPerHour, 1) }}</td> --}}
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center py-4 text-secondary-light">No daily performance data available for the selected period.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 8. Call History -->
        <div class="row gy-4">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <h5 class="card-title mb-0">Call History</h5>
                        <form id="callHistoryFilterForm" method="GET" action="{{ route('lms.agent.performance', $user->id) }}" class="d-flex align-items-center gap-2">
                            <!-- Preserve date filters -->
                            @if(request()->has('date_preset')) <input type="hidden" name="date_preset" value="{{ request('date_preset') }}"> @endif
                            @if(request()->has('date_from')) <input type="hidden" name="date_from" value="{{ request('date_from') }}"> @endif
                            @if(request()->has('date_to')) <input type="hidden" name="date_to" value="{{ request('date_to') }}"> @endif
                            
                            <select name="call_status" class="form-select form-select-sm w-auto">
                                <option value="">All Statuses</option>
                                <option value="Answered" {{ request('call_status') == 'Answered' ? 'selected' : '' }}>Answered</option>
                                <option value="No Answer" {{ request('call_status') == 'No Answer' ? 'selected' : '' }}>No Answer</option>
                            </select>

                            <select name="call_sort" class="form-select form-select-sm w-auto">
                                <option value="desc" {{ request('call_sort') == 'desc' ? 'selected' : '' }}>Newest First</option>
                                <option value="asc" {{ request('call_sort') == 'asc' ? 'selected' : '' }}>Oldest First</option>
                            </select>
                        </form>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table striped-table mb-0 text-nowrap">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>List ID</th>
                                        <th>Call Status</th>
                                        @hasanyrole('Admin|Manager|Cluster|TeamLeader|Supervisor|admin|manager|cluster|teamleader|supervisor')
                                        <th>Duration</th>
                                        <th class="text-center">Recording</th>
                                        @endhasanyrole
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($callHistory as $call)
                                        @php
                                            $callTime = \Carbon\Carbon::parse($call->event_time);
                                            $isAnswered = $call->is_answered;
                                            
                                            if ($isAnswered) {
                                                $badgeClass = 'bg-success-transparent text-success-main';
                                                $statusText = 'Answered';
                                            } else {
                                                $badgeClass = 'bg-warning-transparent text-warning-main';
                                                $statusText = $call->status ?? 'No Answer';
                                            }
                                        @endphp
                                        <tr>
                                            <td>{{ $callTime->format('h:i A') }} <span class="text-secondary-light fs-xs d-block">{{ $callTime->format('M d, Y') }}</span></td>
                                            <td class="text-primary-main fw-medium">List #{{ $call->list_id }}</td>
                                        
                                            <td><span class="badge {{ $badgeClass }} rounded-pill px-24 py-4 text-sm">{{ $statusText }}</span></td>
                                            @hasanyrole('Admin|Manager|Cluster|TeamLeader|Supervisor|admin|manager|cluster|teamleader|supervisor')
                                            <td>{{ gmdate('i:s', $call->talk_sec ?? 0) }}</td>
                                            <td class="text-center">
                                                @if($isAnswered && ($call->talk_sec > 0))
                                                    @if(!empty($call->recording_filename))
                                                        @php
                                                            $recUrl = rtrim(env('RECORDING_DOMAIN', ''), '/') . '/RECORDINGS/' . $callTime->format('M-Y') . '/' . $callTime->format('d-M-Y') . '/' . $call->recording_filename . '-all.mp3';
                                                        @endphp
                                                        <button type="button" class="btn btn-primary-transparent btn-sm rounded-circle w-40-px h-40-px d-inline-flex justify-content-center align-items-center play-recording-btn" 
                                                                data-recording-url="{{ $recUrl }}" 
                                                                data-lead-id="List #{{ $call->list_id }}" 
                                                                data-call-date="{{ $callTime->format('M d, Y') }}"
                                                                title="Play Recording">
                                                            <iconify-icon icon="solar:play-circle-bold" class="text-xl"></iconify-icon>
                                                        </button>
                                                    @else
                                                        <span class="text-secondary-light" title="No Recording Found"> No recording</span>
                                                    @endif
                                                @else
                                                    <span class="text-secondary-light">No recording</span>
                                                @endif
                                            </td>
                                            @endhasanyrole
                                        </tr>
                                    @empty
                                        <tr>
                                            @hasanyrole('Admin|Manager|Cluster|TeamLeader|Supervisor|admin|manager|cluster|teamleader|supervisor')
                                            <td colspan="5" class="text-center py-4 text-secondary-light">No call history available for the selected period.</td>
                                            @else
                                            <td colspan="3" class="text-center py-4 text-secondary-light">No call history available for the selected period.</td>
                                            @endhasanyrole
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-24">
                            {{ $callHistory->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
