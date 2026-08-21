<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * A reusable starting point for an event — see the create_event_blueprints
 * migration for what it is and why it isn't a Task.
 */
class EventBlueprint extends Model
{
    use HasUuids;

    protected $fillable = ['title', 'type', 'metric', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    /** What the create form's autocomplete offers: active, by title. */
    public function scopeSuggestable(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('title');
    }
}
