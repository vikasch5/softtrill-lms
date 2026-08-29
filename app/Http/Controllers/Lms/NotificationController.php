<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Fetch recent unread notifications for dropdown.
     */
    public function fetch(Request $request)
    {
        $limit = $request->input('limit', 10);
        $notifications = $this->notificationService->getUnreadForDropdown($request->user(), $limit);
        
        return response()->json([
            'unread_count' => $request->user()->unreadNotifications()->count(),
            'notifications' => $notifications,
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();
        
        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        
        return response()->json(['success' => true]);
    }

    /**
     * Subscribe to Web Push Notifications.
     */
    public function subscribePush(Request $request)
    {
        $request->validate([
            'endpoint' => 'required|url',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
            'contentEncoding' => 'nullable|string',
        ]);

        $subscription = PushSubscription::updateOrCreate(
            ['endpoint' => $request->endpoint],
            [
                'user_id' => $request->user()->id,
                'public_key' => $request->input('keys.p256dh'),
                'auth_token' => $request->input('keys.auth'),
                'content_encoding' => $request->input('contentEncoding'),
                'user_agent' => $request->userAgent(),
                'last_used_at' => now(),
            ]
        );

        return response()->json(['success' => true, 'subscription_id' => $subscription->id]);
    }

    /**
     * Unsubscribe from Web Push Notifications.
     */
    public function unsubscribePush(Request $request)
    {
        $request->validate(['endpoint' => 'required|url']);
        
        PushSubscription::where('endpoint', $request->endpoint)
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Update Notification Preferences.
     */
    public function updatePreferences(Request $request)
    {
        $validated = $request->validate([
            'preferences' => 'required|array',
        ]);

        $user = $request->user();
        $prefs = $user->notification_preferences ?? [];
        
        // Merge preferences
        foreach ($validated['preferences'] as $key => $value) {
            $prefs[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        $user->update(['notification_preferences' => $prefs]);

        return response()->json(['success' => true]);
    }
}
