<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasUuids;

    protected $fillable = ['title', 'icon_url', 'description'];

    public function tiles(): HasMany
    {
        return $this->hasMany(Tile::class);
    }
}
