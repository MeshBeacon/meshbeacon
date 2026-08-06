<?php

namespace Tests\Feature;

use App\Jobs\SyncRecordToCloud;
use App\Models\ClusterData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Exercises both sides of the hybrid store-and-forward sync described in
 * docs/HYBRID_DEPLOYMENT.md:
 *   - SyncRecordToCloud (field node → central) via Http::fake()
 *   - POST /api/ingest (central receiving from a field node)
 */
class HybridSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_job_posts_record_and_marks_it_synced_on_success(): void
    {
        config(['services.central_dms.url' => 'https://central.example.test']);
        config(['services.central_dms.token' => 'shared-secret']);

        Http::fake([
            'central.example.test/api/ingest' => Http::response(['message' => 'ok'], 200),
        ]);

        $record = ClusterData::create([
            'duck_id'    => 'MAMADUCK1',
            'topic'      => 'status',
            'message_id' => 'msg-1',
            'payload'    => 'MSG,TEXT:hello',
            'hops'       => 1,
            'duck_type'  => 0,
            'synced'     => false,
        ]);

        (new SyncRecordToCloud($record->id))->handle();

        Http::assertSent(function ($request) {
            return $request->url() === 'https://central.example.test/api/ingest'
                && $request->hasHeader('Authorization', 'Bearer shared-secret')
                && $request['duck_id'] === 'MAMADUCK1'
                && $request['message_id'] === 'msg-1';
        });

        $record->refresh();
        $this->assertTrue($record->synced);
        $this->assertNotNull($record->synced_at);
    }

    public function test_sync_job_throws_and_leaves_record_pending_on_failure(): void
    {
        config(['services.central_dms.url' => 'https://central.example.test']);
        config(['services.central_dms.token' => 'shared-secret']);

        Http::fake([
            'central.example.test/api/ingest' => Http::response(['message' => 'error'], 500),
        ]);

        $record = ClusterData::create([
            'duck_id'    => 'MAMADUCK1',
            'topic'      => 'status',
            'message_id' => 'msg-2',
            'payload'    => 'MSG,TEXT:hello',
            'hops'       => 1,
            'duck_type'  => 0,
            'synced'     => false,
        ]);

        $this->expectException(\RuntimeException::class);

        (new SyncRecordToCloud($record->id))->handle();

        $this->assertFalse($record->refresh()->synced);
    }

    public function test_ingest_endpoint_rejects_missing_or_invalid_token(): void
    {
        config(['services.central_dms.token' => 'shared-secret']);

        $payload = [
            'duck_id'    => 'MAMADUCK1',
            'topic'      => 'status',
            'message_id' => 'msg-1',
        ];

        $this->postJson('/api/ingest', $payload)->assertStatus(401);

        $this->withToken('wrong-token')
            ->postJson('/api/ingest', $payload)
            ->assertStatus(401);

        $this->assertDatabaseCount('cluster_data', 0);
    }

    public function test_ingest_endpoint_accepts_valid_record_and_is_idempotent(): void
    {
        config(['services.central_dms.token' => 'shared-secret']);

        $payload = [
            'duck_id'     => 'MAMADUCK1',
            'topic'       => 'status',
            'message_id'  => 'msg-1',
            'payload'     => 'MSG,TEXT:hello',
            'path'        => 'A,B',
            'origin'      => 'A',
            'destination' => 'B',
            'hops'        => 2,
            'duck_type'   => 0,
            'created_at'  => '2026-08-01T12:00:00Z',
        ];

        $this->withToken('shared-secret')
            ->postJson('/api/ingest', $payload)
            ->assertStatus(200)
            ->assertJsonPath('message', 'Record ingested');

        $this->assertDatabaseCount('cluster_data', 1);
        $this->assertDatabaseHas('cluster_data', [
            'duck_id'    => 'MAMADUCK1',
            'message_id' => 'msg-1',
            'origin'     => 'A',
            'destination' => 'B',
        ]);

        // Retrying the same (duck_id, message_id) must not create a duplicate row.
        $this->withToken('shared-secret')
            ->postJson('/api/ingest', $payload)
            ->assertStatus(200);

        $this->assertDatabaseCount('cluster_data', 1);
    }

    public function test_ingest_endpoint_validates_required_fields(): void
    {
        config(['services.central_dms.token' => 'shared-secret']);

        $this->withToken('shared-secret')
            ->postJson('/api/ingest', ['topic' => 'status'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['duck_id', 'message_id']);
    }
}
