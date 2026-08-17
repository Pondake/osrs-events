<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Board extends Model
{
    use HasUuids;

    protected $fillable = ['title', 'description', 'size', 'mode', 'access_mode', 'is_listed'];

    protected $casts = [
        'is_listed' => 'boolean',
    ];

    public function tiles(): HasMany
    {
        return $this->hasMany(Tile::class)->orderBy('position');
    }

    public function playerBoards(): HasMany
    {
        return $this->hasMany(PlayerBoard::class);
    }
}
