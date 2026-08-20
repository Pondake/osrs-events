<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * spatie/laravel-permission's Permission, made uuid-keyed like the rest of
 * this schema. See Role for the reasoning.
 *
 * The permission *names* are the same strings the old `user_permissions.
 * permission_key` column held — `canCreateBoards`, `canCreateTiles` — so
 * nothing in the app had to learn new vocabulary.
 */
class Permission extends SpatiePermission
{
    use HasUuids;
}
