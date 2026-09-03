<template>
    <Head :title="$t('seo.leaderboard_title', { title: board.title })" />

    <u-main>
        <u-page>
            <u-container class="py-12 max-w-2xl">
                <u-breadcrumb :items="breadcrumbs" class="mb-4" />

                <!-- Stacks on a phone. Side by side, the title block was left
                     with a ~150px column and the description broke into
                     four ragged lines beside a button that needed none of
                     the room it was taking. -->
                <div class="flex flex-col items-start gap-4 mb-8 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-highlighted">{{ board.title }}</h1>
                        <p class="text-muted">{{ $t('leaderboard.title') }}</p>
                    </div>
                    <u-button :href="`/events/${board.id}`" color="neutral" variant="outline" icon="i-lucide-arrow-left" :label="$t('leaderboard.back_to_board')" />
                </div>

                <div v-if="!entries.length" class="text-center py-16 text-muted">{{ $t('leaderboard.no_players') }}</div>

                <div v-else class="divide-y divide-default rounded-lg ring ring-default bg-default">
                    <!-- Finishers first, then everyone still walking — the
                         order comes from the server (see
                         LeaderboardController), and the tint is so the two
                         halves read as two halves rather than as one list
                         with an odd sort. -->
                    <div
                        v-for="entry in entries"
                        :key="entry.playerId"
                        class="flex items-center gap-4 px-4 py-3"
                        :class="entry.finishPlace ? 'bg-success/5' : ''"
                    >
                        <div class="w-8 text-center font-bold" :class="entry.rank <= 3 ? 'text-primary' : 'text-muted'">
                            {{ medal(entry.finishPlace) ?? entry.rank }}
                        </div>

                        <!-- An icon rather than initials when there is nobody to
                             initial: UAvatar derives them from `alt`, so the
                             anonymous label came back as a monogram of itself
                             ("Ap"), which reads like somebody's actual name. -->
                        <u-avatar
                            :src="avatarFor(entry) ?? undefined"
                            :alt="namesArePublic ? nameFor(entry) : undefined"
                            :icon="namesArePublic ? undefined : 'i-lucide-user'"
                            size="sm"
                        />

                        <div class="flex-1 min-w-0">
                            <div class="font-medium truncate">{{ nameFor(entry) }}</div>
                            <!-- A finisher's position on the track stopped
                                 being the interesting number about them the
                                 moment they got home; when they did is what
                                 the podium is ordered by, so that is what is
                                 printed. -->
                            <div v-if="entry.finishPlace" class="text-xs text-success">
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
                    </div>
                </div>
            </u-container>
        </u-page>
    </u-main>
</template>

<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { ordinal } from '@/Support/board';

const props = defineProps({
    board: { type: Object, required: true },
    totalTiles: { type: Number, required: true },
    entries: { type: Array, required: true },
    // Whether the roster is this reader's to see — see
    // BoardAccessService::canSeeParticipants(). Sent as its own prop because
    // a row with no user would otherwise read as a deleted account.
    namesArePublic: { type: Boolean, default: true },
});

// A team borrows its Discord server's icon before falling back to
// initials, which UAvatar derives from `alt` — so the name has to be passed
// there as well as printed, and both come from one place to stay in step.
const nameFor = (entry) => {
    if (!props.namesArePublic) return trans('events.anonymous_player');

    return entry.team?.name ?? entry.user?.nickname ?? entry.user?.discord_username ?? trans('common.deleted_user');
};

/** 1st, 2nd, 3rd — anything further down is just its number. */
function medal(place) {
    return place ? (['🥇', '🥈', '🥉'][place - 1] ?? ordinal(place)) : null;
}

function whenText(value) {
    return value
        ? new Date(value).toLocaleString('en-GB', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })
        : trans('common.unknown');
}

const avatarFor = (entry) =>
    entry.team?.icon_url ?? entry.team?.guild_icon_url ?? entry.user?.avatar_url ?? null;

const breadcrumbs = computed(() => [
    { label: trans('nav.home'), icon: 'i-lucide-house', href: '/' },
    { label: trans('nav.events'), href: '/events' },
    { label: props.board.title, href: `/events/${props.board.id}` },
    { label: trans('boards.leaderboard') },
]);
</script>
