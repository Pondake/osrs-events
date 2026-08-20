<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * spatie/laravel-permission's Permission, set up for UUIDs per their own guide
 * (spatie.be/docs/laravel-permission/v6/advanced-usage/uuid). See Role for why
 * `$keyType` and `$incrementing` are declared alongside the trait rather than
 * left to it.
 *
 * The permission *names* are the same strings the old `user_permissions.
 * permission_key` column held — `canCreateBoards`, `canCreateTiles` — so
 * nothing in the app had to learn new vocabulary.
 */
class Permission extends SpatiePermission
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;
}
