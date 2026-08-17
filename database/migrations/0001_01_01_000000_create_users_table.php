<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * osrs-events has exactly one login path — Discord OAuth (see
     * app/Http/Controllers/Auth/DiscordController.php) — so this departs from
     * Laravel's scaffolded users table: no email/password/email_verified_at,
     * UUID primary key to match every other table (mirrors
     * backend/prisma/schema.prisma's User model, kept in stale/ for
     * reference). No password_reset_tokens table either, for the same reason.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('discord_id')->unique();
            $table->string('discord_username');
            $table->string('nickname')->nullable();
            $table->string('avatar_url')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignUuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('sessions');
    }
};
