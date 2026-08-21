<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where a task came from, when it came from the OSRS Wiki.
 *
 * Both nullable: a hand-written task ("Kill 50 cows") has no wiki page, and
 * always will not. These only fill in for tasks the wiki picker created.
 *
 * The page id is the identity, not the title — a wiki page can be renamed,
 * and keying on the title would fork one task into two the day it is.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Unique so firstOrCreate() cannot race two identical tasks into
            // existence when two hosts pick the same page at the same moment.
            // Nullable + unique is fine on both engines this runs on:
            // Postgres and SQLite both allow many NULLs in a unique index.
            $table->unsignedBigInteger('wiki_page_id')->nullable()->unique();
            $table->string('wiki_url', 2048)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['wiki_page_id', 'wiki_url']);
        });
    }
};
