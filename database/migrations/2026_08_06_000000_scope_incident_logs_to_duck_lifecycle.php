<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Scopes the incident lifecycle to a single duck (device) rather than a
 * single SOS message. Previously `message_id` was unique, so every SOS
 * retransmission from the same duck spawned a brand-new "open" incident,
 * silently orphaning any acknowledgement/assignment already in progress.
 *
 * Now: at most one non-resolved IncidentLog may exist per duck_id. New SOS
 * messages for a duck that already has an open incident update that same
 * row (bumping message_id/cluster_data_id and retransmission_count)
 * instead of creating a duplicate.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incident_logs', function (Blueprint $table) {
            $table->dropUnique(['message_id']);
            $table->index('message_id');
            $table->unsignedInteger('retransmission_count')->default(1)->after('message_id');
        });

        // Historical data may already contain multiple non-resolved incidents
        // for the same duck (exactly the bug this migration fixes). Collapse
        // each duck's duplicates into one canonical row before the new
        // uniqueness constraint is added, otherwise the index creation fails.
        $this->mergeDuplicateOpenIncidents();

        // Partial unique index: only one open (non-resolved) incident per duck.
        // Supported by SQLite (this project's only DB driver, see database.php).
        DB::statement(
            'CREATE UNIQUE INDEX incident_logs_open_duck_unique ON incident_logs (duck_id) WHERE status != \'resolved\''
        );
    }

    private function mergeDuplicateOpenIncidents(): void
    {
        $duckIds = DB::table('incident_logs')
            ->where('status', '!=', 'resolved')
            ->select('duck_id')
            ->groupBy('duck_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('duck_id');

        $statusRank = ['open' => 0, 'acknowledged' => 1, 'responding' => 2, 'resolved' => 3];

        foreach ($duckIds as $duckId) {
            $logs = DB::table('incident_logs')
                ->where('duck_id', $duckId)
                ->where('status', '!=', 'resolved')
                ->orderBy('created_at')
                ->get();

            $canonical  = $logs->first();
            $duplicates = $logs->where('id', '!=', $canonical->id);

            // Prefer the most-progressed status/assignment/notes found among
            // the duplicates, since those reflect real dispatcher actions.
            $best = $logs->sortByDesc(fn ($l) => $statusRank[$l->status] ?? 0)->first();

            DB::table('incident_logs')->where('id', $canonical->id)->update([
                'message_id'           => $logs->last()->message_id,
                'cluster_data_id'      => $logs->last()->cluster_data_id,
                'retransmission_count' => $logs->count(),
                'status'               => $best->status,
                'notes'                => $best->notes ?? $canonical->notes,
                'assigned_to'          => $best->assigned_to ?? $canonical->assigned_to,
                'assigned_at'          => $best->assigned_at ?? $canonical->assigned_at,
                'acknowledged_at'      => $canonical->acknowledged_at ?? $best->acknowledged_at,
            ]);

            DB::table('incident_logs')
                ->whereIn('id', $duplicates->pluck('id'))
                ->update(['status' => 'resolved', 'resolved_at' => now()]);
        }
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS incident_logs_open_duck_unique');

        Schema::table('incident_logs', function (Blueprint $table) {
            $table->dropColumn('retransmission_count');
            $table->dropIndex(['message_id']);
            $table->unique('message_id');
        });
    }
};
