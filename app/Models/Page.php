<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * An editable public page, stored as a block document. See the migration for
 * why `blocks` is one JSON column.
 */
class Page extends Model
{
    use HasUuids;

    protected $fillable = [
        'slug',
        'title',
        'subtitle',
        'seo_title',
        'seo_description',
        'blocks',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'blocks' => 'array',
            'is_published' => 'boolean',
        ];
    }

    /**
     * Block types the editor offers and the renderer understands.
     *
     * This list is a **deliberate partial mirror** of
     * resources/js/Components/Cms/blocks.js. PHP validates only that a block
     * names a type that exists and is shaped like a block; the per-prop
     * allowlist stays in JS, where the renderer applies it on the way out.
     *
     * Splitting it that way is on purpose. Duplicating the full prop schema
     * in two languages would give two things to keep in step and a false
     * sense that the server-side copy is what makes rendering safe. It isn't:
     * content also reaches the table through seeders and fixtures, so the
     * renderer has to sanitise regardless, which makes a second full schema
     * here redundant rather than defence in depth.
     */
    public const BLOCK_TYPES = [
        'hero',
        'section',
        'features',
        'image',
        'prose',
        'list',
        'faq',
        'links',
        'cta',
        'callout',
        'separator',
    ];

    /** Container types may carry a nested `blocks` array. */
    public const CONTAINER_TYPES = ['section'];

    /**
     * Bounds, mirroring PageRenderer's own. Enforced here too so a malformed
     * document is refused at the point of writing rather than silently
     * truncated at the point of reading.
     */
    public const MAX_BLOCKS = 200;

    public const MAX_DEPTH = 3;

    /**
     * Slugs the CMS must not treat as pages of their own.
     *
     * Two kinds of row end up here, and they arrive for different reasons:
     *
     *   - `home` is a REAL row that backs only part of a hand-written page.
     *     Home.vue reads its hero copy and block region, but the page itself
     *     is a component at `/`.
     *   - the guide slugs and `about` are LEFTOVERS. Those pages were CMS
     *     documents once and are components now (the guides since 2026-08-27,
     *     `about` since 2026-09-07), so their rows back nothing at all. They
     *     stay listed because an environment that ran the seeder before the
     *     change still has them, and listing them retires those rows without
     *     a migration that deletes content somebody may have edited.
     *
     * Both kinds need the same two things from every caller that checks this
     * list: the row must not be offered as an editable page in the CMS
     * inventory, and the `/{page}` catch-all must not serve it — which for a
     * leftover would put stale copy on the same URL as the component, and for
     * `home` would put the same content on a second URL.
     */
    public const PARTIAL_SLUGS = ['home', 'about', 'osrs-snakes-and-ladders', 'osrs-clan-events', 'osrs-event-ideas', 'osrs-bingo', 'osrs-skill-race', 'osrs-drop-race'];

    /**
     * The public URL this page's content appears on.
     *
     * Usually `/{slug}` through the catch-all, but a partial page's row backs
     * a component mounted elsewhere — `home` renders at `/`, and `/home`
     * deliberately 404s. Kept here so the editor's "view live" link and any
     * future sitemap agree on one answer.
     */
    public function publicPath(): string
    {
        return match ($this->slug) {
            'home' => '/',
            // The landing slugs and `about` happen to equal their public
            // path, so the default is already right for them.
            default => '/'.$this->slug,
        };
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * SEO falls back to the page's own title rather than being required —
     * an empty search-result title is worse than a duplicated one.
     */
    public function seoTitle(): string
    {
        return $this->seo_title ?: $this->title;
    }

    public function seoDescription(): ?string
    {
        return $this->seo_description ?: $this->subtitle;
    }
}
