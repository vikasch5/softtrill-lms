<?php

namespace App\Listeners;

use App\Events\LeadAssigned;
use App\Services\NotificationService;
use App\Services\PushNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendLeadAssignedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(LeadAssigned $event): void
    {
        $notificationService = app(NotificationService::class);
        $pushService = app(PushNotificationService::class);

        $type = $event->isReassignment ? 'lead_reassigned' : 'lead_assigned';
        $title = $event->isReassignment ? 'Lead Reassigned' : 'New Lead Assigned';
        $message = $event->isReassignment 
            ? "Lead {$event->lead->lead_id} has been reassigned to you." 
            : "A new lead ({$event->lead->lead_id}) has been assigned to you.";

        $payload = [
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'lead_id' => $event->lead->lead_id,
            'target_url' => route('lms.lead.view', $event->lead->lead_id),
            'icon' => 'iconoir:user-plus',
            'priority' => 'normal',
            'actions' => [
                [
                    'action' => 'view',
                    'title' => 'View Lead'
                ]
            ]
        ];

        if ($notificationService->send($event->user, $payload)) {
            $pushService->dispatchToUser($event->user, $payload);
        }
    }
}
