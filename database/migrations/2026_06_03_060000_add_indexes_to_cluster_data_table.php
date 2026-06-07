<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add indexes to cluster_data to speed up the common query patterns:
     *
     *   WHERE topic IN (...) ORDER BY id DESC   → composite (topic, id)
     *   GROUP BY duck_id / WHERE duck_id = ?    → (duck_id)
     *   WHERE created_at BETWEEN ? AND ?        → (created_at)
     */
    public function up(): void
    {
        Schema::table('cluster_data', function (Blueprint $table) {
            $table->index(['topic', 'id'], 'cluster_data_topic_id_index');
            $table->index('duck_id',       'cluster_data_duck_id_index');
            $table->index('created_at',    'cluster_data_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('cluster_data', function (Blueprint $table) {
            $table->dropIndex('cluster_data_topic_id_index');
            $table->dropIndex('cluster_data_duck_id_index');
            $table->dropIndex('cluster_data_created_at_index');
        });
    }
};
