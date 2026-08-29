<?php

namespace Tests\Unit;

use App\Jobs\ProcessFollowupNotifications;
use App\Models\Lead;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FollowupNotificationJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_followup_job_deduplicates_notifications()
    {
        $user = User::factory()->create();
        $now = Carbon::now();
        
        $lead = Lead::factory()->create([
            'assigned_to' => $user->id,
            'next_followup_at' => $now->copy()->addMinutes(15)->startOfMinute()
        ]);

        $job = new ProcessFollowupNotifications();

        // First run - should create notification
        $job->handle(app(\App\Services\NotificationService::class), app(\App\Services\PushNotificationService::class));
        $this->assertEquals(1, DB::table('notifications')->where('notifiable_id', $user->id)->count());

        // Second run - should NOT create duplicate notification for the exact same event
        $job->handle(app(\App\Services\NotificationService::class), app(\App\Services\PushNotificationService::class));
        $this->assertEquals(1, DB::table('notifications')->where('notifiable_id', $user->id)->count());
    }

    public function test_overdue_notifications_are_throttled_and_aggregated()
    {
        $user = User::factory()->create();
        Carbon::setTestNow(Carbon::parse('2026-08-01 10:30:00'));
        $now = Carbon::now();

        // 3 overdue leads
        Lead::factory()->count(3)->create([
            'assigned_to' => $user->id,
            'next_followup_at' => $now->copy()->subMinutes(60)
        ]);

        $job = new ProcessFollowupNotifications();
        $job->handle(app(\App\Services\NotificationService::class), app(\App\Services\PushNotificationService::class));

        $notifications = DB::table('notifications')->where('notifiable_id', $user->id)->get();
        $this->assertCount(1, $notifications);
        $this->assertStringContainsString('3 overdue follow-ups', $notifications->first()->data);
    }
}
