<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AgentPerformanceExport;

class ReportController extends Controller
{
    private function resolveDateRange(Request $request)
    {
        $datePreset = $request->input('date_preset', 'today');
        
        if ($datePreset == 'today') {
            $dateFrom = Carbon::today()->toDateString();
            $dateTo = Carbon::today()->toDateString();
        } elseif ($datePreset == 'yesterday') {
            $dateFrom = Carbon::yesterday()->toDateString();
            $dateTo = Carbon::yesterday()->toDateString();
        } elseif ($datePreset == 'this_week') {
            $dateFrom = Carbon::now()->startOfWeek()->toDateString();
            $dateTo = Carbon::today()->toDateString();
        } elseif ($datePreset == 'this_month') {
            $dateFrom = Carbon::now()->startOfMonth()->toDateString();
            $dateTo = Carbon::today()->toDateString();
        } else {
            $dateFrom = $request->input('date_from') ?: Carbon::today()->toDateString();
            $dateTo = $request->input('date_to') ?: Carbon::today()->toDateString();
        }

        $dateFromTime = Carbon::parse($dateFrom)->startOfDay()->toDateTimeString();
        $dateToTime = Carbon::parse($dateTo)->addDay()->startOfDay()->toDateTimeString();

        return [$datePreset, $dateFrom, $dateTo, $dateFromTime, $dateToTime];
    }

    private function getBulkAgentMetrics($dialerDb, array $employeeIds, $dateFromTime, $dateToTime)
    {
        if (empty($employeeIds)) {
            return collect();
        }

        // Base Metrics grouping by user
        $baseMetrics = $dialerDb->table('vicidial_agent_log')
            ->select(
                'user',
                DB::raw('COUNT(agent_log_id) as total_calls'),
                DB::raw('SUM(CASE WHEN talk_sec >= 120 THEN 1 ELSE 0 END) as calls_gt_2min'),
                DB::raw('SUM(talk_sec) as talk_sec'),
                DB::raw('SUM(pause_sec) as pause_sec'),
                DB::raw('SUM(wait_sec) as wait_sec'),
                DB::raw('SUM(dispo_sec) as dispo_sec'),
                DB::raw('SUM(dead_sec) as dead_sec')
            )
            ->whereIn('user', $employeeIds)
            ->where('event_time', '>=', $dateFromTime)
            ->where('event_time', '<', $dateToTime)
            ->groupBy('user')
            ->get()
            ->keyBy('user');

        // Answered Metrics using EXISTS (avoids expensive JOIN + COUNT DISTINCT)
        $answeredMetrics = $dialerDb->table('vicidial_agent_log as val')
            ->select('val.user', DB::raw('COUNT(val.agent_log_id) as answered_calls'))
            ->whereExists(function($q) use ($dateFromTime, $dateToTime) {
                $q->select(DB::raw(1))
                  ->from('vicidial_carrier_log as vcl')
                  ->whereColumn('vcl.lead_id', 'val.lead_id')
                  ->where('vcl.call_date', '>=', $dateFromTime)
                  ->where('vcl.call_date', '<', $dateToTime);
            })
            ->whereIn('val.user', $employeeIds)
            ->where('val.event_time', '>=', $dateFromTime)
            ->where('val.event_time', '<', $dateToTime)
            ->groupBy('val.user')
            ->get()
            ->keyBy('user');

        $result = [];
        foreach ($employeeIds as $empId) {
            $base = $baseMetrics->get($empId);
            $ans = $answeredMetrics->get($empId);

            $totalCalls = $base->total_calls ?? 0;
            $answeredCalls = $ans->answered_calls ?? 0;
            $callsGt2Min = $base->calls_gt_2min ?? 0;
            
            $pauseSec = $base->pause_sec ?? 0;
            $waitSec = $base->wait_sec ?? 0;
            $talkSec = $base->talk_sec ?? 0;
            $dispoSec = $base->dispo_sec ?? 0;
            $deadSec = $base->dead_sec ?? 0;

            $loginSec = $pauseSec + $waitSec + $talkSec + $dispoSec + $deadSec;
            $answerRate = $totalCalls > 0 ? ($answeredCalls / $totalCalls) * 100 : 0;
            $avgDuration = $answeredCalls > 0 ? ($talkSec / $answeredCalls) : 0;
            $callsPerHour = $loginSec > 0 ? ($totalCalls / ($loginSec / 3600)) : 0;

            $result[$empId] = [
                'total_calls' => $totalCalls,
                'answered_calls' => $answeredCalls,
                'calls_gt_2min' => $callsGt2Min,
                'pause_sec' => $pauseSec,
                'wait_sec' => $waitSec,
                'talk_sec' => $talkSec,
                'dispo_sec' => $dispoSec,
                'dead_sec' => $deadSec,
                'login_sec' => $loginSec,
                'answer_rate' => $answerRate,
                'avg_duration' => $avgDuration,
                'calls_per_hour' => $callsPerHour,
            ];
        }

        return $result;
    }

    private function getFilteredQueries(Request $request)
    {
        $currentUser = Auth::user();

        // Core Eloquent query
        $query = User::with('details.teamleader', 'details.manager', 'details.cluster')
            ->whereDoesntHave('roles', function($q) {
                $q->whereIn('name', ['Admin', 'admin', 'Cluster', 'cluster', 'Manager', 'manager']);
            });

        // Lightweight query for Employee IDs (avoids Eloquent hydration)
        $employeeIdsQuery = DB::table('user_details')
            ->whereNotNull('employee_id')
            ->whereNotIn('user_id', function ($q) {
                $q->select('model_id')
                  ->from('model_has_roles')
                  ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                  ->where('model_has_roles.model_type', User::class)
                  ->whereIn('roles.name', ['Admin', 'admin', 'Cluster', 'cluster', 'Manager', 'manager']);
            })
            ->select('employee_id');

        if ($currentUser->hasRole(['Admin', 'admin'])) {
            // Admin can see all users
        } elseif ($currentUser->hasRole(['Cluster', 'cluster'])) {
            $query->whereHas('details', function ($sq) use ($currentUser) { $sq->where('cluster_id', $currentUser->id); });
            $employeeIdsQuery->where('cluster_id', $currentUser->id);
        } elseif ($currentUser->hasRole(['Manager', 'manager'])) {
            $query->whereHas('details', function ($sq) use ($currentUser) { $sq->where('manager_id', $currentUser->id); });
            $employeeIdsQuery->where('manager_id', $currentUser->id);
        } elseif ($currentUser->hasRole(['TeamLeader', 'teamleader', 'Supervisor', 'supervisor'])) {
            $query->where(function($q) use ($currentUser) {
                $q->whereHas('details', function ($sq) use ($currentUser) { $sq->where('teamleader_id', $currentUser->id); })
                  ->orWhere('id', $currentUser->id);
            });
            $employeeIdsQuery->where(function($q) use ($currentUser) {
                $q->where('teamleader_id', $currentUser->id)->orWhere('user_id', $currentUser->id);
            });
        } else {
            // Normal agent, see only themselves
            $query->where('id', $currentUser->id);
            $employeeIdsQuery->where('user_id', $currentUser->id);
        }

        // Apply Hierarchy Filters from Request
        if ($request->filled('agent_id')) {
            $query->where('id', $request->agent_id);
            $employeeIdsQuery->where('user_id', $request->agent_id);
        } elseif ($request->filled('teamleader_id')) {
            $query->whereHas('details', function ($sq) use ($request) { $sq->where('teamleader_id', $request->teamleader_id); });
            $employeeIdsQuery->where('teamleader_id', $request->teamleader_id);
        } elseif ($request->filled('manager_id')) {
            $query->whereHas('details', function ($sq) use ($request) { $sq->where('manager_id', $request->manager_id); });
            $employeeIdsQuery->where('manager_id', $request->manager_id);
        } elseif ($request->filled('cluster_id')) {
            $query->whereHas('details', function ($sq) use ($request) { $sq->where('cluster_id', $request->cluster_id); });
            $employeeIdsQuery->where('cluster_id', $request->cluster_id);
        }

        return [$query, $employeeIdsQuery];
    }

    public function report(Request $request)
    {
        [$datePreset, $dateFrom, $dateTo, $dateFromTime, $dateToTime] = $this->resolveDateRange($request);
        [$query, $employeeIdsQuery] = $this->getFilteredQueries($request);
        
        $users = $query->paginate(10)->appends($request->query());
        
        $aggregate = [
            'total_calls' => 0, 'answered_calls' => 0, 'talk_sec' => 0,
            'answer_rate' => 0, 'avg_duration' => 0, 'total_agents' => 0,
        ];

        try {
            $dialerDb = DB::connection('dialer');
            
            // Pluck employee IDs using raw DB builder
            $allEmployeeIds = $employeeIdsQuery->pluck('employee_id')->filter()->unique()->toArray();
            
            $aggregate['total_agents'] = count($allEmployeeIds);
            
            if (!empty($allEmployeeIds)) {
                $bulkMetrics = $this->getBulkAgentMetrics($dialerDb, $allEmployeeIds, $dateFromTime, $dateToTime);
                
                // Set metrics for the current paginated view
                foreach ($users as $u) {
                    $empId = $u->details->employee_id ?? null;
                    if ($empId && isset($bulkMetrics[$empId])) {
                        $m = $bulkMetrics[$empId];
                        $u->total_calls = $m['total_calls'];
                        $u->answered_calls = $m['answered_calls'];
                        $u->pause_sec = $m['pause_sec'];
                        $u->talk_sec = $m['talk_sec'];
                        $u->login_sec = $m['login_sec'];
                        $u->answer_rate = $m['answer_rate'];
                        $u->avg_duration = $m['avg_duration'];
                        $u->calls_per_hour = $m['calls_per_hour'];
                        $u->calls_gt_2min = $m['calls_gt_2min'];
                    } else {
                        $u->total_calls = 0; $u->answered_calls = 0; $u->pause_sec = 0; $u->talk_sec = 0;
                        $u->login_sec = 0; $u->answer_rate = 0; $u->avg_duration = 0;
                        $u->calls_per_hour = 0; $u->calls_gt_2min = 0;
                    }
                }

                // Calculate Top-Level Aggregates
                $totCalls = 0;
                $totAns = 0;
                $totTalk = 0;
                foreach($bulkMetrics as $m) {
                    $totCalls += $m['total_calls'];
                    $totAns += $m['answered_calls'];
                    $totTalk += $m['talk_sec'];
                }
                
                $aggregate['total_calls'] = $totCalls;
                $aggregate['answered_calls'] = $totAns;
                $aggregate['talk_sec'] = $totTalk;
                $aggregate['answer_rate'] = $totCalls > 0 ? ($totAns / $totCalls) * 100 : 0;
                $aggregate['avg_duration'] = $totAns > 0 ? ($totTalk / $totAns) : 0;
            } else {
                foreach ($users as $u) {
                    $u->total_calls = 0; $u->answered_calls = 0; $u->pause_sec = 0; $u->talk_sec = 0;
                    $u->login_sec = 0; $u->answer_rate = 0; $u->avg_duration = 0;
                    $u->calls_per_hour = 0; $u->calls_gt_2min = 0;
                }
            }
        } catch (\Exception $e) {
            foreach ($users as $u) {
                $u->total_calls = 0; $u->answered_calls = 0; $u->pause_sec = 0; $u->talk_sec = 0;
                $u->login_sec = 0; $u->answer_rate = 0; $u->avg_duration = 0;
                $u->calls_per_hour = 0; $u->calls_gt_2min = 0;
            }
            \Log::error("Dialer DB Error in ReportController: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
        }

        if ($request->ajax()) {
            return view('lms.pages.partials.performance-report-content', compact('users', 'aggregate'))->render();
        }

        return view('lms.pages.performance-report', compact('users', 'dateFrom', 'dateTo', 'datePreset', 'aggregate'));
    }

    private function formatSeconds($seconds)
    {
        if (!is_numeric($seconds) || $seconds <= 0) return '0h 00m';
        return floor((int)$seconds / 3600) . 'h ' . gmdate('i\m', (int)$seconds);
    }
    
    private function formatPercentage($decimal)
    {
        return number_format($decimal, 2) . '%';
    }

    public function export(Request $request)
    {
        [$datePreset, $dateFrom, $dateTo, $dateFromTime, $dateToTime] = $this->resolveDateRange($request);
        [$query, $employeeIdsQuery] = $this->getFilteredQueries($request);

        // Fetch all matching users without pagination for export
        $users = $query->get(['users.id', 'users.name']);
        
        $exportData = collect();
        $aggregate = [
            'total_agents' => count($users),
            'total_calls' => 0,
            'answered_calls' => 0,
            'talk_sec' => 0,
            'answer_rate' => 0,
            'avg_duration' => 0,
        ];

        try {
            $dialerDb = DB::connection('dialer');
            $allEmployeeIds = $employeeIdsQuery->pluck('employee_id')->filter()->unique()->toArray();
            
            $bulkMetrics = [];
            if (!empty($allEmployeeIds)) {
                $bulkMetrics = $this->getBulkAgentMetrics($dialerDb, $allEmployeeIds, $dateFromTime, $dateToTime);
                
                foreach ($bulkMetrics as $m) {
                    $aggregate['total_calls'] += $m['total_calls'];
                    $aggregate['answered_calls'] += $m['answered_calls'];
                    $aggregate['talk_sec'] += $m['talk_sec'];
                }
                
                if ($aggregate['total_calls'] > 0) {
                    $aggregate['answer_rate'] = ($aggregate['answered_calls'] / $aggregate['total_calls']) * 100;
                }
                if ($aggregate['answered_calls'] > 0) {
                    $aggregate['avg_duration'] = $aggregate['talk_sec'] / $aggregate['answered_calls'];
                }
            }
            
            foreach ($users as $user) {
                $empId = $user->details->employee_id ?? null;
                if ($empId && isset($bulkMetrics[$empId])) {
                    $m = $bulkMetrics[$empId];
                } else {
                    $m = [
                        'total_calls' => 0, 'answered_calls' => 0, 'calls_gt_2min' => 0,
                        'pause_sec' => 0, 'wait_sec' => 0, 'talk_sec' => 0,
                        'dispo_sec' => 0, 'dead_sec' => 0, 'login_sec' => 0,
                        'answer_rate' => 0, 'avg_duration' => 0, 'calls_per_hour' => 0,
                    ];
                }
                
                $exportData->push([
                    'Agent Name' => $user->name,
                    'Employee ID' => $empId ?? 'N/A',
                    'Cluster' => optional(optional($user->details)->cluster)->name ?? 'N/A',
                    'Manager' => optional(optional($user->details)->manager)->name ?? 'N/A',
                    'Team Leader' => optional(optional($user->details)->teamleader)->name ?? 'N/A',
                    'Total Calls' => $m['total_calls'],
                    'Answered' => $m['answered_calls'],
                    'Answer Rate' => $this->formatPercentage($m['answer_rate']),
                    'Avg. Duration' => $m['avg_duration'] > 0 ? gmdate('i:s', (int)$m['avg_duration']) : '00:00',
                    'Login Hours' => $this->formatSeconds($m['login_sec']),
                    'Pause' => $this->formatSeconds($m['pause_sec']),
                    'Talk Time' => $this->formatSeconds($m['talk_sec']),
                ]);
            }
        } catch (\Exception $e) {
            \Log::error("Dialer DB Error in ReportController@export: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            return redirect()->back()->with('error', 'Unable to generate export due to a database error. Please try again later.');
        }

        $dateString = Carbon::parse($dateFrom)->format('d M Y');
        if ($dateFrom !== $dateTo) {
            $dateString .= ' - ' . Carbon::parse($dateTo)->format('d M Y');
        }

        $filename = 'agent-performance-';
        if ($dateFrom === $dateTo) {
            $filename .= $dateFrom;
        } else {
            $filename .= "{$dateFrom}-to-{$dateTo}";
        }
        $filename .= '.xlsx';

        return Excel::download(new AgentPerformanceExport($exportData, $dateString, $aggregate), $filename);
    }

    private function getAgentPerformanceData(Request $request, $userId, $isExport = false)
    {
        $user = User::with('details.teamleader', 'details.manager', 'details.cluster')->findOrFail($userId);
        $currentUser = Auth::user();

        // Authorization check
        if ($userId != $currentUser->id && !$currentUser->hasRole(['Admin', 'admin'])) {
            $isAuthorized = false;
            $details = $user->details;
            
            if ($details) {
                if ($currentUser->hasRole(['Cluster', 'cluster']) && $details->cluster_id == $currentUser->id) {
                    $isAuthorized = true;
                } elseif ($currentUser->hasRole(['Manager', 'manager']) && $details->manager_id == $currentUser->id) {
                    $isAuthorized = true;
                } elseif ($currentUser->hasRole(['TeamLeader', 'teamleader', 'Supervisor', 'supervisor']) && $details->teamleader_id == $currentUser->id) {
                    $isAuthorized = true;
                }
            }

            if (!$isAuthorized) {
                abort(403, 'Unauthorized access to this report.');
            }
        }
        
        [$datePreset, $dateFrom, $dateTo, $dateFromTime, $dateToTime] = $this->resolveDateRange($request);

        $employeeId = $user->details->employee_id ?? null;
        $metrics = [
            'total_calls' => 0, 'answered_calls' => 0, 'pause_sec' => 0, 'talk_sec' => 0,
            'login_sec' => 0, 'answer_rate' => 0, 'avg_duration' => 0, 'calls_per_hour' => 0, 'calls_gt_2min' => 0,
        ];
        
        $dailyPerformance = [];
        $callHistory = $isExport ? collect() : new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
        $callingActivity = collect();
        
        try {
            if ($employeeId) {
                $dialerDb = DB::connection('dialer');
                
                // Get Metrics using the bulk function for this single user
                $bulkMetrics = $this->getBulkAgentMetrics($dialerDb, [$employeeId], $dateFromTime, $dateToTime);
                if (isset($bulkMetrics[$employeeId])) {
                    $metrics = $bulkMetrics[$employeeId];
                }
                
                // Get Daily Performance (Using EXISTS)
                $dailyPerformance = $dialerDb->select("
                    SELECT 
                        base.date,
                        base.total_calls,
                        IFNULL(ans.answered_calls, 0) as answered_calls,
                        base.calls_gt_2min,
                        base.talk_sec,
                        base.pause_sec,
                        base.wait_sec,
                        base.dispo_sec,
                        base.dead_sec
                    FROM (
                        SELECT 
                            DATE(event_time) as date,
                            COUNT(agent_log_id) as total_calls,
                            SUM(CASE WHEN talk_sec >= 120 THEN 1 ELSE 0 END) as calls_gt_2min,
                            SUM(talk_sec) as talk_sec,
                            SUM(pause_sec) as pause_sec,
                            SUM(wait_sec) as wait_sec,
                            SUM(dispo_sec) as dispo_sec,
                            SUM(dead_sec) as dead_sec
                        FROM vicidial_agent_log
                        WHERE user = ?
                        AND event_time >= ?
                        AND event_time < ?
                        GROUP BY DATE(event_time)
                    ) base
                    LEFT JOIN (
                        SELECT 
                            DATE(v2.event_time) as date,
                            COUNT(v2.agent_log_id) as answered_calls
                        FROM vicidial_agent_log v2
                        WHERE v2.user = ?
                        AND v2.event_time >= ?
                        AND v2.event_time < ?
                        AND EXISTS (
                            SELECT 1 FROM vicidial_carrier_log c 
                            WHERE c.lead_id = v2.lead_id 
                            AND c.call_date >= ?
                            AND c.call_date < ?
                        )
                        GROUP BY DATE(v2.event_time)
                    ) ans ON base.date = ans.date
                    ORDER BY base.date DESC
                ", [$employeeId, $dateFromTime, $dateToTime, $employeeId, $dateFromTime, $dateToTime, $dateFromTime, $dateToTime]);
                
                // Get Call History - Pure Pagination (No Joins)
                $callHistoryQuery = $dialerDb->table('vicidial_agent_log as val')
                    ->select(
                        'val.agent_log_id',
                        'val.event_time', 
                        'val.lead_id', 
                        'val.campaign_id', 
                        'val.status', 
                        'val.talk_sec'
                    )
                    ->where('val.user', $employeeId)
                    ->where('val.event_time', '>=', $dateFromTime)
                    ->where('val.event_time', '<', $dateToTime);

                // Apply Filters using fast EXISTS
                if ($request->filled('call_status')) {
                    if ($request->call_status === 'Answered') {
                        $callHistoryQuery->whereExists(function($q) use ($dateFromTime, $dateToTime) {
                            $q->select(DB::raw(1))
                              ->from('vicidial_carrier_log as vcl')
                              ->whereColumn('vcl.lead_id', 'val.lead_id')
                              ->where('vcl.call_date', '>=', $dateFromTime)
                              ->where('vcl.call_date', '<', $dateToTime);
                        });
                    } elseif ($request->call_status === 'No Answer') {
                        $callHistoryQuery->whereNotExists(function($q) use ($dateFromTime, $dateToTime) {
                            $q->select(DB::raw(1))
                              ->from('vicidial_carrier_log as vcl')
                              ->whereColumn('vcl.lead_id', 'val.lead_id')
                              ->where('vcl.call_date', '>=', $dateFromTime)
                              ->where('vcl.call_date', '<', $dateToTime);
                        });
                    }
                }

                $sortOrder = strtolower($request->input('call_sort', 'desc')) === 'asc' ? 'asc' : 'desc';
                $callHistoryQuery->orderBy('val.event_time', $sortOrder);

                if ($isExport) {
                    $callHistory = $callHistoryQuery->get();
                } else {
                    $callHistory = $callHistoryQuery->paginate(15)->appends($request->query());
                }
                
                // Lazy-load Recordings and Answered Status
                $hasItems = $isExport ? $callHistory->count() > 0 : $callHistory->count() > 0;
                if ($hasItems) {
                    $paginatedLeadIds = $isExport ? $callHistory->pluck('lead_id')->unique()->toArray() : collect($callHistory->items())->pluck('lead_id')->unique()->toArray();
                    
                    $recordingsMap = collect();
                    try {
                        $recordingsMap = $dialerDb->table('recording_log')
                            ->select('lead_id', DB::raw('MAX(filename) as filename'))
                            ->whereIn('lead_id', $paginatedLeadIds)
                            ->groupBy('lead_id')
                            ->get()
                            ->keyBy('lead_id');
                    } catch (\Exception $e) {
                        \Log::warning("Could not fetch recordings: " . $e->getMessage());
                    }
                        
                    $answeredMap = $dialerDb->table('vicidial_carrier_log')
                        ->whereIn('lead_id', $paginatedLeadIds)
                        ->where('call_date', '>=', $dateFromTime)
                        ->where('call_date', '<', $dateToTime)
                        ->pluck('lead_id')
                        ->flip(); // Flip to make lead_id the key for fast array_key_exists
                        
                    $listIdMap = $dialerDb->table('vicidial_list')
                        ->select('lead_id', 'vendor_lead_code')
                        ->whereIn('lead_id', $paginatedLeadIds)
                        ->get()
                        ->keyBy('lead_id');
                        
                    $transformFn = function($item) use ($recordingsMap, $answeredMap, $listIdMap) {
                        $item->recording_filename = $recordingsMap->has($item->lead_id) ? $recordingsMap->get($item->lead_id)->filename : null;
                        $item->is_answered = $answeredMap->has($item->lead_id) ? 1 : 0;
                        $item->list_id = $listIdMap->has($item->lead_id) ? $listIdMap->get($item->lead_id)->vendor_lead_code : $item->lead_id;
                        return $item;
                    };
                    
                    if ($isExport) {
                        $callHistory->transform($transformFn);
                    } else {
                        $callHistory->getCollection()->transform($transformFn);
                    }
                }
                    
                // Get Calling Activity (Calls per hour)
                $callingActivity = $dialerDb->table('vicidial_agent_log')
                    ->select(DB::raw('HOUR(event_time) as hour'), DB::raw('COUNT(*) as total_calls'))
                    ->where('user', $employeeId)
                    ->where('event_time', '>=', $dateFromTime)
                    ->where('event_time', '<', $dateToTime)
                    ->groupBy(DB::raw('HOUR(event_time)'))
                    ->orderBy('hour', 'asc')
                    ->get();
            }
        } catch (\Exception $e) {
            \Log::error("Dialer DB Error in agentPerformance: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
        }
        
        $user->total_calls = $metrics['total_calls'];
        $user->answered_calls = $metrics['answered_calls'];
        $user->pause_sec = $metrics['pause_sec'];
        $user->talk_sec = $metrics['talk_sec'];
        $user->login_sec = $metrics['login_sec'];
        $user->answer_rate = $metrics['answer_rate'];
        $user->avg_duration = $metrics['avg_duration'];
        $user->calls_per_hour = $metrics['calls_per_hour'];
        $user->calls_gt_2min = $metrics['calls_gt_2min'];

        return [$user, $dateFrom, $dateTo, $datePreset, $dailyPerformance, $callHistory, $callingActivity];
    }

    public function agentPerformance(Request $request, $id = null)
    {
        $userId = $id ?? Auth::id();
        [$user, $dateFrom, $dateTo, $datePreset, $dailyPerformance, $callHistory, $callingActivity] = $this->getAgentPerformanceData($request, $userId, false);

        if ($request->ajax()) {
            return view('lms.pages.partials.agent-performance-content', compact('user', 'dailyPerformance', 'callHistory', 'callingActivity'))->render();
        }

        return view('lms.pages.agent-performance', compact('user', 'dateFrom', 'dateTo', 'datePreset', 'dailyPerformance', 'callHistory', 'callingActivity'));
    }

    public function exportAgentPerformance(Request $request, $id)
    {
        $userId = $id;
        [$user, $dateFrom, $dateTo, $datePreset, $dailyPerformance, $callHistory, $callingActivity] = $this->getAgentPerformanceData($request, $userId, true);

        $dateString = Carbon::parse($dateFrom)->format('d M Y');
        if ($dateFrom !== $dateTo) {
            $dateString .= ' - ' . Carbon::parse($dateTo)->format('d M Y');
        }

        $filename = 'agent-performance-' . str_replace(' ', '-', strtolower($user->name)) . '-';
        if ($dateFrom === $dateTo) {
            $filename .= $dateFrom;
        } else {
            $filename .= "{$dateFrom}-to-{$dateTo}";
        }
        $filename .= '.xlsx';

        return Excel::download(new \App\Exports\SingleAgentExport($user->name, $dailyPerformance, $callHistory, $dateString), $filename);
    }

    /**
     * API for fetching users in the hierarchy for reports
     */
    public function getHierarchyUsers(Request $request)
    {
        $role = $request->input('role');
        $parentId = $request->input('parent_id');

        $query = User::role($role)->select('users.id', 'users.name');

        if ($parentId) {
            $query->whereHas('details', function ($q) use ($role, $parentId) {
                if ($role === 'Manager') {
                    $q->where('cluster_id', $parentId);
                } elseif ($role === 'TeamLeader') {
                    $q->where('manager_id', $parentId);
                } elseif ($role === 'Agent') {
                    $q->where('teamleader_id', $parentId);
                }
            });
        }

        // Apply auth user visibility restrictions
        $currentUser = Auth::user();
        if ($currentUser->hasRole(['Cluster', 'cluster'])) {
             $query->whereHas('details', function($q) use ($currentUser) { $q->where('cluster_id', $currentUser->id); });
        } elseif ($currentUser->hasRole(['Manager', 'manager'])) {
             $query->whereHas('details', function($q) use ($currentUser) { $q->where('manager_id', $currentUser->id); });
        } elseif ($currentUser->hasRole(['TeamLeader', 'teamleader', 'Supervisor', 'supervisor'])) {
             $query->whereHas('details', function($q) use ($currentUser) { $q->where('teamleader_id', $currentUser->id); });
        }

        $users = $query->orderBy('name')->get();
        return response()->json($users);
    }
}
