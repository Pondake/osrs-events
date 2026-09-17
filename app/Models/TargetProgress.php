<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One counted report toward a "do this N times" square or tile.
 *
 * The running total is these rows, counted — see the migration for why it is
 * stored rather than derived from plugin_completions.
 */
class TargetProgress extends Model
{
    use HasUuids;

    protected $table = 'target_progress';

    protected $fillable = ['kind', 'target_id', 'competitor_key', 'dedupe_key', 'plugin_completion_id'];

    public function pluginCompletion(): BelongsTo
    {
        return $this->belongsTo(PluginCompletion::class);
    }
}
