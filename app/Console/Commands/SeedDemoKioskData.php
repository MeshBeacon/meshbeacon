<?php

namespace App\Console\Commands;

use App\Models\ClusterData;
use App\Models\IncidentLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * One-off command: wipes cluster_data / incident_logs and replaces them with
 * clean, fictional demo data suitable for public marketing screenshots.
 * Local dev only — never run against a real deployment.
 */
class SeedDemoKioskData extends Command
{
    protected $signature = 'demo:seed-kiosk';

    protected $description = 'Replace cluster_data/incident_logs with fictional demo data for marketing screenshots';

    public function handle(): int
    {
        ClusterData::query()->delete();
        IncidentLog::query()->delete();

        $admin = User::query()->where('role', User::ROLE_ADMIN)->first();
        $now   = Carbon::now();

        // --- BASECAMP1 (PapaDuck / gateway) — steady heartbeat, all healthy ---
        $this->heartbeats(
            duckId: 'BASECAMP1',
            duckType: 1,
            lat: 3.1579,
            lng: 101.7123,
            batt: 92,
            count: 10,
            spanHours: 11,
        );

        // --- RESCUE07 (MamaDuck) — resolved hardware SOS from a few hours ago ---
        $this->heartbeats(
            duckId: 'RESCUE07',
            duckType: 2,
            lat: 3.1502,
            lng: 101.6987,
            batt: 71,
            count: 8,
            spanHours: 11,
        );

        $resolvedAt = $now->copy()->subHours(3);
        $resolvedMsgId = 'demo-' . Str::random(8);
        $resolvedCluster = ClusterData::forceCreate([
            'duck_id'    => 'RESCUE07',
            'topic'      => 'alert',
            'message_id' => $resolvedMsgId,
            'payload'    => 'SOS,SRC:DEVICE,ID:RESCUE07,LAT:3.1502,LNG:101.6987,ALT:38.4,SPD:0.0,HDG:118.0,BATT:71',
            'path'       => 'RESCUE07,BASECAMP1',
            'hops'       => 1,
            'duck_type'  => 2,
            'created_at' => $resolvedAt,
            'updated_at' => $resolvedAt,
        ]);

        IncidentLog::forceCreate([
            'cluster_data_id'       => $resolvedCluster->id,
            'duck_id'               => 'RESCUE07',
            'message_id'            => $resolvedMsgId,
            'retransmission_count'  => 0,
            'status'                => 'resolved',
            'notes'                 => 'Team dispatched, survivor located and extracted safely.',
            'assigned_to'           => $admin?->id,
            'assigned_at'           => $resolvedAt->copy()->addMinutes(1),
            'acknowledged_at'       => $resolvedAt->copy()->addMinutes(2),
            'resolved_at'           => $resolvedAt->copy()->addMinutes(18),
            'created_at'            => $resolvedAt,
            'updated_at'            => $resolvedAt->copy()->addMinutes(18),
        ]);

        // --- OUTPOST3 (MamaDuck) — currently OPEN mobile-app SOS ---
        $this->heartbeats(
            duckId: 'OUTPOST3',
            duckType: 2,
            lat: 3.1650,
            lng: 101.7050,
            batt: 54,
            count: 6,
            spanHours: 11,
        );

        $openAt = $now->copy()->subMinutes(4);
        $openMsgId = 'demo-' . Str::random(8);
        $openCluster = ClusterData::forceCreate([
            'duck_id'    => 'OUTPOST3',
            'topic'      => 'status',
            'message_id' => $openMsgId,
            'payload'    => 'SOS,ID:OUTPOST3,LAT:3.1650,LNG:101.7050,BATT:54',
            'path'       => null,
            'hops'       => 0,
            'duck_type'  => 2,
            'created_at' => $openAt,
            'updated_at' => $openAt,
        ]);

        IncidentLog::forceCreate([
            'cluster_data_id'      => $openCluster->id,
            'duck_id'              => 'OUTPOST3',
            'message_id'           => $openMsgId,
            'retransmission_count' => 0,
            'status'               => 'open',
            'created_at'           => $openAt,
            'updated_at'           => $openAt,
        ]);

        // --- A couple of relayed messages for the "Recent Relay Paths" panel ---
        for ($i = 0; $i < 3; $i++) {
            ClusterData::forceCreate([
                'duck_id'    => 'OUTPOST3',
                'topic'      => 'rrep',
                'message_id' => 'demo-' . Str::random(8),
                'payload'    => null,
                'path'       => 'OUTPOST3,RESCUE07,BASECAMP1',
                'hops'       => 2,
                'duck_type'  => 2,
                'created_at' => $now->copy()->subMinutes(30 + $i * 20),
                'updated_at' => $now->copy()->subMinutes(30 + $i * 20),
            ]);
        }

        $this->info('Demo kiosk data seeded: BASECAMP1 (Papa), RESCUE07 (Mama, resolved SOS), OUTPOST3 (Mama, open SOS).');

        return self::SUCCESS;
    }

    /**
     * Create $count evenly-spaced "status" heartbeat rows over the past
     * $spanHours for a duck, so the hourly chart / message totals / duck
     * health widget all have plausible-looking data.
     */
    private function heartbeats(string $duckId, int $duckType, float $lat, float $lng, int $batt, int $count, int $spanHours): void
    {
        $now = Carbon::now();

        for ($i = $count; $i >= 1; $i--) {
            $createdAt = $now->copy()->subMinutes((int) ($spanHours * 60 * $i / $count));
            $jitterLat = $lat + mt_rand(-30, 30) / 100000;
            $jitterLng = $lng + mt_rand(-30, 30) / 100000;

            ClusterData::forceCreate([
                'duck_id'    => $duckId,
                'topic'      => 'status',
                'message_id' => 'demo-' . Str::random(8),
                'payload'    => sprintf(
                    'MSG,URGENCY:0,LAT:%.4f,LNG:%.4f,ALT:45.0,SPD:0.0,HDG:0.0,BATT:%d,TEXT:Status OK',
                    $jitterLat,
                    $jitterLng,
                    max(10, $batt - $i)
                ),
                'path'       => null,
                'hops'       => 0,
                'duck_type'  => $duckType,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }
}
