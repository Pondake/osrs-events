<template>
    <p v-if="!entries.length" class="px-4 py-8 text-center text-sm text-muted">{{ $t('leaderboard.no_players') }}</p>

    <!-- Finishers first, then everyone still walking — the order comes from
         the server (see BoardLeaderboardService), and the tint is so the two
         halves read as two halves rather than as one list with an odd sort. -->
    <ul v-else class="divide-y divide-default">
        <li
            v-for="entry in entries"
            :key="entry.playerId"
            class="flex items-center gap-3 px-4 py-2.5"
            :class="entry.finishPlace ? 'bg-success/5' : ''"
        >
            <!-- A provisional finish gets its row number, not a medal: the
                 place is not settled until the queue is clear of anyone who
                 submitted earlier. -->
            <div class="w-8 text-center font-bold shrink-0" :class="entry.rank <= 3 ? 'text-primary' : 'text-muted'">
                {{ placeOf(entry) ?? entry.rank }}
            </div>

            <!-- An icon rather than initials when there is nobody to initial:
                 UAvatar derives them from `alt`, so the anonymous label came
                 back as a monogram of itself ("Ap"). -->
            <u-avatar
                :src="avatarFor(entry) ?? undefined"
                :alt="named ? nameFor(entry) : undefined"
                :icon="named ? undefined : 'i-lucide-user'"
                size="sm"
            />

            <div class="flex-1 min-w-0">
                <div class="font-medium truncate">{{ nameFor(entry) }}</div>
                <div v-if="entry.finishPlace && entry.finishProvisional" class="text-xs text-muted">
                    {{ $t('board.finished_unsettled') }}
                </div>
                <div v-else-if="entry.finishPlace" class="text-xs text-success">
                    {{ $t('leaderboard.finished_at', { when: whenText(entry.finishedAt) }) }}
                </div>
                <div v-else class="text-xs text-muted">
                    {{ $t('board.tile', { n: entry.currentPosition + 1 }) }} / {{ totalTiles }} — {{ entry.tilesRemaining }} {{ $t('leaderboard.tiles_left') }}
                </div>
            </div>

            <div class="flex items-center gap-1.5 shrink-0">
                <u-icon v-if="entry.finishPlace" name="i-lucide-flag" class="text-success" :title="$t('board.finished_title')" />
                <u-icon v-if="!entry.finishPlace && entry.pathHasLadder" name="i-lucide-arrow-up-from-line" class="text-success" :title="$t('leaderboard.path_has_ladder')" />
                <u-icon v-if="!entry.finishPlace && entry.pathHasSnake" name="i-lucide-arrow-down-to-line" class="text-error" :title="$t('leaderboard.path_has_snake')" />
            </div>
        </li>
    </ul>
</template>

<script setup>
import { trans } from 'laravel-vue-i18n';
import { ordinal, settledPlace } from '@/Support/board';

const props = defineProps({
    entries: { type: Array, required: true },
    totalTiles: { type: Number, required: true },
    // Sent rather than inferred from a null user: a row with no user is
    // otherwise indistinguishable from a deleted account.
    named: { type: Boolean, default: false },
});

// UAvatar derives initials from `alt`, so the name is passed there as well
// as printed, and both come from one place to stay in step.
const nameFor = (entry) => {
    if (!props.named) return trans('events.anonymous_player');

    return entry.team?.name ?? entry.user?.nickname ?? entry.user?.discord_username ?? trans('common.deleted_user');
};

/** 1st, 2nd, 3rd — anything further down is just its number. */
function medal(place) {
    return place ? (['🥇', '🥈', '🥉'][place - 1] ?? ordinal(place)) : null;
}

/** Null while the place could still change hands — same rule as the event page. */
function placeOf(entry) {
    return medal(settledPlace({ rank: entry.finishPlace, provisional: entry.finishProvisional }));
}

function whenText(value) {
    return value
        ? new Date(value).toLocaleString('en-GB', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })
        : trans('common.unknown');
}

const avatarFor = (entry) =>
    entry.team?.icon_url ?? entry.team?.guild_icon_url ?? entry.user?.avatar_url ?? null;
</script>
