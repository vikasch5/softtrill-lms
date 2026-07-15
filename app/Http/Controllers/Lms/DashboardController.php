<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\User;
use App\Models\UserDetails;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $stats = $this->getDashboardStats($user);

        return view('lms.pages.dashboard', $stats);
    }

    public function getDashboardStats($user): array
    {
        $visibleUserIds = $this->getVisibleUserIds($user);

        $leadQuery = Lead::query();
        if (! $user->hasRole('Admin')) {
            $leadQuery->whereIn('assigned_to', $visibleUserIds);
        }

        $totalLeads = (clone $leadQuery)->count();

        $convertedLeads = (clone $leadQuery)
            ->whereIn('status', ['completed', 'converted', 'closed', 'won'])
            ->count();

        $pendingLeads = (clone $leadQuery)
            ->whereIn('status', ['pending', 'waiting', 'hold', 'on_hold'])
            ->count();

        $activeLeads = max(0, $totalLeads - $convertedLeads - $pendingLeads);

        $agentQuery = User::query()->whereHas('roles', function ($query) {
            $query->where('name', 'Agent');
        });

        if (! $user->hasRole('Admin')) {
            $agentQuery->whereIn('id', $visibleUserIds);
        }

        $totalAgents = $agentQuery->count();

        return [
            'totalLeads' => $totalLeads,
            'totalAgents' => $totalAgents,
            'activeLeads' => $activeLeads,
            'convertedLeads' => $convertedLeads,
            'pendingLeads' => $pendingLeads,
        ];
    }

    private function getVisibleUserIds($user): array
    {
        $userId = $user->id;

        if ($user->hasRole('Admin')) {
            return [];
        }

        $visibleIds = [$userId];

        if ($user->hasRole('Manager')) {
            $teamLeaderIds = UserDetails::where('manager_id', $userId)
                ->pluck('user_id')
                ->toArray();

            $visibleIds = array_merge($visibleIds, $teamLeaderIds);

            if (! empty($teamLeaderIds)) {
                $agentIds = UserDetails::whereIn('teamleader_id', $teamLeaderIds)
                    ->pluck('user_id')
                    ->toArray();

                $visibleIds = array_merge($visibleIds, $agentIds);
            }

            $directAgentIds = UserDetails::where('manager_id', $userId)
                ->pluck('user_id')
                ->toArray();

            $visibleIds = array_merge($visibleIds, $directAgentIds);
        } elseif ($user->hasRole('Cluster')) {
            $clusterUserIds = UserDetails::where('cluster_id', $userId)
                ->pluck('user_id')
                ->toArray();

            $visibleIds = array_merge($visibleIds, $clusterUserIds);
        } elseif ($user->hasRole('TeamLeader')) {
            $agentIds = UserDetails::where('teamleader_id', $userId)
                ->pluck('user_id')
                ->toArray();

            $visibleIds = array_merge($visibleIds, $agentIds);
        }

        return array_values(array_unique($visibleIds));
    }
}
