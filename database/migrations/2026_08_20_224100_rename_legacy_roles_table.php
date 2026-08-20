<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Gets the homegrown `roles` table out of the way.
 *
 * spatie/laravel-permission wants a table of that exact name, and the
 * existing one has live rows in it — so the package migration would fail
 * outright on "table roles already exists". Renaming rather than dropping
 * keeps the data available for the copy two migrations later; the drop
 * happens there, once its contents are safely in the new tables.
 *
 * `user_roles` and `user_permissions` don't collide with anything and are
 * left alone until that same step.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('roles') && ! Schema::hasTable('legacy_roles')) {
            Schema::rename('roles', 'legacy_roles');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('legacy_roles') && ! Schema::hasTable('roles')) {
            Schema::rename('legacy_roles', 'roles');
        }
    }
};
