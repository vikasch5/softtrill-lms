<?php

namespace App\Services;

use App\Models\User;
use App\Models\PushSubscription;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class PushNotificationService
{
    /**
     * Dispatch web push notifications for a user.
     */
    public function dispatchToUser(User $user, array $data)
    {
        if (empty(env('VAPID_PUBLIC_KEY')) || empty(env('VAPID_PRIVATE_KEY'))) {
            return;
        }

        // We defer to a queued job to avoid slowing down the web request
        \App\Jobs\SendWebPushNotification::dispatch($user, $data);
    }

    /**
     * Send payload to a specific subscription synchronously.
     */
    public function sendToSubscription(PushSubscription $sub, array $payload)
    {
        $auth = [
            'VAPID' => [
                'subject' => env('VAPID_SUBJECT', 'mailto:admin@example.com'),
                'publicKey' => env('VAPID_PUBLIC_KEY'),
                'privateKey' => env('VAPID_PRIVATE_KEY'),
            ],
        ];

        $webPush = new WebPush($auth);

        $subscription = Subscription::create([
            'endpoint' => $sub->endpoint,
            'publicKey' => $sub->public_key,
            'authToken' => $sub->auth_token,
            'contentEncoding' => $sub->content_encoding ?? 'aesgcm',
        ]);

        $res = $webPush->sendOneNotification($subscription, json_encode($payload));
        
        if (!$res->isSuccess()) {
            // Check if it's expired/invalid (404, 410)
            $statusCode = $res->getRequest()->getResponse()->getStatusCode();
            if ($statusCode === 404 || $statusCode === 410) {
                $sub->delete(); // Clean up invalid subscriptions
            }
            \Illuminate\Support\Facades\Log::warning('Push Notification Failed', [
                'endpoint' => $sub->endpoint,
                'reason' => $res->getReason(),
                'status' => $statusCode,
            ]);
        } else {
            $sub->update(['last_used_at' => now()]);
        }
    }
}
