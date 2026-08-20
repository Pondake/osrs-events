<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * spatie/laravel-permission's Role, with the two things this schema needs.
 *
 * **HasUuids**, because spatie's own model assumes an auto-incrementing id
 * and this database is uuid-keyed throughout (CLAUDE.md). Without it, created
 * roles get no id at all.
 *
 * **`description`**, carried over from the homegrown roles table because the
 * admin roles UI displays it. spatie's model has no such column, so it has to
 * be added to `$fillable` explicitly — its parent sets that list, and a
 * merge is not automatic.
 *
 * @property string|null $description
 */
class Role extends SpatieRole
{
    use HasUuids;

    /**
     * Spatie's Role declares `protected $guarded = []`, so everything is
     * mass-assignable already; this exists to be explicit about the extra
     * column rather than to restrict anything.
     */
    protected $fillable = ['name', 'guard_name', 'description'];
}
