<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "This square only counts from N."
 *
 * Tempoross drops a Soaked page in stacks of varying size and a clan had
 * agreed only the 25 stack counted; nothing could express that, so every
 * single page claimed the square. The plugin has always reported `quantity`
 * on a completion — this is the bar it gets measured against.
 *
 * Not nullable: 1 already means "any amount counts", and a second spelling
 * of that (null) is a branch every reader and every query has to remember.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bingo_squares', function (Blueprint $table) {
            $table->unsignedInteger('min_quantity')->default(1)->after('points');
        });

        Schema::table('tiles', function (Blueprint $table) {
            $table->unsignedInteger('min_quantity')->default(1)->after('title_override');
        });
    }

    public function down(): void
    {
        Schema::table('bingo_squares', fn (Blueprint $table) => $table->dropColumn('min_quantity'));
        Schema::table('tiles', fn (Blueprint $table) => $table->dropColumn('min_quantity'));
    }
};
