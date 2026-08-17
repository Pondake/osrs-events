<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'icon_url', 'guild_id', 'guild_name'];
}
