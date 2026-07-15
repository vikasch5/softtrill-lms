<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use App\Models\UserDetails;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_sees_all_leads_and_agents(): void
    {
        Role::create(['name' => 'Admin']);
        Role::create(['name' => 'Agent']);

        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $agentOne = User::factory()->create();
        $agentOne->assignRole('Agent');

        $agentTwo = User::factory()->create();
        $agentTwo->assignRole('Agent');

        Lead::create([
            'added_by' => $admin->id,
            'tenant_id' => 1,
            'list_id' => 1,
            'name' => 'Lead One',
            'email' => 'lead1@example.com',
            'phone_number' => '1111111111',
            'assigned_to' => $agentOne->id,
            'status' => 'completed',
            'data' => [],
            'created_by' => $admin->id,
        ]);

        Lead::create([
            'added_by' => $admin->id,
            'tenant_id' => 1,
            'list_id' => 1,
            'name' => 'Lead Two',
            'email' => 'lead2@example.com',
            'phone_number' => '2222222222',
            'assigned_to' => $agentTwo->id,
            'status' => 'pending',
            'data' => [],
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin);

        $controller = new \App\Http\Controllers\Lms\DashboardController();
        $stats = $controller->getDashboardStats($admin);

        $this->assertSame(2, $stats['totalLeads']);
        $this->assertSame(2, $stats['totalAgents']);
        $this->assertSame(1, $stats['convertedLeads']);
        $this->assertSame(1, $stats['pendingLeads']);
        $this->assertSame(0, $stats['activeLeads']);
    }

    public function test_manager_dashboard_sees_only_his_team(): void
    {
        Role::create(['name' => 'Manager']);
        Role::create(['name' => 'TeamLeader']);
        Role::create(['name' => 'Agent']);

        $manager = User::factory()->create();
        $manager->assignRole('Manager');

        $teamLeader = User::factory()->create();
        $teamLeader->assignRole('TeamLeader');

        $agent = User::factory()->create();
        $agent->assignRole('Agent');

        UserDetails::create([
            'user_id' => $teamLeader->id,
            'manager_id' => $manager->id,
        ]);

        UserDetails::create([
            'user_id' => $agent->id,
            'teamleader_id' => $teamLeader->id,
        ]);

        Lead::create([
            'added_by' => $manager->id,
            'tenant_id' => 1,
            'list_id' => 1,
            'name' => 'Team Lead',
            'email' => 'team@example.com',
            'phone_number' => '3333333333',
            'assigned_to' => $agent->id,
            'status' => 'new',
            'data' => [],
            'created_by' => $manager->id,
        ]);

        $this->actingAs($manager);

        $controller = new \App\Http\Controllers\Lms\DashboardController();
        $stats = $controller->getDashboardStats($manager);

        $this->assertSame(1, $stats['totalLeads']);
        $this->assertSame(1, $stats['totalAgents']);
    }
}
