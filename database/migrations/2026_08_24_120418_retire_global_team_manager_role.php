<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

/**
 * Removes `TEAM_MANAGER`, the last of the pre-per-team-roles permissions.
 *
 * It granted management over **every team on the site** — rename, add and
 * remove members, promote and demote — to anyone holding it, and by the time
 * this runs it had exactly one reason to still exist: nobody holding it should
 * lose access on the deploy that introduced OWNER/MANAGER/MEMBER. That deploy
 * was 2026-08-21 (add_role_to_team_members_table), and those per-team roles
 * now cover everything it was created for.
 *
 * **Deleted rather than renamed.** The alternative on the backlog was to keep
 * it under an honest name ("site-wide team staff"), and the reason not to is
 * that nothing in the app explains such a role, nothing grants it deliberately,
 * and ADMIN already covers "somebody has to be able to fix this team". A
 * standing over-permission kept for a use case nobody has is how a permission
 * system rots: it survives every review because removing it feels riskier than
 * leaving it, and the risk only grows.
 *
 * **What somebody loses.** Anyone who held it can no longer manage teams they
 * are not a member of. There is deliberately no automatic conversion: turning a
 * global role into per-team memberships would mean adding that account to every
 * team as a MANAGER, which is a far larger and much less reversible change than
 * the one being undone. If somebody genuinely needs it back, the answers are
 * ADMIN, or a MANAGER seat on the specific teams — both of which say what they
 * are. Who held it is written to the log first, so that conversation can happen
 * with names in it rather than guesses.
 */
return new class extends Migration
{
    private const ROLE = 'TEAM_MANAGER';

    public function up(): void
    {
        $role = Role::where('name', self::ROLE)->first();

        if ($role === null) {
            // The normal case on a fresh install, and on any environment where
            // it was never granted. Nothing to do, and not worth a log line.
            return;
        }

        $holders = User::role(self::ROLE)->pluck('id')->all();

        if ($holders !== []) {
            // Logged before the detach, because after it there is no record
            // anywhere that these accounts ever had it — the role row goes too,
            // so even the audit log's own history has nothing to join against.
            Log::warning('Retiring the global TEAM_MANAGER role', [
                'users' => $holders,
                'note' => 'They keep every per-team role they hold. Site-wide team management is now ADMIN only.',
            ]);
        }

        // Detached explicitly rather than relying on the pivot's cascade: the
        // package's own tables are its business, and an explicit detach is the
        // half that has to happen even if the role row is kept.
        foreach (User::role(self::ROLE)->get() as $user) {
            $user->removeRole($role);
        }

        $role->delete();
    }

    /**
     * Recreates the role, empty.
     *
     * It cannot restore who held it — that is gone by design, see above — and
     * it deliberately does not put the check back in `Team::isManagedBy()`,
     * which is code rather than schema. Rolling back leaves a role that grants
     * nothing, which is the honest result: the permission this removes lived in
     * the model, not in the database.
     */
    public function down(): void
    {
        Role::findOrCreate(self::ROLE, config('auth.defaults.guard'));
    }
};
