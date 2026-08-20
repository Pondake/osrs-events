<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Editable public pages, stored as a block document per page.
     *
     * `blocks` is one JSON column rather than a `page_blocks` table with a
     * row per block and a position integer. A block list is only ever read
     * and written whole — the renderer takes the entire array, and the editor
     * saves the entire array — so rows would buy ordering queries and
     * per-block foreign keys that nothing needs, at the cost of a reorder
     * becoming an UPDATE across every row rather than one write.
     *
     * The shape matches what resources/js/Components/Cms/PageRenderer.vue
     * already accepts, deliberately: the renderer was built and proven first
     * so this column could be designed around a document that is known to
     * render rather than one guessed at.
     *
     * Nothing here is trusted at render time. The renderer's allowlist
     * (Cms/blocks.js) is the security boundary and sanitises on the way out,
     * because content can also reach this column through a seeder, a fixture
     * or a future import — not only through the validated admin form.
     */
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // The public path, without a leading slash: 'about', 'privacy'.
            // Unique because it IS the route key.
            $table->string('slug')->unique();

            $table->string('title');
            $table->string('subtitle')->nullable();

            // Kept apart from title/subtitle: what a page calls itself in its
            // own heading and what it should say in a search result are
            // different jobs, and collapsing them makes one of the two worse.
            $table->string('seo_title')->nullable();
            $table->string('seo_description')->nullable();

            $table->json('blocks');

            // Unpublished pages 404 for visitors but stay editable in admin,
            // so a rewrite can be drafted without taking the live page down.
            $table->boolean('is_published')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
