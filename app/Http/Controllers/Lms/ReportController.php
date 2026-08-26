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
    public function report(Request $request)
    {
        $currentUser = Auth::user();
        
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

        $query = User::with('details.teamleader', 'details.manager');

        if ($currentUser->hasRole('Admin')) {
            // Admin can see all users
        } elseif ($currentUser->hasRole('cluster')) {
            $query->whereHas('details', function ($q) use ($currentUser) {
                $q->where('cluster_id', $currentUser->id);
            });
        } elseif ($currentUser->hasRole('manager')) {
            $query->whereHas('details', function ($q) use ($currentUser) {
                $q->where('manager_id', $currentUser->id);
            });
        } elseif ($currentUser->hasRole('teamleader') || $currentUser->hasRole('supervisor')) {
            $query->whereHas('details', function ($q) use ($currentUser) {
                $q->where('teamleader_id', $currentUser->id);
            });
        } else {
            // Normal agent, see only themselves
            $query->where('id', $currentUser->id);
        }
        
        $users = $query->paginate(10)->appends($request->query());
        // dd($users->toArray());
        
        try {
            $dialerDb = DB::connection('dialer');
            foreach ($users as $u) {
                $employeeId = $u->details->employee_id ?? null;
                if ($employeeId) {
                    $metrics = $this->calculateAgentMetrics($dialerDb, $employeeId, $dateFrom, $dateTo);
                    $u->total_calls = $metrics['total_calls'];
                    $u->answered_calls = $metrics['answered_calls'];
                    $u->pause_sec = $metrics['pause_sec'];
                    $u->talk_sec = $metrics['talk_sec'];
                    $u->login_sec = $metrics['login_sec'];
                    $u->answer_rate = $metrics['answer_rate'];
                    $u->avg_duration = $metrics['avg_duration'];
                    $u->calls_per_hour = $metrics['calls_per_hour'];
                    $u->calls_gt_2min = $metrics['calls_gt_2min'];
                } else {
                    $u->total_calls = 0;
                    $u->answered_calls = 0;
                    $u->pause_sec = 0;
                    $u->talk_sec = 0;
                    $u->login_sec = 0;
                    $u->answer_rate = 0;
                    $u->avg_duration = 0;
                    $u->calls_per_hour = 0;
                    $u->calls_gt_2min = 0;
                }
            }
        } catch (\Exception $e) {
            foreach ($users as $u) {
                $u->total_calls = 0;
                $u->answered_calls = 0;
                $u->pause_sec = 0;
                $u->talk_sec = 0;
                $u->login_sec = 0;
                $u->answer_rate = 0;
                $u->avg_duration = 0;
                $u->calls_per_hour = 0;
                $u->calls_gt_2min = 0;
            }
            \Log::error("Dialer DB Error in ReportController: " . $e->getMessage());
        }

        return view('lms.pages.performance-report', compact('users', 'dateFrom', 'dateTo', 'datePreset'));
    }

    public function agentPerformance(Request $request, $id = null)
    {
        $userId = $id ?? Auth::id();
        $user = User::with('details.teamleader', 'details.manager', 'details.cluster')->findOrFail($userId);
        
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

        $employeeId = $user->details->employee_id ?? null;
        $metrics = [
            'total_calls' => 0,
            'answered_calls' => 0,
            'pause_sec' => 0,
            'talk_sec' => 0,
            'login_sec' => 0,
            'answer_rate' => 0,
            'avg_duration' => 0,
            'calls_per_hour' => 0,
            'calls_gt_2min' => 0,
        ];
        
        $dailyPerformance = [];
        $callHistory = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
        $callingActivity = collect();
        
        try {
            if ($employeeId) {
                $dialerDb = DB::connection('dialer');
                $metrics = $this->calculateAgentMetrics($dialerDb, $employeeId, $dateFrom, $dateTo);
                
                // Get Daily Performance
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
                        AND DATE(event_time) >= ?
                        AND DATE(event_time) <= ?
                        GROUP BY DATE(event_time)
                    ) base
                    LEFT JOIN (
                        SELECT 
                            DATE(v2.event_time) as date,
                            COUNT(DISTINCT v2.agent_log_id) as answered_calls
                        FROM vicidial_agent_log v2
                        JOIN vicidial_carrier_log c ON v2.lead_id = c.lead_id AND DATE(c.call_date) = DATE(v2.event_time)
                        WHERE v2.user = ?
                        AND DATE(v2.event_time) >= ?
                        AND DATE(v2.event_time) <= ?
                        GROUP BY DATE(v2.event_time)
                    ) ans ON base.date = ans.date
                    ORDER BY base.date DESC
                ", [$employeeId, $dateFrom, $dateTo, $employeeId, $dateFrom, $dateTo]);
                
                // Get Call History
                $callHistory = $dialerDb->table('vicidial_agent_log')
                    ->leftJoin('vicidial_carrier_log', function($join) {
                        $join->on('vicidial_agent_log.lead_id', '=', 'vicidial_carrier_log.lead_id')
                             ->whereRaw('DATE(vicidial_carrier_log.call_date) = DATE(vicidial_agent_log.event_time)');
                    })
                    ->select(
                        'vicidial_agent_log.agent_log_id',
                        'vicidial_agent_log.event_time', 
                        'vicidial_agent_log.lead_id', 
                        'vicidial_agent_log.campaign_id', 
                        'vicidial_agent_log.status', 
                        'vicidial_agent_log.talk_sec',
                        DB::raw('MAX(CASE WHEN vicidial_carrier_log.uniqueid IS NOT NULL THEN 1 ELSE 0 END) as is_answered')
                    )
                    ->where('vicidial_agent_log.user', $employeeId)
                    ->whereDate('vicidial_agent_log.event_time', '>=', $dateFrom)
                    ->whereDate('vicidial_agent_log.event_time', '<=', $dateTo)
                    ->groupBy(
                        'vicidial_agent_log.agent_log_id',
                        'vicidial_agent_log.event_time',
                        'vicidial_agent_log.lead_id',
                        'vicidial_agent_log.campaign_id',
                        'vicidial_agent_log.status',
                        'vicidial_agent_log.talk_sec'
                    )
                    ->orderBy('vicidial_agent_log.event_time', 'desc')
                    ->paginate(15)
                    ->appends($request->query());
                    
                // Get Calling Activity (Calls per hour)
                $callingActivity = $dialerDb->table('vicidial_agent_log')
                    ->select(DB::raw('HOUR(event_time) as hour'), DB::raw('COUNT(*) as total_calls'))
                    ->where('user', $employeeId)
                    ->whereDate('event_time', '>=', $dateFrom)
                    ->whereDate('event_time', '<=', $dateTo)
                    ->groupBy(DB::raw('HOUR(event_time)'))
                    ->orderBy('hour', 'asc')
                    ->get();
            }
        } catch (\Exception $e) {
            \Log::error("Dialer DB Error in agentPerformance: " . $e->getMessage());
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

    private function calculateAgentMetrics($dialerDb, $employeeId, $dateFrom, $dateTo)
    {
        $totalCalls = $dialerDb->table('vicidial_agent_log')
            ->where('user', $employeeId)
            ->whereDate('event_time', '>=', $dateFrom)
            ->whereDate('event_time', '<=', $dateTo)
            ->count();

        $answeredCalls = $dialerDb->table('vicidial_agent_log')
            ->join('vicidial_carrier_log', function($join) {
                $join->on('vicidial_agent_log.lead_id', '=', 'vicidial_carrier_log.lead_id')
                     ->whereRaw('DATE(vicidial_carrier_log.call_date) = DATE(vicidial_agent_log.event_time)');
            })
            ->where('vicidial_agent_log.user', $employeeId)
            ->whereDate('vicidial_agent_log.event_time', '>=', $dateFrom)
            ->whereDate('vicidial_agent_log.event_time', '<=', $dateTo)
            ->count(DB::raw('DISTINCT vicidial_agent_log.agent_log_id'));
            
        $callsGt2Min = $dialerDb->table('vicidial_agent_log')
            ->where('user', $employeeId)
            ->whereDate('event_time', '>=', $dateFrom)
            ->whereDate('event_time', '<=', $dateTo)
            ->where('talk_sec', '>=', 120)
            ->count();
            
        $stats = $dialerDb->table('vicidial_agent_log')
            ->selectRaw('
                SUM(pause_sec) as pause_sec,
                SUM(wait_sec) as wait_sec,
                SUM(talk_sec) as talk_sec,
                SUM(dispo_sec) as dispo_sec,
                SUM(dead_sec) as dead_sec
            ')
            ->where('user', $employeeId)
            ->whereDate('event_time', '>=', $dateFrom)
            ->whereDate('event_time', '<=', $dateTo)
            ->first();
        
        $pauseSec = $stats->pause_sec ?? 0;
        $talkSec = $stats->talk_sec ?? 0;
        $loginSec = $pauseSec + ($stats->wait_sec ?? 0) + $talkSec + ($stats->dispo_sec ?? 0) + ($stats->dead_sec ?? 0);
        $answerRate = $totalCalls > 0 ? ($answeredCalls / $totalCalls) * 100 : 0;
        $avgDuration = $answeredCalls > 0 ? ($talkSec / $answeredCalls) : 0;
        $callsPerHour = $loginSec > 0 ? ($totalCalls / ($loginSec / 3600)) : 0;

        return [
            'total_calls' => $totalCalls,
            'answered_calls' => $answeredCalls,
            'pause_sec' => $pauseSec,
            'talk_sec' => $talkSec,
            'login_sec' => $loginSec,
            'answer_rate' => $answerRate,
            'avg_duration' => $avgDuration,
            'calls_per_hour' => $callsPerHour,
            'calls_gt_2min' => $callsGt2Min
        ];
    }
}
