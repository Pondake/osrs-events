<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The people thanked on /supporters.
 *
 * Kept by hand in admin, never filled from accounts or donations: a name is
 * only published once `consented_at` is set, and that is a box the admin ticks
 * after the person said yes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supporters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 80);
            // Subset of Supporter::ROLES.
            $table->json('roles');
            // Free text shown beside the name: an RSN, a Discord name, or a URL.
            $table->string('link')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamp('consented_at')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supporters');
    }
};
