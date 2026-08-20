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
        'prose',
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
