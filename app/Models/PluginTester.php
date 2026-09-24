<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A tester's restart point for the plugin test set, see PluginTestReport. */
class PluginTester extends Model
{
    use HasUuids;

    protected $fillable = ['user_id', 'started_at'];

    protected $casts = ['started_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
