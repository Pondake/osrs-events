<?php

namespace App\Console\Commands;

use App\Models\BoardAuthor;
use App\Models\Event;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Puts one account into the shape of a persona, so a single browser session
 * can be walked through the app as six different people.
 *
 * Testing permissions properly means being a guest, a player, a player who
 * may create, a co-host, an owner and an admin. Doing that with six real
 * logins means six passwords typed into a form on every pass — and it cannot
 * be automated by anything that should not be handling passwords. The app's
 * own rule makes it worse in the useful way: roles are handed out by an
 * admin, so a second account cannot elevate itself into the seat you need.
 *
 * So: one seat, re-roled between passes. `restore` puts back exactly what was
 * there before the first switch, read from a file written at that moment.
 *
 * Local only. This grants and revokes roles without asking anybody.
 */
class DevPersona extends Command
{
    protected $signature = 'dev:persona
        {persona : guest|player|creator|cohost|owner|admin|newcomer|no-email|restore|show}
        {--as=admin : discord_username of the account the browser is signed in as}
        {--event= : title fragment of the event to co-host, for the cohost persona}';

    protected $description = 'Reshape one account into a test persona (local only)';

    /** Where the pre-test state is kept, so `restore` is exact rather than assumed. */
    private const SNAPSHOT = 'dev-persona-snapshot.json';

    public function handle(): int
    {
        if (! app()->environment('local')) {
            $this->error('dev:persona only runs locally — it hands out roles without asking.');

            return self::FAILURE;
        }

        $user = User::where('discord_username', $this->option('as'))->first();

        if ($user === null) {
            $this->error("No account with discord_username \"{$this->option('as')}\".");

            return self::FAILURE;
        }

        $persona = $this->argument('persona');

        if ($persona === 'show') {
            return $this->report($user, 'current');
        }

        $this->snapshot($user);

        if ($persona === 'restore') {
            return $this->restore($user);
        }

        // Everything off first, so each persona is what it says and not what
        // it says plus whatever the last one left behind.
        $user->syncRoles([]);
        $user->syncPermissions([]);
        $user->forceFill([
            'osrs_username' => $user->osrs_username ?: 'Pondake',
            'onboarding_completed_at' => now(),
        ])->save();

        $this->clearCoHosting($user);

        match ($persona) {
            'guest' => $this->comment('Sign out in the browser, or use a cookie-less client — there is no account state for a guest.'),
            'player' => null,
            'creator' => $user->givePermissionTo(Permission::findOrCreate('canCreateBoards', 'web')),
            'cohost' => $this->coHost($user),
            'owner' => $this->comment('Owner is per event: open one this account already owns, or use --event with cohost and promote in /admin/events.'),
            'admin' => $user->assignRole(Role::findOrCreate('ADMIN', 'web')),
            'newcomer' => $user->forceFill(['osrs_username' => null, 'onboarding_completed_at' => null])->save(),
            'no-email' => $user->forceFill(['email' => null])->save(),
            default => $this->error("Unknown persona \"{$persona}\"."),
        };

        return $this->report($user->fresh(), $persona);
    }

    /**
     * Written once, before the first change, and never overwritten — so a
     * restore after five switches still returns the state from before the
     * first one.
     */
    private function snapshot(User $user): void
    {
        if (Storage::exists(self::SNAPSHOT)) {
            return;
        }

        Storage::put(self::SNAPSHOT, json_encode([
            'user_id' => $user->id,
            'roles' => $user->getRoleNames()->all(),
            'permissions' => $user->getPermissionNames()->all(),
            'osrs_username' => $user->osrs_username,
            'email' => $user->email,
            'onboarding_completed_at' => $user->onboarding_completed_at?->toIso8601String(),
        ], JSON_PRETTY_PRINT));
    }

    private function restore(User $user): int
    {
        if (! Storage::exists(self::SNAPSHOT)) {
            $this->error('Nothing to restore — no snapshot was taken.');

            return self::FAILURE;
        }

        $state = json_decode(Storage::get(self::SNAPSHOT), true);

        $user->syncRoles($state['roles'] ?? []);
        $user->syncPermissions($state['permissions'] ?? []);
        $user->forceFill([
            'osrs_username' => $state['osrs_username'] ?? null,
            'email' => $state['email'] ?? null,
            'onboarding_completed_at' => $state['onboarding_completed_at'] ?? null,
        ])->save();

        $this->clearCoHosting($user);
        Storage::delete(self::SNAPSHOT);

        return $this->report($user->fresh(), 'restored');
    }

    /** Co-hosting is added by this command, so it is this command's to remove. */
    private function clearCoHosting(User $user): void
    {
        BoardAuthor::where('user_id', $user->id)->where('is_owner', false)->delete();
    }

    private function coHost(User $user): void
    {
        $event = Event::query()
            ->when($this->option('event'), fn ($q, $title) => $q->where('title', 'like', "%{$title}%"))
            ->whereDoesntHave('authors', fn ($q) => $q->where('user_id', $user->id))
            ->first();

        if ($event === null) {
            $this->error('No event found to co-host — pass --event, or seed one with dev:fixtures.');

            return;
        }

        BoardAuthor::create(['event_id' => $event->id, 'user_id' => $user->id, 'is_owner' => false]);
        $this->line("  co-hosting: {$event->title}");
        $this->line("  url: /events/{$event->id}");
    }

    private function report(User $user, string $persona): int
    {
        $this->info("persona: {$persona}");
        $this->line('  account:     '.$user->discord_username);
        $this->line('  roles:       '.($user->getRoleNames()->implode(', ') ?: '—'));
        $this->line('  permissions: '.($user->getPermissionNames()->implode(', ') ?: '—'));
        $this->line('  osrs name:   '.($user->osrs_username ?? '—'));
        $this->line('  email:       '.($user->email ?? '—'));
        $this->line('  onboarding:  '.($user->onboarding_completed_at ? 'done' : 'pending'));

        return self::SUCCESS;
    }
}
