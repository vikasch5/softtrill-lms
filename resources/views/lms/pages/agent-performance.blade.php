@extends('lms.common.master')

@section('content')


    <style>
        .table-responsive::-webkit-scrollbar {
            height: 8px;
            width: 8px;
        }

        .table-responsive::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }

        .table-responsive::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>

    <div class="dashboard-main-body">

        <!-- 1. Page Header -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
            <div>
                <h5 class="fw-bold mb-1 text-dark">
                    Agent Performance <span class="text-secondary-light fw-medium mx-1">|</span> <span class="text-primary-main">{{ $user->name ?? 'Unknown' }}</span>
                </h5>
                <p class="text-secondary-light mb-0 fs-sm">Detailed view of dialer performance for this agent</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('lms.performance.report') }}"
                    class="btn btn-outline-secondary d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:arrow-left-outline" class="text-xl"></iconify-icon> Back
                </a>
                <button class="btn btn-outline-primary d-flex align-items-center gap-2" type="button"
                    data-bs-toggle="collapse" data-bs-target="#performanceFiltersCollapse" aria-expanded="false"
                    aria-controls="performanceFiltersCollapse">
                    <iconify-icon icon="lucide:filter" class="text-xl"></iconify-icon> Filter
                </button>
                <button class="btn btn-primary d-flex align-items-center gap-2">
                    <iconify-icon icon="file-icons:microsoft-excel" class="text-xl"></iconify-icon> Export
                </button>
            </div>
        </div>

        <!-- Global Filters -->
        <form id="globalFiltersForm" method="GET" action="{{ route('lms.agent.performance', $user->id) }}" class="mb-4" novalidate>
            <div class="collapse" id="performanceFiltersCollapse">
                <div class="card card-body border rounded-3 bg-light-subtle shadow-sm mb-4">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Date Range</label>
                            <select name="date_preset" class="form-select">
                                <option value="today" {{ ($datePreset ?? '') == 'today' ? 'selected' : '' }}>Today</option>
                                <option value="yesterday" {{ ($datePreset ?? '') == 'yesterday' ? 'selected' : '' }}>Yesterday
                                </option>
                                <option value="this_week" {{ ($datePreset ?? '') == 'this_week' ? 'selected' : '' }}>This Week
                                </option>
                                <option value="this_month" {{ ($datePreset ?? '') == 'this_month' ? 'selected' : '' }}>This
                                    Month</option>
                                <option value="custom" {{ ($datePreset ?? '') == 'custom' ? 'selected' : '' }}>Custom Range
                                </option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Date From</label>
                            <input type="date" name="date_from" class="form-control" value="{{ $dateFrom ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Date To</label>
                            <input type="date" name="date_to" class="form-control" value="{{ $dateTo ?? '' }}">
                        </div>
                        <div class="col-12 mt-3 d-flex justify-content-end gap-2">
                            <a href="{{ route('lms.agent.performance', $user->id) }}" class="btn btn-outline-secondary">Clear</a>
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- 2. Agent Summary (Compact & Aligned) -->
        <div class="card shadow-sm border mb-24 bg-white rounded-3">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-4">
                    <!-- Left Profile Info -->
                    <div class="d-flex align-items-center gap-3">
                        <div class="w-48-px h-48-px rounded-circle bg-primary-transparent text-primary-main d-flex justify-content-center align-items-center fs-4 fw-bold shadow-sm">
                            {{ strtoupper(substr($user->name ?? 'A', 0, 1)) }}
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h6 class="mb-0 fw-bold text-dark">{{ $user->name ?? 'Unknown' }}</h6>
                                <span class="badge bg-success-transparent text-success-main rounded-pill px-2 py-1 fs-xs fw-medium d-flex align-items-center gap-1">
                                    <span class="w-6-px h-6-px rounded-circle bg-success-main d-inline-block"></span> Active
                                </span>
                            </div>
                            <span class="text-secondary-light fs-sm fw-medium d-flex align-items-center gap-1">
                                <iconify-icon icon="solar:user-id-linear"></iconify-icon> Agent #{{ $user->details->employee_id ?? ($user->id ?? '') }}
                            </span>
                        </div>
                    </div>
                    
                    <!-- Right Meta Info -->
                    <div class="d-flex flex-wrap align-items-center gap-4 gap-md-5">
                        @if(optional(optional($user->details)->cluster)->name)
                        <div class="d-flex align-items-center gap-2">
                            <div class="w-32-px h-32-px rounded bg-light-subtle d-flex align-items-center justify-content-center text-primary-main border">
                                <iconify-icon icon="solar:user-bold"></iconify-icon>
                            </div>
                            <div>
                                <p class="text-xs text-secondary-light mb-0 text-uppercase fw-semibold tracking-wide">Cluster</p>
                                <h6 class="mb-0 fw-bold text-dark fs-sm text-truncate" style="max-width: 140px;" title="{{ $user->details->cluster->name }}">{{ $user->details->cluster->name }}</h6>
                            </div>
                        </div>
                        @endif

                        @if(optional(optional($user->details)->manager)->name)
                        <div class="d-flex align-items-center gap-2">
                            <div class="w-32-px h-32-px rounded bg-light-subtle d-flex align-items-center justify-content-center text-info-main border">
                                <iconify-icon icon="solar:user-hands-bold"></iconify-icon>
                            </div>
                            <div>
                                <p class="text-xs text-secondary-light mb-0 text-uppercase fw-semibold tracking-wide">Manager</p>
                                <h6 class="mb-0 fw-bold text-dark fs-sm text-truncate" style="max-width: 140px;" title="{{ $user->details->manager->name }}">{{ $user->details->manager->name }}</h6>
                            </div>
                        </div>
                        @endif
                        
                        @if(optional(optional($user->details)->teamleader)->name)
                        <div class="d-flex align-items-center gap-2">
                            <div class="w-32-px h-32-px rounded bg-light-subtle d-flex align-items-center justify-content-center text-warning-main border">
                                <iconify-icon icon="solar:users-group-rounded-bold"></iconify-icon>
                            </div>
                            <div>
                                <p class="text-xs text-secondary-light mb-0 text-uppercase fw-semibold tracking-wide">Team Leader</p>
                                <h6 class="mb-0 fw-bold text-dark fs-sm text-truncate" style="max-width: 140px;" title="{{ $user->details->teamleader->name }}">{{ $user->details->teamleader->name }}</h6>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        
        <div id="agentPerformanceContent">
            @include('lms.pages.partials.agent-performance-content')
        </div>

    <!-- Modern Recording Player Modal -->
    <div class="modal fade" id="recordingModal" tabindex="-1" aria-labelledby="recordingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg bg-white" style="border-radius: 1.25rem; overflow: hidden;">
                <div class="modal-header border-bottom-0 pt-4 px-4 pb-0">
                    <div class="w-100 text-center position-relative">
                        <h5 class="modal-title fw-bold text-dark mb-1" id="recordingModalLabel">Call Recording</h5>
                        <p class="text-secondary-light fs-sm mb-0" id="recordingSubtitle">Loading...</p>
                        <button type="button" class="btn-close position-absolute top-0 end-0 mt-1 me-1" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                
                <div class="modal-body px-4 py-4 text-center">
                    <!-- Hidden Audio Element -->
                    <audio id="recordingPlayer" class="d-none" preload="metadata">
                        <source src="" type="audio/mpeg">
                    </audio>

                    <!-- Play Controls -->
                    <div class="d-flex align-items-center justify-content-center gap-4 mb-4 mt-3">
                        <button type="button" class="btn btn-light rounded-circle d-flex align-items-center justify-content-center text-secondary-light transition-2 shadow-sm border-0" id="skipBackBtn" title="-10s" style="width: 48px; height: 48px;">
                            <iconify-icon icon="solar:rewind-10-seconds-back-bold" class="text-2xl"></iconify-icon>
                        </button>
                        
                        <button type="button" class="btn btn-primary rounded-circle d-flex align-items-center justify-content-center shadow-lg transition-2 border-0" id="playPauseBtn" style="width: 72px; height: 72px; background: linear-gradient(135deg, var(--bs-primary), #0056b3);">
                            <iconify-icon icon="solar:play-bold" class="text-white" style="font-size: 32px;" id="playIcon"></iconify-icon>
                        </button>

                        <button type="button" class="btn btn-light rounded-circle d-flex align-items-center justify-content-center text-secondary-light transition-2 shadow-sm border-0" id="skipForwardBtn" title="+10s" style="width: 48px; height: 48px;">
                            <iconify-icon icon="solar:forward-10-seconds-bold" class="text-2xl"></iconify-icon>
                        </button>
                    </div>

                    <!-- Progress Bar & Waveform -->
                    <div class="mb-4 mt-2 px-3">
                        <!-- Simulated Waveform -->
                        <div class="d-flex align-items-end justify-content-between gap-1 mb-2" id="waveformContainer" style="height: 40px;">
                            @for($i=0; $i<48; $i++)
                                <div class="bg-primary rounded-pill wave-bar" data-index="{{ $i }}" style="flex-grow: 1; height: {{ rand(20, 100) }}%; opacity: 0.15; transition: opacity 0.2s;"></div>
                            @endfor
                        </div>

                        <div class="d-flex align-items-center justify-content-between text-secondary-light fs-sm fw-medium mb-2">
                            <span id="currentTimeDisplay">00:00</span>
                            <span id="durationDisplay">00:00</span>
                        </div>
                        <div class="progress bg-primary-transparent rounded-pill" style="cursor: pointer; overflow: visible; height: 6px;" id="progressBarContainer">
                            <div class="progress-bar bg-primary rounded-pill position-relative" id="progressBar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                <span class="position-absolute top-50 start-100 translate-middle bg-white rounded-circle shadow border border-2 border-primary" style="cursor: grab; width: 16px; height: 16px;"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Speed & Volume -->
                    <div class="d-flex align-items-center justify-content-between mt-4 px-3">
                        <div class="d-flex align-items-center gap-2">
                            <iconify-icon icon="solar:volume-loud-bold" class="text-secondary-light text-lg" id="volumeIcon"></iconify-icon>
                            <input type="range" class="form-range" id="volumeControl" min="0" max="1" step="0.05" value="1" style="width: 90px; height: 4px;">
                        </div>
                        
                        <div class="bg-light rounded-pill p-1 d-flex gap-1">
                            <button class="btn btn-sm btn-primary text-white rounded-pill px-3 py-1 fs-xs fw-medium shadow-sm speed-btn" data-speed="1">1x</button>
                            <button class="btn btn-sm btn-light text-secondary-light rounded-pill px-3 py-1 fs-xs fw-medium speed-btn border-0" data-speed="1.5">1.5x</button>
                            <button class="btn btn-sm btn-light text-secondary-light rounded-pill px-3 py-1 fs-xs fw-medium speed-btn border-0" data-speed="2">2x</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const recordingModal = document.getElementById('recordingModal');
        if (!recordingModal) return;
        
        const audio = document.getElementById('recordingPlayer');
        const playPauseBtn = document.getElementById('playPauseBtn');
        const playIcon = document.getElementById('playIcon');
        const skipBackBtn = document.getElementById('skipBackBtn');
        const skipForwardBtn = document.getElementById('skipForwardBtn');
        const progressBarContainer = document.getElementById('progressBarContainer');
        const progressBar = document.getElementById('progressBar');
        const currentTimeDisplay = document.getElementById('currentTimeDisplay');
        const durationDisplay = document.getElementById('durationDisplay');
        const volumeControl = document.getElementById('volumeControl');
        const volumeIcon = document.getElementById('volumeIcon');
        const speedBtns = document.querySelectorAll('.speed-btn');
        const subtitle = document.getElementById('recordingSubtitle');
        const waveBars = document.querySelectorAll('.wave-bar');
        const totalBars = waveBars.length;
        
        let isDragging = false;

        function formatTime(seconds) {
            if (isNaN(seconds) || seconds < 0) return "00:00";
            const m = Math.floor(seconds / 60);
            const s = Math.floor(seconds % 60);
            return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
        }

        function updatePlayState() {
            if (audio.paused) {
                playIcon.setAttribute('icon', 'solar:play-bold');
            } else {
                playIcon.setAttribute('icon', 'solar:pause-bold');
            }
        }

        // Play/Pause toggle
        playPauseBtn.addEventListener('click', () => {
            if (audio.paused) {
                audio.play().catch(e => console.log('Playback prevented', e));
            } else {
                audio.pause();
            }
        });

        audio.addEventListener('play', updatePlayState);
        audio.addEventListener('pause', updatePlayState);

        // Time updates
        audio.addEventListener('timeupdate', () => {
            if(!isDragging) {
                const percent = (audio.currentTime / audio.duration) * 100 || 0;
                progressBar.style.width = percent + '%';
                currentTimeDisplay.textContent = formatTime(audio.currentTime);
                
                // Update waveform opacity
                const activeIndex = Math.floor((audio.currentTime / audio.duration) * totalBars);
                waveBars.forEach((bar, index) => {
                    bar.style.opacity = index <= activeIndex ? '1' : '0.15';
                });
            }
        });

        audio.addEventListener('loadedmetadata', () => {
            durationDisplay.textContent = formatTime(audio.duration);
        });
        
        // Skip buttons
        skipBackBtn.addEventListener('click', () => {
            audio.currentTime = Math.max(0, audio.currentTime - 10);
        });
        
        skipForwardBtn.addEventListener('click', () => {
            audio.currentTime = Math.min(audio.duration, audio.currentTime + 10);
        });

        // Progress Bar Click & Drag
        function updateProgressFromEvent(e) {
            const rect = progressBarContainer.getBoundingClientRect();
            let pos = (e.clientX - rect.left) / rect.width;
            pos = Math.max(0, Math.min(1, pos));
            progressBar.style.width = (pos * 100) + '%';
            if (audio.duration) {
                audio.currentTime = pos * audio.duration;
            }
        }

        progressBarContainer.addEventListener('mousedown', (e) => {
            isDragging = true;
            updateProgressFromEvent(e);
        });

        document.addEventListener('mousemove', (e) => {
            if(isDragging) updateProgressFromEvent(e);
        });

        document.addEventListener('mouseup', () => {
            isDragging = false;
        });

        // Volume
        volumeControl.addEventListener('input', (e) => {
            audio.volume = e.target.value;
            if(audio.volume === 0) {
                volumeIcon.setAttribute('icon', 'solar:volume-cross-bold');
            } else if (audio.volume < 0.5) {
                volumeIcon.setAttribute('icon', 'solar:volume-small-bold');
            } else {
                volumeIcon.setAttribute('icon', 'solar:volume-loud-bold');
            }
        });

        // Playback Speed
        speedBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                speedBtns.forEach(b => {
                    b.classList.remove('btn-primary', 'text-white', 'shadow-sm');
                    b.classList.add('btn-light', 'text-secondary-light', 'border-0');
                });
                this.classList.remove('btn-light', 'text-secondary-light', 'border-0');
                this.classList.add('btn-primary', 'text-white', 'shadow-sm');
                audio.playbackRate = parseFloat(this.getAttribute('data-speed'));
            });
        });

        // Open Modal Event
        document.querySelectorAll('.play-recording-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const url = this.getAttribute('data-recording-url');
                const leadId = this.getAttribute('data-lead-id');
                const date = this.getAttribute('data-call-date');
                
                subtitle.textContent = leadId + ' • ' + date;
                audio.src = url;
                
                const bsModal = new bootstrap.Modal(recordingModal);
                bsModal.show();
                
                recordingModal.addEventListener('shown.bs.modal', function onShown() {
                    audio.play().catch(e => console.log('Autoplay prevented:', e));
                    recordingModal.removeEventListener('shown.bs.modal', onShown);
                });
            });
        });
        
        recordingModal.addEventListener('hidden.bs.modal', function () {
            audio.pause();
            audio.currentTime = 0;
            progressBar.style.width = '0%';
            waveBars.forEach(b => b.style.opacity = '0.15');
            playIcon.setAttribute('icon', 'solar:play-bold');
            
            // Reset playback speed to 1x
            audio.playbackRate = 1;
            speedBtns.forEach(b => {
                b.classList.remove('btn-primary', 'text-white', 'shadow-sm');
                b.classList.add('btn-light', 'text-secondary-light', 'border-0');
            });
            const defaultSpeedBtn = document.querySelector('.speed-btn[data-speed="1"]');
            if (defaultSpeedBtn) {
                defaultSpeedBtn.classList.remove('btn-light', 'text-secondary-light', 'border-0');
                defaultSpeedBtn.classList.add('btn-primary', 'text-white', 'shadow-sm');
            }
        });
    });

    // AJAX Handling for Filters and Pagination
    function rebindModalEvents() {
        // Re-bind the play buttons inside the newly loaded content
        document.querySelectorAll('.play-recording-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const url = this.getAttribute('data-recording-url');
                const leadId = this.getAttribute('data-lead-id');
                const date = this.getAttribute('data-call-date');
                
                const subtitle = document.getElementById('recordingSubtitle');
                const audio = document.getElementById('recordingPlayer');
                const recordingModal = document.getElementById('recordingModal');
                
                subtitle.textContent = leadId + ' • ' + date;
                audio.src = url;
                
                const bsModal = new bootstrap.Modal(recordingModal);
                bsModal.show();
                
                recordingModal.addEventListener('shown.bs.modal', function onShown() {
                    audio.play().catch(e => console.log('Autoplay prevented:', e));
                    recordingModal.removeEventListener('shown.bs.modal', onShown);
                });
            });
        });
    }

    function fetchAjaxContent(url, data = {}) {
        const container = $('#agentPerformanceContent');
        container.css('opacity', '0.5'); // Visual loading indicator
        
        $.ajax({
            url: url,
            type: 'GET',
            data: data,
            success: function(response) {
                container.html(response);
                container.css('opacity', '1');
                rebindModalEvents();
            },
            error: function() {
                alert('An error occurred while fetching data. Please try again.');
                container.css('opacity', '1');
            }
        });
    }

    // Handle Global Filters Submit
    $(document).on('submit', '#globalFiltersForm', function(e) {
        e.preventDefault();
        fetchAjaxContent($(this).attr('action'), $(this).serialize());
    });

    // Handle Call History Form Changes (Select dropdowns)
    $(document).on('change', '#callHistoryFilterForm select', function() {
        const globalData = $('#globalFiltersForm').serializeArray();
        const historyData = $('#callHistoryFilterForm').serializeArray();
        
        // Merge the form data so we keep the date context
        const mergedData = $.param(globalData.concat(historyData));
        
        fetchAjaxContent($('#callHistoryFilterForm').attr('action'), mergedData);
    });

    // Handle Pagination Clicks inside the dynamic content
    $(document).on('click', '#agentPerformanceContent .pagination a', function(e) {
        e.preventDefault();
        fetchAjaxContent($(this).attr('href'));
    });

</script>
@endsection
