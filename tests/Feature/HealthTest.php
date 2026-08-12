<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_liveness_endpoint_returns_without_authentication(): void
    {
        $this->getJson('/health/live')
            ->assertOk()
            ->assertJsonPath('status', 'ok');
    }

    public function test_readiness_reports_database_and_migration_state(): void
    {
        $this->getJson('/health/ready')
            ->assertOk()
            ->assertJsonPath('ready', true)
            ->assertJsonPath('checks.database.status', 'ok')
            ->assertJsonPath('checks.migrations.pending', 0);
    }

    public function test_operations_page_requires_authentication(): void
    {
        $this->get('/system-health')
            ->assertRedirect('/login');
    }

    public function test_authenticated_users_can_view_operations_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/system-health')
            ->assertOk()
            ->assertSee('Runtime health and service activity');
    }

    public function test_metrics_exposes_safe_aggregate_values(): void
    {
        $response = $this->get('/metrics');

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/plain; version=0.0.4; charset=utf-8')
            ->assertSee('meshbeacon_health_ready')
            ->assertSee('meshbeacon_queue_failed_jobs')
            ->assertDontSee('APP_KEY')
            ->assertDontSee('base64:');
    }
}
