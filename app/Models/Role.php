<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * spatie/laravel-permission's Role, set up for UUIDs per their own guide
 * (spatie.be/docs/laravel-permission/v6/advanced-usage/uuid).
 *
 * `HasUuids` alone is not quite enough. It overrides `getKeyType()` and
 * `getIncrementing()`, so Eloquent itself behaves — but the underlying
 * `$keyType` / `$incrementing` **properties** stay `'int'` and `true`, and
 * anything reading those directly rather than through the accessors gets the
 * wrong answer about a uuid key. Declaring both is what the guide asks for,
 * and it costs nothing to be unambiguous about it.
 *
 * No `$fillable` here on purpose. spatie's constructor does
 * `$this->guarded[] = $this->primaryKey`, so the id is already protected from
 * mass assignment while everything else — including the `description` column
 * this schema adds — stays assignable. Declaring a `$fillable` list instead
 * would take precedence over that and silently drop any attribute the package
 * passes that the list did not anticipate.
 *
 * @property string|null $description
 */
class Role extends SpatieRole
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;
}
