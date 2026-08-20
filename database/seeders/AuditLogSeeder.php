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
    public function run(): void
    {
        // Linked to a real user where one exists, so the "actor still exists"
        // case is represented too — the deleted-actor case is covered by the
        // rows whose actor_id stays null.
        $admin = User::query()->whereHas('userRoles.role', fn ($q) => $q->where('name', 'ADMIN'))->first();
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
