<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Services\NotificationService;
use App\Services\PushNotificationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessFollowupNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notificationService, PushNotificationService $pushService): void
    {
        $now = Carbon::now();
        
        // 1. Check for Upcoming Followups (15 mins from now)
        $upcomingTargetStart = $now->copy()->addMinutes(15)->startOfMinute();
        $upcomingTargetEnd = $now->copy()->addMinutes(15)->endOfMinute();
        
        $upcomingLeads = Lead::whereBetween('next_followup_at', [$upcomingTargetStart, $upcomingTargetEnd])
            ->whereNotNull('assigned_to')
            ->get();
            
        foreach ($upcomingLeads as $lead) {
            $this->processLeadNotification($lead, 'upcoming_followup', 'Upcoming Follow-up', "Follow-up with {$lead->name} is in 15 minutes.", $notificationService, $pushService, $now->copy()->startOfMinute());
        }

        // 2. Check for Due Followups (exactly now)
        $dueStart = $now->copy()->startOfMinute();
        $dueEnd = $now->copy()->endOfMinute();
        
        $dueLeads = Lead::whereBetween('next_followup_at', [$dueStart, $dueEnd])
            ->whereNotNull('assigned_to')
            ->get();
            
        foreach ($dueLeads as $lead) {
            $this->processLeadNotification($lead, 'followup_due', 'Follow-up Due', "{$lead->name}'s follow-up is due now.", $notificationService, $pushService, $now->copy()->startOfMinute());
        }

        // 3. Throttled Overdue Followups (Aggregated)
        // We shouldn't notify every minute. We will run the overdue aggregation every 30 minutes, or just when it becomes 30m/1h overdue.
        // Let's find agents who have overdue followups that are exactly 30 minutes overdue (or we can just aggregate at the start of every hour).
        if ($now->minute == 0 || $now->minute == 30) {
            $overdueCounts = Lead::where('next_followup_at', '<', $now->copy()->startOfMinute())
                ->whereNotNull('assigned_to')
                ->select('assigned_to', DB::raw('count(*) as total'))
                ->groupBy('assigned_to')
                ->get();
                
            foreach ($overdueCounts as $row) {
                if ($row->total > 0) {
                    $user = \App\Models\User::find($row->assigned_to);
                    if ($user) {
                        $dedupKey = "overdue_followup_{$user->id}_{$now->format('Y-m-d_H:i')}";
                        
                        if (!DB::table('notifications')->where('data->dedup_key', $dedupKey)->exists()) {
                            $payload = [
                                'type' => 'overdue_followup',
                                'title' => 'Overdue Follow-ups',
                                'message' => "You have {$row->total} overdue follow-ups.",
                                'target_url' => route('lms.leads', ['followup_status' => 'missed']),
                                'icon' => 'iconoir:warning-triangle',
                                'priority' => 'high',
                                'dedup_key' => $dedupKey,
                                'actions' => [
                                    [
                                        'action' => 'view',
                                        'title' => 'View Leads'
                                    ]
                                ]
                            ];

                            if ($notificationService->send($user, $payload)) {
                                $pushService->dispatchToUser($user, $payload);
                            }
                        }
                    }
                }
            }
        }
    }
    
    private function processLeadNotification(Lead $lead, string $type, string $title, string $message, NotificationService $notificationService, PushNotificationService $pushService, Carbon $dedupTime)
    {
        $user = \App\Models\User::find($lead->assigned_to);
        if (!$user) return;
        
        // Deduplication Key: Ensures we never send this exact type for this lead at this time again
        $dedupKey = "{$type}_{$lead->lead_id}_{$dedupTime->format('Y-m-d_H:i')}";
        
        $alreadySent = DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->where('data->dedup_key', $dedupKey)
            ->exists();
            
        if ($alreadySent) return;

        $payload = [
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'lead_id' => $lead->lead_id,
            'target_url' => route('lms.lead.view', $lead->lead_id),
            'icon' => 'iconoir:calendar',
            'priority' => ($type == 'followup_due') ? 'high' : 'normal',
            'dedup_key' => $dedupKey,
            'actions' => [
                [
                    'action' => 'view',
                    'title' => 'View Lead'
                ]
            ]
        ];

        if ($notificationService->send($user, $payload)) {
            $pushService->dispatchToUser($user, $payload);
        }
    }
}
