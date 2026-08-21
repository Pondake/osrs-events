<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Named, reusable starting points for an event — "Skill of the Week",
 * "Boss of the Month", "Clan Bingo Night". Creating an event is mostly a
 * clan doing the same handful of formats over and over, and typing the
 * title out each time is where that shows: the name drifts, the type gets
 * picked wrong, and nothing links this month's run to the last one.
 *
 * Deliberately not a Task: a Task is one thing to do on a tile, a blueprint
 * is the shape of a whole event. They autocomplete in different forms and
 * carry different fields.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_blueprints', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');

            // Nullable throughout: a blueprint is allowed to be nothing but
            // a title. Picking one fills in whichever of these it carries
            // and leaves the rest of the form alone, so a title-only row is
            // exactly the plain autocomplete entry it looks like.
            $table->string('type')->nullable();
            $table->string('metric')->nullable();
            $table->text('description')->nullable();

            // Retiring a format should not delete the rows that reference
            // its name in the audit log, so it hides instead.
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // The list is filtered by is_active and sorted by title on every
            // keystroke of the create form's autocomplete.
            $table->index(['is_active', 'title']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_blueprints');
    }
};
