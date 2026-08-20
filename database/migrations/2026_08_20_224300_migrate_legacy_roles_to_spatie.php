<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Moves the homegrown roles and permissions into spatie's tables.
 *
 * Written against the query builder, not the models: a data migration that
 * depends on Eloquent depends on whatever those classes look like *today*,
 * and this file has to keep working after they are rewritten to extend
 * spatie's. Raw tables are the only stable thing here.
 *
 * Role ids are **preserved**, not regenerated. Nothing outside `user_roles`
 * references them today, but keeping them means an admin's audit-log entry or
 * a bookmarked id still lines up, and there is no cost to it.
 *
 * Permissions are the interesting half. The old model had no permissions
 * table at all — `user_permissions` stored a bare string key per user, with
 * no catalogue of what keys exist. So the set is derived from two places:
 * the keys actually granted to somebody, plus the two the code checks for by
 * name. Without that second half a permission nobody happens to hold right
 * now would silently vanish from the system.
 */
return new class extends Migration
{
    /**
     * Every permission key the application checks. `User::hasPermission()` is
     * called with these; they must exist as rows or spatie throws
     * PermissionDoesNotExist rather than simply answering "no".
     */
    private const KNOWN_PERMISSIONS = ['canCreateBoards', 'canCreateTiles'];

    public function up(): void
    {
        $guard = config('auth.defaults.guard', 'web');
        $now = now();

        if (Schema::hasTable('legacy_roles')) {
            foreach (DB::table('legacy_roles')->get() as $role) {
                DB::table('roles')->insertOrIgnore([
                    'id' => $role->id,
                    'name' => $role->name,
                    'guard_name' => $guard,
                    'description' => $role->description ?: null,
                    'created_at' => $role->created_at ?? $now,
                    'updated_at' => $role->updated_at ?? $now,
                ]);
            }
        }

        // Granted keys first, then the ones the code names, so a permission
        // nobody currently holds still exists to be granted later.
        $keys = Schema::hasTable('user_permissions')
            ? DB::table('user_permissions')->distinct()->pluck('permission_key')->all()
            : [];

        foreach (array_unique([...$keys, ...self::KNOWN_PERMISSIONS]) as $key) {
            DB::table('permissions')->insertOrIgnore([
                'id' => (string) Str::uuid(),
                'name' => $key,
                'guard_name' => $guard,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $permissionIds = DB::table('permissions')->pluck('id', 'name');

        if (Schema::hasTable('user_roles')) {
            foreach (DB::table('user_roles')->get() as $assignment) {
                DB::table('model_has_roles')->insertOrIgnore([
                    'role_id' => $assignment->role_id,
                    'model_type' => 'App\Models\User',
                    'model_uuid' => $assignment->user_id,
                ]);
            }
        }

        if (Schema::hasTable('user_permissions')) {
            foreach (DB::table('user_permissions')->get() as $grant) {
                if (! isset($permissionIds[$grant->permission_key])) {
                    continue;
                }

                DB::table('model_has_permissions')->insertOrIgnore([
                    'permission_id' => $permissionIds[$grant->permission_key],
                    'model_type' => 'App\Models\User',
                    'model_uuid' => $grant->user_id,
                ]);
            }
        }

        Schema::dropIfExists('user_permissions');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('legacy_roles');
    }

    /**
     * Rebuilds the old tables and copies the assignments back, so the step is
     * genuinely reversible rather than reversible-in-name. Role descriptions
     * survive because the new roles table kept that column.
     */
    public function down(): void
    {
        Schema::create('legacy_roles', function ($table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('user_roles', function ($table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('role_id');
            $table->timestamp('created_at')->nullable();
            $table->unique(['user_id', 'role_id']);
        });

        Schema::create('user_permissions', function ($table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('permission_key');
            $table->timestamp('created_at')->nullable();
            $table->unique(['user_id', 'permission_key']);
        });

        foreach (DB::table('roles')->get() as $role) {
            DB::table('legacy_roles')->insert([
                'id' => $role->id,
                'name' => $role->name,
                'description' => $role->description ?? null,
                'created_at' => $role->created_at,
                'updated_at' => $role->updated_at,
            ]);
        }

        foreach (DB::table('model_has_roles')->get() as $assignment) {
            DB::table('user_roles')->insert([
                'id' => (string) Str::uuid(),
                'user_id' => $assignment->model_uuid,
                'role_id' => $assignment->role_id,
                'created_at' => now(),
            ]);
        }

        $permissionNames = DB::table('permissions')->pluck('name', 'id');

        foreach (DB::table('model_has_permissions')->get() as $grant) {
            DB::table('user_permissions')->insert([
                'id' => (string) Str::uuid(),
                'user_id' => $grant->model_uuid,
                'permission_key' => $permissionNames[$grant->permission_id],
                'created_at' => now(),
            ]);
        }

        // Deliberately leaves the table named `legacy_roles`. Rolling back
        // runs this first, while spatie's `roles` table still exists — and
        // renaming onto a live table would fail. The rename back is
        // 2026_08_20_224100's job, which runs after spatie's own down() has
        // dropped it.
    }
};
