<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cluster_data_id')->nullable()->index();
            $table->string('duck_id', 64)->index();
            $table->string('message_id', 64)->unique();
            $table->enum('status', ['open', 'acknowledged', 'responding', 'resolved'])
                  ->default('open')
                  ->index();
            $table->text('notes')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_logs');
    }
};
