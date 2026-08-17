<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPermission extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['user_id', 'permission_key'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
