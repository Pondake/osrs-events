<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('board_accesses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('board_id')->constrained('boards')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('invite_id')->nullable()->constrained('board_invites')->nullOnDelete();
            $table->enum('access_mode', ['OPEN', 'GUILD', 'INVITE']);
            $table->timestamp('joined_at')->useCurrent();

            $table->unique(['board_id', 'user_id']);
        });

        // Cached on every Discord login — see
        // app/Http/Controllers/Auth/DiscordController.php's guild sync,
        // ported from the old NestJS UsersService::syncGuilds() (delete-all +
        // re-insert in a transaction, same behavior kept here).
        Schema::create('user_guilds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('guild_id');
            $table->string('guild_name');
            $table->string('guild_icon')->nullable();
            $table->timestamp('synced_at')->useCurrent();

            $table->unique(['user_id', 'guild_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_guilds');
        Schema::dropIfExists('board_accesses');
    }
};
