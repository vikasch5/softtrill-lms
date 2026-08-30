<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PushSubscription;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class TestPushNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push:test {user_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test push notification to a user or all users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');

        $query = PushSubscription::query();
        if ($userId) {
            $query->where('user_id', $userId);
        }

        $subscriptions = $query->get();

        if ($subscriptions->isEmpty()) {
            $this->error('No push subscriptions found' . ($userId ? " for user $userId" : ''));
            return;
        }

        $auth = [
            'VAPID' => [
                'subject' => env('APP_URL', 'mailto:admin@example.com'),
                'publicKey' => config('services.webpush.vapid_public_key', env('VAPID_PUBLIC_KEY', '')),
                'privateKey' => config('services.webpush.vapid_private_key', env('VAPID_PRIVATE_KEY', '')),
            ],
        ];

        $webPush = new WebPush($auth);
        $payload = json_encode([
            'title' => 'Test Notification!',
            'body' => 'This is a test web push notification from Laravel.',
            'url' => '/',
            'icon_url' => '/lms/images/icons/favicon-192.png'
        ]);

        foreach ($subscriptions as $sub) {
            $webPush->sendOneNotification(
                Subscription::create([
                    'endpoint' => $sub->endpoint,
                    'publicKey' => $sub->public_key,
                    'authToken' => $sub->auth_token,
                ]),
                $payload
            );
        }

        $this->info("Sent notifications. Flushing...");
        
        // Ensure to flush the notifications
        $reports = $webPush->flush();

        if (is_array($reports) || is_iterable($reports)) {
            foreach ($reports as $report) {
                if ($report->isSuccess()) {
                    $this->info("[v] Message sent successfully for subscription {$report->getRequest()->getUri()}.");
                } else {
                    $this->error("[x] Message failed to sent for subscription {$report->getRequest()->getUri()}: {$report->getReason()}");
                    
                    if ($report->isSubscriptionExpired()) {
                        $this->warn('Subscription expired.');
                    }
                }
            }
        }
        
        $this->info("Done sending push notifications!");
    }
}
