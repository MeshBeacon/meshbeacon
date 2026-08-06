<?php

namespace Tests\Feature;

use App\Models\ClusterData;
use App\Models\IncidentLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A "read-only" instance (DASHBOARD_READONLY=true) is meant to be a
 * monitoring-only hybrid central aggregator — see docs/HYBRID_DEPLOYMENT.md.
 * Dispatch (acknowledge/assign/notes/resolve) must be structurally
 * impossible there, not just hidden in the UI.
 */
class DashboardReadonlyTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatch_actions_are_blocked_when_readonly(): void
    {
        config(['services.central_dms.dashboard_readonly' => true]);

        $user = User::factory()->create();
        $log  = IncidentLog::create([
            'duck_id'    => 'MAMADUCK1',
            'message_id' => 'msg-1',
            'status'     => 'open',
        ]);

        $this->actingAs($user)
            ->postJson('/dashboard/sos-ack', ['duck_id' => 'MAMADUCK1', 'message_id' => 'msg-1'])
            ->assertStatus(403);

        $this->actingAs($user)
            ->postJson('/dashboard/incidents/bulk-acknowledge')
            ->assertStatus(403);

        $this->actingAs($user)
            ->patchJson('/dashboard/incidents/msg-1/status', ['status' => 'acknowledged'])
            ->assertStatus(403);

        $this->actingAs($user)
            ->patchJson('/dashboard/incidents/msg-1/notes', ['notes' => 'hello'])
            ->assertStatus(403);

        $this->actingAs($user)
            ->patchJson('/dashboard/incidents/msg-1/assign', ['user_id' => $user->id])
            ->assertStatus(403);

        $this->assertSame('open', $log->fresh()->status);
        $this->assertNull($log->fresh()->notes);
        $this->assertNull($log->fresh()->assigned_to);
    }

    public function test_dispatch_actions_work_normally_when_not_readonly(): void
    {
        config(['services.central_dms.dashboard_readonly' => false]);

        $user = User::factory()->create();
        IncidentLog::create([
            'duck_id'    => 'MAMADUCK1',
            'message_id' => 'msg-1',
            'status'     => 'open',
        ]);

        $this->actingAs($user)
            ->patchJson('/dashboard/incidents/msg-1/status', ['status' => 'acknowledged'])
            ->assertStatus(200);
    }

    public function test_read_only_flag_is_exposed_on_the_dashboard_page(): void
    {
        config(['services.central_dms.dashboard_readonly' => true]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('data-dashboard-readonly="1"', false);
    }
}
