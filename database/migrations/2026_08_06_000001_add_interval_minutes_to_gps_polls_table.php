<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Allow each duck's GPS auto-poll interval to be configured individually
     * instead of the hardcoded 1-minute default in PollGps::INTERVAL_MINUTES.
     */
    public function up(): void
    {
        Schema::table('gps_polls', function (Blueprint $table) {
            $table->unsignedSmallInteger('interval_minutes')->default(1)->after('enabled');
        });
    }

    public function down(): void
    {
        Schema::table('gps_polls', function (Blueprint $table) {
            $table->dropColumn('interval_minutes');
        });
    }
};
