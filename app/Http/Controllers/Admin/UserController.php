<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPermission;
use App\Models\UserRole;
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

        $users = User::with(['userRoles.role', 'userPermissions'])
            ->when(
                $request->string('search')->isNotEmpty(),
                fn ($q) => $q->where('discord_username', 'like', '%'.$request->string('search').'%'),
            )
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Admin/Users/Index', [
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
            $role = Role::firstOrCreate(['name' => $data['role']]);
            UserRole::firstOrCreate(['user_id' => $user->id, 'role_id' => $role->id]);
        });

        return back()->with('board-save', 'Role assigned.');
    }

    public function removeRole(User $user, Role $role): RedirectResponse
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        UserRole::where(['user_id' => $user->id, 'role_id' => $role->id])->delete();

        return back()->with('board-save', 'Role removed.');
    }

    public function grantPermission(Request $request, User $user): RedirectResponse
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $data = $request->validate(['permission_key' => ['required', 'in:'.implode(',', self::PERMISSION_KEYS)]]);

        UserPermission::firstOrCreate(['user_id' => $user->id, 'permission_key' => $data['permission_key']]);

        return back()->with('board-save', 'Permission granted.');
    }

    public function revokePermission(User $user, string $permissionKey): RedirectResponse
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        UserPermission::where(['user_id' => $user->id, 'permission_key' => $permissionKey])->delete();

        return back()->with('board-save', 'Permission revoked.');
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
        $user->delete();

        return back()->with('board-save', trans('admin.user_deleted', ['name' => $name]));
    }
}
