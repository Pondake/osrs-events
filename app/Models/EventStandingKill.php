<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** One boss kill the plugin reported for a drop race standing, keyed by its kill count. */
class EventStandingKill extends Model
{
    use HasUuids;

    protected $fillable = ['event_standing_id', 'kill_count', 'plugin_completion_id'];

    protected $casts = ['kill_count' => 'integer'];
}
