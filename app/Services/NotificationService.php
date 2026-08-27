<?php

namespace App\Services;

use App\Models\User;
use App\Models\Lead;
use App\Models\UserDetails;

class NotificationService
{
    /**
     * Get unread notifications for a user, formatted for the bell dropdown.
     */
    public function getUnreadForDropdown(User $user, int $limit = 10)
    {
        return $user->unreadNotifications()->limit($limit)->get()->map(function ($notification) {
            return [
                'id' => $notification->id,
                'title' => $notification->data['title'] ?? 'Notification',
                'message' => $notification->data['message'] ?? '',
                'url' => $notification->data['target_url'] ?? '#',
                'icon' => $notification->data['icon'] ?? 'iconoir:bell',
                'time_ago' => $notification->created_at->diffForHumans(),
                'priority' => $notification->data['priority'] ?? 'normal',
                'type' => $notification->type,
            ];
        });
    }

    /**
     * Send an in-app notification to a user.
     */
    public function send(User $user, array $data)
    {
        // The data should contain: type, title, message, target_url, icon, priority, etc.
        // We use the generic DatabaseNotification implementation.
        
        // Ensure user has this preference enabled.
        if (!$this->isPreferenceEnabled($user, $data['type'])) {
            return false;
        }

        $notificationClass = \Illuminate\Notifications\Notification::class;
        // Since we are using standard db notifications, we can just use the DB facade or a custom notification class.
        // It's cleaner to use a custom Notification class: \App\Notifications\SystemNotification.
        $user->notify(new \App\Notifications\SystemNotification($data));

        return true;
    }

    /**
     * Check if a user has a specific notification preference enabled.
     */
    public function isPreferenceEnabled(User $user, string $type): bool
    {
        $prefs = $user->notification_preferences ?? [];
        
        // If the key exists, respect it. Otherwise default to true.
        if (isset($prefs[$type])) {
            return (bool) $prefs[$type];
        }

        return true;
    }
}
