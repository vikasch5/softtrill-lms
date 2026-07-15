<?php

namespace App\Providers;

use App\Models\Lead;
use App\Models\UserDetails;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        View::composer('lms.common.header', function ($view) {
            $stats = [
                'today' => 0,
                'pending' => 0,
                'upcoming' => 0,
            ];

            $user = Auth::user();

            if (!$user) {
                $view->with('headerFollowupStats', $stats);
                return;
            }

            $visibleUserIds = [$user->id];

            if ($user->hasRole('Manager')) {
                $teamLeaderIds = UserDetails::where('manager_id', $user->id)
                    ->pluck('user_id')
                    ->toArray();

                $agentIds = !empty($teamLeaderIds)
                    ? UserDetails::whereIn('teamleader_id', $teamLeaderIds)->pluck('user_id')->toArray()
                    : [];

                $directAgentIds = UserDetails::where('manager_id', $user->id)
                    ->pluck('user_id')
                    ->toArray();

                $visibleUserIds = array_merge($visibleUserIds, $teamLeaderIds, $agentIds, $directAgentIds);
            } elseif ($user->hasRole('Cluster')) {
                $clusterUserIds = UserDetails::where('cluster_id', $user->id)
                    ->pluck('user_id')
                    ->toArray();

                $visibleUserIds = array_merge($visibleUserIds, $clusterUserIds);
            } elseif ($user->hasRole('TeamLeader')) {
                $agentIds = UserDetails::where('teamleader_id', $user->id)
                    ->pluck('user_id')
                    ->toArray();

                $visibleUserIds = array_merge($visibleUserIds, $agentIds);
            }

            $visibleUserIds = array_values(array_unique($visibleUserIds));
            $today = Carbon::today();

            $followupQuery = Lead::query()
                ->whereNotNull('next_followup_at');

            if (!$user->hasRole('Admin')) {
                $followupQuery->where(function ($query) use ($visibleUserIds) {
                    $query->whereIn('assigned_to', $visibleUserIds)
                        ->orWhereIn('added_by', $visibleUserIds);
                });
            }

            $stats['today'] = (clone $followupQuery)
                ->whereDate('next_followup_at', $today)
                ->count();

            $stats['pending'] = (clone $followupQuery)
                ->where('next_followup_at', '<', $today->copy()->startOfDay())
                ->count();

            $stats['upcoming'] = (clone $followupQuery)
                ->where('next_followup_at', '>', $today->copy()->endOfDay())
                ->count();

            $view->with('headerFollowupStats', $stats);
        });
    }
}
