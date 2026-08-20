<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * One entry per action in AuditLog::ACTIONS, plus enough metadata variety to
 * exercise every formatter in resources/js/Support/audit.js — booleans,
 * board sizes, the null-means-unlimited roll limit, announcement copy and
 * type, roles, permissions, and an entry with no metadata at all.
 *
 * Writes rows directly rather than through AuditLog::record(), which reads
 * the actor from the session and stamps `now` — neither of which exists in a
 * seeder. This is the one place that's allowed to bypass it.
 *
 * Idempotent through fixed ids: each entry below carries its own, and the
 * run clears exactly those before reinserting. Matching on something softer
 * (an actor label, say) would both miss rows — the actor label is resolved
 * from whichever admin exists, not a constant — and risk deleting real
 * entries that happened to share it.
 */
class AuditLogSeeder extends Seeder
{
    // Fixed so the scope filter has stable options across re-runs. These
    // don't point at real `teams` rows on purpose — the whole reason the
    // labels are stored on the entry is that the team may be gone.
    private const TEAM_IRON_FIST = '0192b000-0000-7000-8000-00000000000a';

    private const TEAM_VANGUARD = '0192b000-0000-7000-8000-00000000000b';

    private const TEAM_FREELANCE = '0192b000-0000-7000-8000-00000000000c';

    private const GUILD_ID = '318972164434528769';

    public function run(): void
    {
        // Linked to a real user where one exists, so the "actor still exists"
        // case is represented too — the deleted-actor case is covered by the
        // rows whose actor_id stays null.
        $admin = User::role('ADMIN')->first();
        $adminLabel = $admin?->displayName() ?: 'mbeetje';

        $entries = [
            [
                'id' => '0192a000-0000-7000-8000-000000000001',
                'action' => 'settings.updated',
                'actor' => $admin,
                'actor_label' => $adminLabel,
                'metadata' => [
                    'registration_open' => ['from' => true, 'to' => false],
                    'default_board_size' => ['from' => 'SIZE_7X7', 'to' => 'SIZE_9X9'],
                    'default_dice_roll_limit' => ['from' => 1, 'to' => null],
                ],
                'minutes_ago' => 4,
            ],
            [
                'id' => '0192a000-0000-7000-8000-000000000002',
                'action' => 'settings.updated',
                'actor' => $admin,
                'actor_label' => $adminLabel,
                'metadata' => [
                    'announcement' => ['from' => null, 'to' => 'Summer bingo starts Friday — [sign up in #events](https://discord.gg/osrs) or read the **rules** first.'],
                    'announcement_type' => ['from' => 'info', 'to' => 'warning'],
                ],
                'minutes_ago' => 26,
            ],
            [
                'id' => '0192a000-0000-7000-8000-000000000003',
                'action' => 'user.role_granted',
                'actor' => $admin,
                'actor_label' => $adminLabel,
                'target_type' => 'User',
                'target_label' => 'Woox',
                'metadata' => ['role' => 'EDITOR'],
                'minutes_ago' => 95,
            ],
            [
                'id' => '0192a000-0000-7000-8000-000000000004',
                'action' => 'user.role_revoked',
                'actor' => $admin,
                'actor_label' => $adminLabel,
                'target_type' => 'User',
                'target_label' => 'Durial321',
                'metadata' => ['role' => 'TEAM_MANAGER'],
                'minutes_ago' => 180,
            ],
            [
                'id' => '0192a000-0000-7000-8000-000000000005',
                'action' => 'user.permission_granted',
                'actor' => $admin,
                'actor_label' => $adminLabel,
                'target_type' => 'User',
                'target_label' => 'B0aty',
                'metadata' => ['permission' => 'canCreateBoards'],
                'minutes_ago' => 320,
            ],
            [
                'id' => '0192a000-0000-7000-8000-000000000006',
                'action' => 'user.permission_revoked',
                'actor' => $admin,
                'actor_label' => $adminLabel,
                'target_type' => 'User',
                'target_label' => 'Sparc Mac',
                'metadata' => ['permission' => 'canCreateTiles'],
                'minutes_ago' => 700,
            ],
            [
                'id' => '0192a000-0000-7000-8000-000000000007',
                // Actor deliberately left unlinked: this is what an entry
                // looks like once the admin who made it has been deleted too.
                'action' => 'user.deleted',
                'actor' => null,
                'actor_label' => 'Zezima',
                'target_type' => 'User',
                'target_label' => 'Bot Account 4471',
                'metadata' => ['roles' => ['PLAYER', 'EDITOR']],
                'minutes_ago' => 1500,
            ],
            // Team- and clan-scoped entries. Two teams under one clan plus a
            // third with no clan at all, so both filters have something with
            // more than one row behind it and the "unguilded team" case is
            // represented too.
            [
                'id' => '0192a000-0000-7000-8000-000000000010',
                'action' => 'team.created',
                'actor' => $admin,
                'actor_label' => $adminLabel,
                'target_type' => 'Team',
                'target_label' => 'Iron Fist',
                'team' => ['id' => self::TEAM_IRON_FIST, 'label' => 'Iron Fist', 'guild_id' => self::GUILD_ID, 'guild_label' => 'Lumbridge Legends'],
                'minutes_ago' => 60,
            ],
            [
                'id' => '0192a000-0000-7000-8000-000000000011',
                'action' => 'team.member_added',
                'actor' => $admin,
                'actor_label' => $adminLabel,
                'target_type' => 'User',
                'target_label' => 'Woox',
                'team' => ['id' => self::TEAM_IRON_FIST, 'label' => 'Iron Fist', 'guild_id' => self::GUILD_ID, 'guild_label' => 'Lumbridge Legends'],
                'minutes_ago' => 52,
            ],
            [
                'id' => '0192a000-0000-7000-8000-000000000012',
                'action' => 'team.member_removed',
                'actor' => $admin,
                'actor_label' => $adminLabel,
                'target_type' => 'User',
                'target_label' => 'Durial321',
                'team' => ['id' => self::TEAM_IRON_FIST, 'label' => 'Iron Fist', 'guild_id' => self::GUILD_ID, 'guild_label' => 'Lumbridge Legends'],
                'minutes_ago' => 44,
            ],
            [
                'id' => '0192a000-0000-7000-8000-000000000013',
                'action' => 'team.updated',
                'actor' => $admin,
                'actor_label' => $adminLabel,
                'target_type' => 'Team',
                'target_label' => 'Varrock Vanguard',
                'team' => ['id' => self::TEAM_VANGUARD, 'label' => 'Varrock Vanguard', 'guild_id' => self::GUILD_ID, 'guild_label' => 'Lumbridge Legends'],
                'metadata' => [
                    'name' => ['from' => 'Varrock Squad', 'to' => 'Varrock Vanguard'],
                    'icon_url' => ['from' => null, 'to' => 'https://oldschool.runescape.wiki/images/Team_cape_zero.png'],
                ],
                'minutes_ago' => 220,
            ],
            [
                'id' => '0192a000-0000-7000-8000-000000000014',
                'action' => 'board.team_added',
                'actor' => $admin,
                'actor_label' => $adminLabel,
                'target_type' => 'Board',
                'target_label' => 'Weekend Warmup',
                'team' => ['id' => self::TEAM_VANGUARD, 'label' => 'Varrock Vanguard', 'guild_id' => self::GUILD_ID, 'guild_label' => 'Lumbridge Legends'],
                'minutes_ago' => 400,
            ],
            [
                'id' => '0192a000-0000-7000-8000-000000000015',
                'action' => 'board.team_removed',
                'actor' => $admin,
                'actor_label' => $adminLabel,
                'target_type' => 'Board',
                'target_label' => 'Weekend Warmup',
                // No clan: teams can exist without a Discord guild, and the
                // team filter has to work for those too.
                'team' => ['id' => self::TEAM_FREELANCE, 'label' => 'Freelance Ironmen', 'guild_id' => null, 'guild_label' => null],
                'minutes_ago' => 900,
            ],
            [
                'id' => '0192a000-0000-7000-8000-000000000016',
                'action' => 'team.deleted',
                'actor' => $admin,
                'actor_label' => $adminLabel,
                'target_type' => 'Team',
                'target_label' => 'Freelance Ironmen',
                'team' => ['id' => self::TEAM_FREELANCE, 'label' => 'Freelance Ironmen', 'guild_id' => null, 'guild_label' => null],
                'metadata' => ['members' => 6],
                'minutes_ago' => 1100,
            ],
            [
                'id' => '0192a000-0000-7000-8000-000000000008',
                // No metadata at all — the row still has to read properly.
                'action' => 'task.deleted',
                'actor' => $admin,
                'actor_label' => $adminLabel,
                'target_type' => 'Task',
                'target_label' => 'Obtain a Dragon Warhammer',
                'metadata' => null,
                'minutes_ago' => 2880,
            ],
            [
                'id' => '0192a000-0000-7000-8000-000000000009',
                // 'system' is what record() stores when there's no session —
                // worth having one in the list so it doesn't look like a bug
                // the first time it appears in production.
                'action' => 'user.deleted',
                'actor' => null,
                'actor_label' => 'system',
                'target_type' => 'User',
                'target_label' => 'Spam Signup 902',
                'metadata' => ['roles' => []],
                'ip_address' => null,
                'minutes_ago' => 4320,
            ],
        ];

        AuditLog::whereIn('id', array_column($entries, 'id'))->delete();

        foreach ($entries as $entry) {
            $log = new AuditLog([
                'actor_id' => $entry['actor']?->id,
                'actor_label' => $entry['actor_label'],
                'action' => $entry['action'],
                'target_type' => $entry['target_type'] ?? null,
                'target_id' => null,
                'target_label' => $entry['target_label'] ?? null,
                'team_id' => $entry['team']['id'] ?? null,
                'team_label' => $entry['team']['label'] ?? null,
                'guild_id' => $entry['team']['guild_id'] ?? null,
                'guild_label' => $entry['team']['guild_label'] ?? null,
                'metadata' => $entry['metadata'] ?? null,
                'ip_address' => array_key_exists('ip_address', $entry) ? $entry['ip_address'] : '127.0.0.1',
            ]);

            // Fixed id, so HasUuids leaves it alone (it only generates one
            // when the key is empty) and a re-run replaces rather than adds.
            $log->id = $entry['id'];

            // created_at isn't fillable (audit rows are never authored by
            // request input), so it's set directly to spread the entries out.
            $log->created_at = Carbon::now()->subMinutes($entry['minutes_ago']);
            $log->save();
        }

        $this->command?->info('Seeded '.count($entries).' audit log entries.');
    }
}
