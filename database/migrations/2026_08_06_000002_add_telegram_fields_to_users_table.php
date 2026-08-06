<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Set once a responder links their Telegram account (via /start deep link).
            $table->string('telegram_chat_id', 64)->nullable()->unique()->after('password');
            // One-time code used to prove ownership of a Telegram chat during linking.
            $table->string('telegram_link_token', 32)->nullable()->unique()->after('telegram_chat_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['telegram_chat_id', 'telegram_link_token']);
        });
    }
};
