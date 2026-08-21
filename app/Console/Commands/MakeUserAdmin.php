<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Promotes a real account to ADMIN.
 *
 * AdminUserSeeder covers local only: it invents an account with a sentinel
 * discord_id and a password, because there are no Discord credentials in a
 * dev environment. On a deployed environment the admin is a real person who
 * has already logged in through Discord, so there is nothing to create —
 * only a role to attach, and no UI can offer that to the first admin without
 * being a privilege-escalation hole.
 */
class MakeUserAdmin extends Command
{
    protected $signature = 'user:make-admin {identifier : Discord username, email, or OSRS name}';

    protected $description = 'Grant the ADMIN role to an existing account';

    public function handle(): int
    {
        $identifier = $this->argument('identifier');

        $matches = User::query()
            ->where('discord_username', $identifier)
            ->orWhere('email', $identifier)
            ->orWhere('osrs_username', $identifier)
            ->get();

        if ($matches->isEmpty()) {
            $this->error("No account matches \"{$identifier}\".");
            $this->line('Log in on the site once first — this promotes an existing account, it does not create one.');

            return self::FAILURE;
        }

        // Three columns are searched, so two accounts can legitimately match
        // one string. Picking the first would silently promote the wrong
        // person, which is the one outcome worth refusing over.
        if ($matches->count() > 1) {
            $this->error("\"{$identifier}\" matches {$matches->count()} accounts:");

            foreach ($matches as $match) {
                $this->line("  {$match->id}  discord={$match->discord_username}  email={$match->email}  osrs={$match->osrs_username}");
            }

            $this->line('Re-run with something unique to one of them.');

            return self::FAILURE;
        }

        $user = $matches->first();

        // findOrCreate, not firstOrCreate(['name' => ...]): the latter skips
        // guard_name, and a role row without one matches no check anywhere.
        $role = Role::findOrCreate('ADMIN', 'web');
        $role->description ??= 'Full access — manage boards, tiles, tasks and users';
        $role->save();

        if ($user->hasRole('ADMIN')) {
            $this->info("{$user->displayName()} is already an ADMIN.");

            return self::SUCCESS;
        }

        $user->assignRole($role);

        $this->info("{$user->displayName()} is now an ADMIN.");
        $this->line('Log out and back in if the account is currently signed in — roles are shared into the page on login.');

        return self::SUCCESS;
    }
}
