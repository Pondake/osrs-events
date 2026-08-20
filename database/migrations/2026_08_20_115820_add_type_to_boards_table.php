<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What kind of event this is.
     *
     * Snakes & Ladders was the whole product, so a board WAS the event and
     * needed no type. Per docs/ROADMAP.md phase 5 it becomes one type among
     * several (Bingo next, then the phase 7 list), which makes the type a
     * property of every existing row rather than a new concept bolted on.
     *
     * A string with an app-level allowlist rather than a database enum:
     * adding a type would otherwise need a migration that rewrites the column
     * on every engine, and the set is expected to grow. The allowlist lives
     * on the model (Board::EVENT_TYPES) where the labels and icons are too.
     *
     * Defaulted, not nullable — every existing row genuinely IS a snakes and
     * ladders event, and a nullable column would invite "no type" as a state
     * that has to be handled everywhere forever.
     */
    public function up(): void
    {
        Schema::table('boards', function (Blueprint $table) {
            $table->string('type')->default('SNAKES_LADDERS')->after('title')->index();
        });
    }

    public function down(): void
    {
        Schema::table('boards', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
