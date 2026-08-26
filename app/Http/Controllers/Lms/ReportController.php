<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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

    public function report(Request $request)
    {
        $currentUser = Auth::user();
        [$datePreset, $dateFrom, $dateTo, $dateFromTime, $dateToTime] = $this->resolveDateRange($request);

        // Core Eloquent query for paginated view only
        $query = User::with('details.teamleader', 'details.manager')
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

        return view('lms.pages.performance-report', compact('users', 'dateFrom', 'dateTo', 'datePreset', 'aggregate'));
    }

    public function agentPerformance(Request $request, $id = null)
    {
        $userId = $id ?? Auth::id();
        $user = User::with('details.teamleader', 'details.manager', 'details.cluster')->findOrFail($userId);
        
        [$datePreset, $dateFrom, $dateTo, $dateFromTime, $dateToTime] = $this->resolveDateRange($request);

        $employeeId = $user->details->employee_id ?? null;
        $metrics = [
            'total_calls' => 0, 'answered_calls' => 0, 'pause_sec' => 0, 'talk_sec' => 0,
            'login_sec' => 0, 'answer_rate' => 0, 'avg_duration' => 0, 'calls_per_hour' => 0, 'calls_gt_2min' => 0,
        ];
        
        $dailyPerformance = [];
        $callHistory = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
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

                // Apply Sorting
                $sortOrder = strtolower($request->input('call_sort', 'desc')) === 'asc' ? 'asc' : 'desc';
                $callHistoryQuery->orderBy('val.event_time', $sortOrder);

                $callHistory = $callHistoryQuery->paginate(15)->appends($request->query());
                
                // Lazy-load Recordings and Answered Status ONLY for the 15 paginated items
                if ($callHistory->count() > 0) {
                    $paginatedLeadIds = collect($callHistory->items())->pluck('lead_id')->unique()->toArray();
                    
                    $recordingsMap = $dialerDb->table('recording_log')
                        ->select('lead_id', DB::raw('MAX(filename) as filename'))
                        ->whereIn('lead_id', $paginatedLeadIds)
                        ->groupBy('lead_id')
                        ->get()
                        ->keyBy('lead_id');
                        
                    $answeredMap = $dialerDb->table('vicidial_carrier_log')
                        ->whereIn('lead_id', $paginatedLeadIds)
                        ->where('call_date', '>=', $dateFromTime)
                        ->where('call_date', '<', $dateToTime)
                        ->pluck('lead_id')
                        ->flip(); // Flip to make lead_id the key for fast array_key_exists
                        
                    $callHistory->getCollection()->transform(function($item) use ($recordingsMap, $answeredMap) {
                        $item->recording_filename = $recordingsMap->has($item->lead_id) ? $recordingsMap->get($item->lead_id)->filename : null;
                        $item->is_answered = $answeredMap->has($item->lead_id) ? 1 : 0;
                        return $item;
                    });
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

        return view('lms.pages.agent-performance', compact('user', 'dateFrom', 'dateTo', 'datePreset', 'dailyPerformance', 'callHistory', 'callingActivity'));
    }
}
