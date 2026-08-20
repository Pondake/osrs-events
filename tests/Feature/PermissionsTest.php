<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Roles and permissions, now backed by spatie/laravel-permission.
 *
 * The behaviour these lock down is the behaviour the homegrown system had —
 * the point of the move was the plumbing (Gate integration, caching, a real
 * permissions catalogue), not a change in who can do what.
 */
class PermissionsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return tap(User::factory()->create())->assignRole(
            Role::findOrCreate('ADMIN', 'web'),
        );
    }

    private function player(): User
    {
        return tap(User::factory()->create())->assignRole(
            Role::findOrCreate('PLAYER', 'web'),
        );
    }

    // ------------------------------------------------------------- schema

    /**
     * spatie's stub uses auto-incrementing ids; this schema is uuid-keyed
     * throughout. If the trait were missing, roles would save with no id.
     */
    #[Test]
    public function roles_and_permissions_get_uuid_keys(): void
    {
        $role = Role::findOrCreate('EDITOR', 'web');
        $permission = Permission::findOrCreate('canCreateTiles', 'web');

        $this->assertTrue(Str::isUuid($role->id));
        $this->assertTrue(Str::isUuid($permission->id));
    }

    /** Carried over from the homegrown table; the admin UI displays it. */
    #[Test]
    public function a_role_keeps_its_description(): void
    {
        $role = Role::create(['name' => 'EDITOR', 'guard_name' => 'web', 'description' => 'Edits things']);

        $this->assertSame('Edits things', $role->fresh()->description);
    }

    // -------------------------------------------------------------- roles

    #[Test]
    public function a_role_holder_is_recognised(): void
    {
        $this->assertTrue($this->player()->hasRole('PLAYER'));
        $this->assertFalse($this->player()->hasRole('ADMIN'));
    }

    #[Test]
    public function is_admin_reflects_the_admin_role(): void
    {
        $this->assertTrue($this->admin()->isAdmin());
        $this->assertFalse($this->player()->isAdmin());
    }

    #[Test]
    public function assigning_the_same_role_twice_does_not_duplicate_it(): void
    {
        $role = Role::findOrCreate('PLAYER', 'web');
        $user = User::factory()->create();

        $user->assignRole($role);
        $user->assignRole($role);

        $this->assertCount(1, $user->fresh()->roles);
    }

    // -------------------------------------------------------- permissions

    #[Test]
    public function a_granted_permission_is_held(): void
    {
        $user = $this->player();
        $user->givePermissionTo(Permission::findOrCreate('canCreateBoards', 'web'));

        $this->assertTrue($user->hasPermission('canCreateBoards'));
        $this->assertFalse($user->hasPermission('canCreateTiles'));
    }

    /** The app's own rule, not the package's: ADMIN bypasses every check. */
    #[Test]
    public function an_admin_holds_every_permission_without_being_granted_one(): void
    {
        $admin = $this->admin();

        $this->assertCount(0, $admin->permissions);
        $this->assertTrue($admin->hasPermission('canCreateBoards'));
        $this->assertTrue($admin->hasPermission('canCreateTiles'));
    }

    /**
     * spatie's hasPermissionTo() *throws* PermissionDoesNotExist for a key
     * with no row. Every caller here wants a plain false — a permission that
     * was never seeded is not granted, not an error.
     */
    #[Test]
    public function an_unknown_permission_key_is_false_rather_than_an_exception(): void
    {
        $this->assertFalse($this->player()->hasPermission('noSuchPermissionAnywhere'));
    }

    /** The Gate wiring is the reason for the move — `can()` must work too. */
    #[Test]
    public function permissions_resolve_through_laravels_gate(): void
    {
        $user = $this->player();
        $user->givePermissionTo(Permission::findOrCreate('canCreateBoards', 'web'));

        $this->assertTrue($user->can('canCreateBoards'));
        $this->assertFalse($user->can('canCreateTiles'));
    }

    // ------------------------------------------------------- admin routes

    #[Test]
    public function the_admin_area_is_closed_to_a_plain_player(): void
    {
        $this->actingAs($this->player())->get('/admin')->assertForbidden();
    }

    #[Test]
    public function the_admin_area_opens_for_an_admin(): void
    {
        $this->actingAs($this->admin())->get('/admin')->assertOk();
    }

    #[Test]
    public function an_admin_can_grant_and_revoke_a_role(): void
    {
        $admin = $this->admin();
        $target = $this->player();

        $this->actingAs($admin)->post("/admin/users/{$target->id}/roles", ['role' => 'EDITOR']);
        $this->assertTrue($target->fresh()->hasRole('EDITOR'));

        $role = Role::findByName('EDITOR', 'web');
        $this->actingAs($admin)->delete("/admin/users/{$target->id}/roles/{$role->id}");
        $this->assertFalse($target->fresh()->hasRole('EDITOR'));
    }

    #[Test]
    public function an_admin_can_grant_and_revoke_a_permission(): void
    {
        $admin = $this->admin();
        $target = $this->player();

        $this->actingAs($admin)->post("/admin/users/{$target->id}/permissions", ['permission_key' => 'canCreateBoards']);
        $this->assertTrue($target->fresh()->hasPermission('canCreateBoards'));

        $this->actingAs($admin)->delete("/admin/users/{$target->id}/permissions/canCreateBoards");
        $this->assertFalse($target->fresh()->hasPermission('canCreateBoards'));
    }

    /** Only the two the app defines — an open-ended key must be refused. */
    #[Test]
    public function an_unlisted_permission_key_cannot_be_granted(): void
    {
        $target = $this->player();

        $this->actingAs($this->admin())
            ->post("/admin/users/{$target->id}/permissions", ['permission_key' => 'becomeGod'])
            ->assertSessionHasErrors('permission_key');

        $this->assertCount(0, $target->fresh()->permissions);
    }

    #[Test]
    public function a_player_cannot_hand_themselves_a_role(): void
    {
        $player = $this->player();

        $this->actingAs($player)
            ->post("/admin/users/{$player->id}/roles", ['role' => 'ADMIN'])
            ->assertForbidden();

        $this->assertFalse($player->fresh()->isAdmin());
    }
}
