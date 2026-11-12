<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BingoCompletion;
use App\Models\BoardAuthor;
use App\Models\BoardTeam;
use App\Models\CompletedTile;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\OsrsAccount;
use App\Models\Permission;
use App\Models\PluginToken;
use App\Models\Role;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\AccountDeletionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

    /**
     * One account, everything attached to it. Admin-only and read-only: the
     * role and permission controls stay on the list.
     */
    public function show(User $user): Response
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $user->load(['roles:id,name', 'permissions:id,name', 'osrsAccounts', 'userGuilds']);

        return Inertia::render('Admin/UserShow', [
            'profile' => [
                'id' => $user->id,
                'name' => $user->displayName(),
                'discordUsername' => $user->discord_username,
                'email' => $user->email,
                'avatarUrl' => $user->avatar_url,
                'createdAt' => $user->created_at,
                'onboardedAt' => $user->onboarding_completed_at,
                'roles' => $user->roles->pluck('name'),
                'access' => $this->accessFor($user),
                'isSelf' => $user->id === Auth::id(),
            ],
            'characters' => $user->osrsAccounts->map(fn (OsrsAccount $account) => [
                'username' => $account->username,
                'isMain' => $account->isMain(),
                'verifiedAt' => $account->osrs_verified_at,
                'provenAt' => $account->osrs_proven_at,
            ]),
            'connections' => [
                'discord' => $user->discord_id !== null,
                'password' => $user->password !== null,
                'guilds' => $user->userGuilds->pluck('guild_name')->filter()->sort()->values(),
                'pushDevices' => $user->pushSubscriptions()->whereNull('expired_at')->count(),
                'plugin' => PluginToken::where('user_id', $user->id)->first(['last_used_at', 'last_plugin_version']),
            ],
            'events' => $this->eventsFor($user),
            'teams' => $user->teamMembers()->with('team:id,name')->get()
                ->filter(fn (TeamMember $member) => $member->team)
                ->map(fn (TeamMember $member) => [
                    'id' => $member->team->id,
                    'name' => $member->team->name,
                    'role' => $member->role,
                ])
                ->values(),
            'claims' => $this->claimsBy($user),
            'auditRetentionDays' => AuditLog::retentionDays(),
            'activity' => AuditLog::query()
                ->where('actor_id', $user->id)
                ->orWhere(fn ($q) => $q->where('target_type', 'User')->where('target_id', $user->id))
                ->orderByDesc('created_at')
                ->limit(30)
                ->get(['id', 'actor_id', 'action', 'actor_label', 'target_label', 'created_at']),
        ]);
    }

    /**
     * Every event the account is tied to, one row each: hosting, playing solo,
     * or playing through a team. Deleted events included — this page is
     * where you find out what somebody was part of.
     */
    private function eventsFor(User $user): Collection
    {
        $links = collect();

        foreach (BoardAuthor::where('user_id', $user->id)->get() as $author) {
            $links->push([$author->event_id, $author->is_owner ? 'owner' : 'cohost', null]);
        }

        foreach (EventParticipant::where('user_id', $user->id)->pluck('event_id') as $eventId) {
            $links->push([$eventId, 'participant', null]);
        }

        $teamIds = $user->teamMembers()->pluck('team_id');
        foreach (BoardTeam::whereIn('team_id', $teamIds)->with('team:id,name')->get() as $entry) {
            $links->push([$entry->event_id, 'team', $entry->team?->name]);
        }

        $events = Event::withTrashed()
            ->whereIn('id', $links->pluck(0)->unique())
            ->get(['id', 'title', 'type', 'mode', 'start_date', 'end_date', 'paused_at', 'closed_at', 'deleted_at'])
            ->keyBy('id');

        return $links->groupBy(0)
            ->map(function (Collection $rows, string $eventId) use ($events) {
                $event = $events->get($eventId);

                if (! $event) {
                    return null;
                }

                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'type' => $event->type,
                    'status' => match (true) {
                        $event->trashed() => 'deleted',
                        $event->isPaused() => 'paused',
                        $event->isEnded() => 'ended',
                        $event->start_date?->isFuture() ?? false => 'upcoming',
                        default => 'live',
                    },
                    'startDate' => $event->start_date,
                    'links' => $rows->map(fn ($row) => ['kind' => $row[1], 'team' => $row[2]])->values(),
                ];
            })
            ->filter()
            ->sortByDesc('startDate')
            ->values();
    }

    /**
     * The claims this account marked itself, newest first, across both event
     * types that have them.
     */
    private function claimsBy(User $user): Collection
    {
        $tiles = CompletedTile::where('marked_by', $user->id)
            ->with(['tile.task:id,title', 'playerBoard.board.event' => fn ($q) => $q->withTrashed()->select('id', 'title', 'deleted_at')])
            ->latest('completed_at')
            ->limit(25)
            ->get()
            ->map(fn (CompletedTile $claim) => [
                'id' => $claim->id,
                'label' => $claim->tile?->title_override ?: $claim->tile?->task?->title,
                'event' => $this->claimEvent($claim->playerBoard?->board?->event),
                'status' => $claim->status,
                'via' => $claim->completed_via,
                'at' => $claim->completed_at,
            ]);

        $squares = BingoCompletion::where('marked_by', $user->id)
            ->with(['square.task:id,title', 'square.card.event' => fn ($q) => $q->withTrashed()->select('id', 'title', 'deleted_at')])
            ->latest('claimed_at')
            ->limit(25)
            ->get()
            ->map(fn (BingoCompletion $claim) => [
                'id' => $claim->id,
                'label' => $claim->square?->label(),
                'event' => $this->claimEvent($claim->square?->card?->event),
                'status' => $claim->status,
                'via' => $claim->completed_via,
                'at' => $claim->claimed_at,
            ]);

        return $tiles->concat($squares)->sortByDesc('at')->take(25)->values();
    }

    private function claimEvent(?Event $event): ?array
    {
        return $event ? ['id' => $event->id, 'title' => $event->title, 'deleted' => $event->trashed()] : null;
    }

    /**
     * Where each permission in the catalogue comes from, if anywhere. The
     * account's own grants are only part of it: a role can carry one, and
     * ADMIN passes every check (see User::hasPermission()).
     */
    private function accessFor(User $user): array
    {
        $roles = $user->roles()->with('permissions:id,name')->get();

        return array_map(function (string $key) use ($user, $roles) {
            $viaRole = $roles->first(fn (Role $role) => $role->permissions->contains('name', $key));
            $source = match (true) {
                $user->permissions->contains('name', $key) => 'direct',
                $viaRole !== null => 'role',
                $user->isAdmin() => 'admin',
                default => null,
            };

            return ['key' => $key, 'source' => $source, 'role' => $source === 'role' ? $viaRole->name : null];
        }, self::PERMISSION_KEYS);
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
    public function destroy(User $user, AccountDeletionService $deletion): RedirectResponse
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

        // Through the service rather than a bare delete: finished events keep
        // their leaderboards, and the invite foreign key that used to make this
        // fail outright is cleared first. See AccountDeletionService.
        $deletion->deleteAsAdmin($user);

        return back()->with('board-save', trans('admin.user_deleted', ['name' => $name]));
    }
}
