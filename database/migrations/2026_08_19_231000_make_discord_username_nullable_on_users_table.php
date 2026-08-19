<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Follow-up to 2026_08_19_230500_add_email_auth_to_users_table: that
     * migration made discord_id nullable for the new email/password path
     * but missed discord_username, which was still NOT NULL — caught live
     * when RegisteredUserController::store() threw a NOT NULL constraint
     * violation on its very first real registration attempt.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('discord_username')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('discord_username')->nullable(false)->change();
        });
    }
};
