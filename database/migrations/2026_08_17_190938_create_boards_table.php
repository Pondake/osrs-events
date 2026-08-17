<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mirrors backend/prisma/schema.prisma's Board model, trimmed to the
     * columns the prototype's slice page actually renders — access grants,
     * invites, and author pivots are out of scope here (see the branch's
     * evaluation notes on the access-control matrix).
     *
     * Prisma uses a `@default(uuid())` String id on every model uniformly.
     * Eloquent's usual convention is an auto-increment bigint id — this table
     * uses `uuid()->primary()` instead, deliberately matching Prisma, to
     * prove Eloquent handles UUID PKs cleanly (it does, via HasUuids). The
     * default `users` table this project scaffolded still uses a bigint id,
     * which is the real decision point for a full migration: either bring
     * every table to UUIDs to match Prisma 1:1, or accept a mixed-key schema.
     */
    public function up(): void
    {
        Schema::create('boards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('size', ['SIZE_5X5', 'SIZE_7X7', 'SIZE_9X9'])->default('SIZE_7X7');
            $table->enum('mode', ['SOLO', 'TEAM'])->default('SOLO');
            $table->enum('access_mode', ['OPEN', 'GUILD', 'INVITE'])->default('OPEN');
            $table->string('required_guild_id')->nullable();
            $table->boolean('is_listed')->default(true);
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->unsignedInteger('dice_roll_limit')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boards');
    }
};
