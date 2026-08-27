<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use App\Models\PushSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use App\Events\LeadAssigned;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_push_subscription_creation()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/notifications/push/subscribe', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/fake-endpoint',
            'keys' => [
                'p256dh' => 'fake_p256dh_key',
                'auth' => 'fake_auth_key'
            ],
            'contentEncoding' => 'aesgcm'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $user->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/fake-endpoint'
        ]);
        
        // Ensure user can have multiple subscriptions (different endpoints)
        $this->actingAs($user)->postJson('/notifications/push/subscribe', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/second-endpoint',
            'keys' => ['p256dh' => 'key2', 'auth' => 'auth2']
        ]);
        
        $this->assertEquals(2, $user->pushSubscriptions()->count());
    }

    public function test_push_unsubscribe_removes_subscription()
    {
        $user = User::factory()->create();
        $sub = PushSubscription::create([
            'user_id' => $user->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test',
            'public_key' => 'test',
            'auth_token' => 'test'
        ]);

        $response = $this->actingAs($user)->postJson('/notifications/push/unsubscribe', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('push_subscriptions', ['id' => $sub->id]);
    }

    public function test_user_preferences_can_be_updated()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/notifications/preferences', [
            'preferences' => [
                'followup_due' => false,
                'lead_assigned' => true
            ]
        ]);

        $response->assertStatus(200);
        $user->refresh();
        $this->assertFalse($user->notification_preferences['followup_due']);
        $this->assertTrue($user->notification_preferences['lead_assigned']);
    }

    public function test_lead_assigned_triggers_notification()
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->create(['assigned_to' => $user->id]);

        event(new LeadAssigned($lead, $user));

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'type' => 'App\Notifications\SystemNotification'
        ]);
    }
}
