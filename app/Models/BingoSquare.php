<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One square on a bingo card. Row-major, zero-based position, matching
 * `tiles.position` so both grids read the same way.
 */
class BingoSquare extends Model
{
    use HasUuids;

    protected $fillable = ['bingo_card_id', 'position', 'task_id', 'title_override'];

    protected $casts = ['position' => 'integer'];

    public function card(): BelongsTo
    {
        return $this->belongsTo(BingoCard::class, 'bingo_card_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function completions(): HasMany
    {
        return $this->hasMany(BingoCompletion::class);
    }

    /**
     * What to show on the square. An override wins over the task's title so
     * an event can reword one square without editing a shared Task.
     */
    public function label(): ?string
    {
        return $this->title_override ?: $this->task?->title;
    }
}
