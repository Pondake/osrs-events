<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoardTeam extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['board_id', 'team_id'];

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
