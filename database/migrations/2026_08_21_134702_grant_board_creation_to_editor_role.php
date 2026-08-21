<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

/**
 * Attaches `canCreateBoards` to EDITOR.
 *
 * Until now no role carried a single permission. The whole set was granted
 * per user, inherited from the old `user_permissions` table, which stored a
 * bare key against a user id and had no notion of roles at all. Roles came
 * across in the spatie migration with their names and descriptions and
 * nothing else — so handing somebody EDITOR granted them exactly nothing,
 * and the only account that could do anything was ADMIN, which bypasses
 * every check in User::hasPermission() rather than holding permissions.
 *
 * That made EDITOR a label rather than a role. This is the smallest step
 * that makes it mean something: somebody has to be able to create the
 * boards, and it cannot only be the admin.
 *
 * Deliberately not granting `canCreateTiles` here as well. It is arguably
 * just as much an editor's job, but it is a separate decision and nobody
 * has asked for it — see docs/backlog.md for the larger question of who
 * should be able to create boards at all.
 */
return new class extends Migration
{
    private const ROLE = 'EDITOR';

    private const PERMISSION = 'canCreateBoards';

    public function up(): void
    {
        // findOrCreate, not a bare firstOrCreate(['name' => …]): that skips
        // guard_name, and a row without one matches no check anywhere.
        $role = Role::findOrCreate(self::ROLE, 'web');
        $permission = Permission::findOrCreate(self::PERMISSION, 'web');

        $role->givePermissionTo($permission);

        // Spatie serves every check from a cached map, so without this the
        // grant is invisible until the cache happens to expire.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $role = Role::where('name', self::ROLE)->first();

        $role?->revokePermissionTo(self::PERMISSION);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
