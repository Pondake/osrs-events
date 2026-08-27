<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasUuids;

    /**
     * Soft, not hard. Every tile/bingo square that used this task points at
     * it via a `nullOnDelete` foreign key — which only fires on a real SQL
     * DELETE. Soft-deleting keeps that pointer intact while the task is
     * "gone", which is what lets an admin undo a delete by simply restoring
     * the row: no tile needs re-linking, because none was ever severed.
     */
    use SoftDeletes;

    protected $fillable = ['title', 'icon_url', 'description', 'wiki_page_id', 'wiki_url', 'wiki_synced_at'];

    /** Only set on tasks the OSRS Wiki picker created — see WikiController. */
    protected $casts = ['wiki_page_id' => 'integer', 'wiki_synced_at' => 'datetime'];

    /**
     * How long a wiki-sourced task is trusted before it is re-read.
     *
     * A week: wiki pages do get renamed and re-illustrated, but not on a
     * timescale where a stale title costs anybody anything, and every
     * refresh is a request against a volunteer-run server.
     */
    public const WIKI_TTL_DAYS = 7;

    /**
     * Whether this row still speaks for the wiki page behind it.
     *
     * A hand-written task is never stale — it has no upstream to be stale
     * against, which is why the null case answers false rather than true.
     */
    public function wikiCacheIsStale(): bool
    {
        if ($this->wiki_page_id === null) {
            return false;
        }

        return $this->wiki_synced_at === null
            || $this->wiki_synced_at->addDays(self::WIKI_TTL_DAYS)->isPast();
    }

    public function tiles(): HasMany
    {
        return $this->hasMany(Tile::class);
    }
}
