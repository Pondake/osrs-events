<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds an email/password login path alongside Discord OAuth. discord_id
     * drops its NOT NULL (an email-registered user has none); email is the
     * new unique identity for that path. Both stay nullable — a user has
     * exactly one or the other, never neither (enforced at the application
     * layer: registration requires email+password, Discord callback always
     * sets discord_id).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('discord_id')->nullable()->change();
            $table->string('email')->nullable()->unique()->after('discord_id');
            $table->string('password')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['email', 'password']);
            $table->string('discord_id')->nullable(false)->change();
        });
    }
};
