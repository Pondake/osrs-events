<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An admin's own icon for a boss, overriding the committed pet sprite.
 *
 * Most bosses need no row here: 61 of the 71 have a pet in
 * public/images/osrs/bosses/, written by scripts/extract-osrs-icons.mjs. This
 * table is for the rest and for disagreements — Aggy (Mad Angel) and Bran (The
 * Royal Titans) have a pet on the wiki that the icon package has not shipped,
 * and filling those in should not need a deploy for what is, in the end, a
 * picture.
 *
 * Sparse on purpose: a row exists only where somebody set one, so the absence
 * of a row means "use the committed sprite", not "no icon".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boss_icons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // The Wise Old Man metric name (Event::BOSS_METRICS), which is what
            // the app stores on an event and what the committed PNG is named
            // after — so there is no third vocabulary to keep in step.
            $table->string('metric')->unique();
            // A URL, not a file: the OSRS Wiki is where these come from, and
            // the app already fetches wiki images for task and team icons.
            // text, because wiki image URLs are long and get longer.
            $table->text('icon_url');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boss_icons');
    }
};
