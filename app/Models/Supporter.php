<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Somebody thanked on /supporters. Published only with consent and while visible.
 */
#[Fillable(['name', 'roles', 'link', 'sort_order', 'consented_at', 'is_visible'])]
class Supporter extends Model
{
    use HasUuids;

    /** In the order the public page groups them. */
    public const ROLES = ['tester', 'ideas', 'donor'];

    protected $casts = [
        'roles' => 'array',
        'sort_order' => 'integer',
        'consented_at' => 'datetime',
        'is_visible' => 'boolean',
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('consented_at')->where('is_visible', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
