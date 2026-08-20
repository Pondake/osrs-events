<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Ported from UsersService::findAll() + the role/permission mutations
 * scattered across users.resolver.ts / permissions.service.ts. The only
 * granular permission keys the app defines are canCreateBoards and
 * canCreateTiles (see PermissionKey enum in the old backend) — no UI
 * validates against an open-ended key here for the same reason.
 */
class UserController extends Controller
{
    private const PERMISSION_KEYS = ['canCreateBoards', 'canCreateTiles'];

    public function index(Request $request): Response
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $users = User::with(['roles:id,name', 'permissions:id,name'])
            ->when(
                $request->string('search')->isNotEmpty(),
                // All three, not just discord_username: email registration
                // means an account can have no Discord name at all, and those
                // users were unfindable here.
                fn ($q) => $q->where(function ($sub) use ($request) {
                    $term = '%'.$request->string('search').'%';
                    $sub->where('discord_username', 'like', $term)
                        ->orWhere('nickname', 'like', $term)
                        ->orWhere('email', 'like', $term);
                }),
            )
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Admin/Users', [
            'users' => $users,
            'search' => $request->string('search')->toString(),
            'permissionKeys' => self::PERMISSION_KEYS,
        ]);
    }

    public function assignRole(Request $request, User $user): RedirectResponse
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $data = $request->validate(['role' => ['required', 'string']]);

        DB::transaction(function () use ($user, $data) {
            // firstOrCreate on name alone would miss the guard, and spatie
            // keys roles on (name, guard_name) — a role created without one
            // is invisible to every check.
            $role = Role::findOrCreate($data['role'], config('auth.defaults.guard'));
            $user->assignRole($role);
            AuditLog::record('user.role_granted', $user, ['role' => $role->name]);
        });

        return back()->with('board-save', trans('admin.role_assigned', ['role' => $data['role']]));
    }

    public function removeRole(User $user, Role $role): RedirectResponse
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $user->removeRole($role);
        AuditLog::record('user.role_revoked', $user, ['role' => $role->name]);

        return back()->with('board-save', trans('admin.role_removed', ['role' => $role->name]));
    }

    public function grantPermission(Request $request, User $user): RedirectResponse
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $data = $request->validate(['permission_key' => ['required', 'in:'.implode(',', self::PERMISSION_KEYS)]]);

        // findOrCreate, not find: PERMISSION_KEYS is the app's catalogue,
        // and a key that has never been granted to anyone has no row yet.
        $user->givePermissionTo(
            Permission::findOrCreate($data['permission_key'], config('auth.defaults.guard')),
        );
        AuditLog::record('user.permission_granted', $user, ['permission' => $data['permission_key']]);

        return back()->with('board-save', trans('admin.permission_granted'));
    }

    public function revokePermission(User $user, string $permissionKey): RedirectResponse
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $user->permissions()->detach(
            Permission::where('name', $permissionKey)->value('id'),
        );
        $user->forgetCachedPermissions();
        AuditLog::record('user.permission_revoked', $user, ['permission' => $permissionKey]);

        return back()->with('board-save', trans('admin.permission_revoked'));
    }

    /**
     * Ported from UsersService::deleteUser() — the one mutation from the old
     * backend that never made it across (its i18n strings did, which is how
     * the gap surfaced). Same two rules: an admin can't be deleted until the
     * ADMIN role is removed first, and — new here — you can't delete
     * yourself, which the old GraphQL version never guarded against.
     */
    public function destroy(User $user): RedirectResponse
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        if ($user->id === Auth::id()) {
            return back()->with('board-save-error', trans('admin.cannot_delete_self'));
        }

        if ($user->isAdmin()) {
            return back()->with('board-save-error', trans('admin.cannot_delete_admin'));
        }

        $name = $user->displayName();

        // Logged BEFORE the delete: record() reads the target's label off the
        // live model, and this is the one action where that label is the only
        // thing that survives. target_id keeps pointing at a row that no
        // longer exists (it carries no foreign key — targets are polymorphic),
        // so target_label is what makes the entry readable afterwards.
        AuditLog::record('user.deleted', $user, [
            'roles' => $user->roles->pluck('name')->all(),
        ]);

        $user->delete();

        return back()->with('board-save', trans('admin.user_deleted', ['name' => $name]));
    }
}
