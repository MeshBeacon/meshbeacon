<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cluster_data', function (Blueprint $table) {
            // Route origin duck ID (RREP: the duck that initiated the route discovery)
            $table->string('origin', 32)->nullable()->after('path');
            // Route destination duck ID (RREP: the duck being routed to)
            $table->string('destination', 32)->nullable()->after('origin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cluster_data', function (Blueprint $table) {
            $table->dropColumn(['origin', 'destination']);
        });
    }
};
