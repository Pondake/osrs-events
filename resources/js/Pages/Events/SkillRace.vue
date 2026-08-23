<template>
    <Head :title="liveEvent.title" />

    <u-main>
        <u-page>
            <u-container class="py-8 sm:py-12">
                <div class="flex items-start justify-between gap-4 flex-wrap mb-6">
                    <event-type-heading :event="liveEvent" :can-edit="canEdit" :viewing-as-admin="viewingAsAdmin">
                        <template #meta>
                            <!-- The skill's own icon, beside the line that
                                 names it. A race is chosen by its skill and
                                 then never showed one anywhere — and this is
                                 the one line already saying "ranked by
                                 Mining", so the icon belongs to it rather
                                 than needing a place of its own.
                                 A boss race falls back to the trophy: the
                                 icon set is built from wiki item images and
                                 there is no Zulrah icon (see
                                 Support/metrics.js). -->
                            <span class="inline-flex items-center gap-1.5">
                                <img
                                    v-if="metricIcon"
                                    :src="metricIcon"
                                    alt=""
                                    class="size-4 shrink-0 object-contain"
                                >
                                <u-icon v-else name="i-lucide-trophy" class="size-4 shrink-0" />
                                {{ rankedBy }}
                            </span>
                        </template>
                    </event-type-heading>

                    <!-- Wraps on a phone. `shrink-0` kept this bar at its full
                         natural width, so it never wrapped and simply ran
                         off the side — 772px of controls on a 375px screen
                         on the bingo card. It only needs to hold its ground
                         once there is room for it to. -->
                    <div class="flex flex-wrap items-center gap-2 sm:gap-3 sm:shrink-0">
                        <!-- Reports the live channel rather than offering a
                             refresh: the table updates itself, and a refresh
                             button would imply it can't be trusted to. -->
                        <span v-if="streaming" class="inline-flex items-center gap-1.5 text-xs" :class="stale ? 'text-muted' : 'text-success'">
                            <span class="relative flex size-2">
                                <span class="relative inline-flex size-2 rounded-full" :class="stale ? 'bg-muted' : 'bg-success'" />
                            </span>
                            {{ stale ? $t('events.reconnecting') : $t('events.auto_updating') }}
                        </span>

                        <!-- Only worth a button while the standings are not
                             already the whole list. A race ranks every
                             entrant on the page, so "who is playing" was a
                             link to the same names one scroll down. It comes
                             back once the table is long enough to be a
                             sample rather than the roster. -->
                        <u-button
                            v-if="rows.length > PARTICIPANTS_WORTH_A_LINK"
                            :href="`/events/${liveEvent.id}/participants`"
                            color="neutral"
                            variant="outline"
                            size="sm"
                            icon="i-lucide-users-round"
                            :label="$t('participants.open')"
                        />

                        <!-- Entering is a decision, so it's a button. Looking
                             at a public leaderboard must not enrol anyone.
                             The same component every other type uses, with the
                             race's own words on it. -->
                        <join-event-button
                            v-if="isParticipant || (status !== 'ended' && status !== 'paused')"
                            :event-id="liveEvent.id"
                            :joined="isParticipant"
                            icon="i-lucide-swords"
                            :join-label="$t('events.enter')"
                            :leave-label="$t('events.leave')"
                        />

                        <!-- The page has always been handed `canEdit` and
                             never rendered anything with it, so an owner had
                             no way into their own race's settings — the same
                             account can edit a snakes & ladders board from
                             its page, which is where the inconsistency shows.
                             -->
                        <u-button
                            v-if="canEdit"
                            color="neutral"
                            variant="outline"
                            icon="i-lucide-settings"
                            :label="$t('board.event_settings')"
                            @click="showSettingsModal = true"
                        />
                    </div>
                </div>

                <!-- Without an RSN there is nothing to look up on the hiscores, so
                     this person is not in the race no matter that they joined. Said
                     plainly, at the top, with the fix one click away. -->
                <u-alert
                    v-if="!osrsUsername"
                    icon="i-lucide-user-round-search"
                    color="warning"
                    variant="subtle"
                    class="mb-6"
                    :title="$t('events.no_rsn_title')"
                    :description="$t('events.no_rsn_body')"
                    :actions="[{ label: $t('events.no_rsn_action'), color: 'warning', variant: 'solid', onClick: goToProfile }]"
                />

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                    <div class="lg:col-span-2">
                        <u-card :ui="{ body: 'p-0 sm:p-0' }">
                            <template #header>
                                <div class="flex items-center justify-between gap-3 flex-wrap">
                                    <span class="font-semibold">{{ $t('events.standings') }}</span>
                                    <span class="text-xs text-muted">{{ rankedBy }}</span>
                                </div>
                            </template>

                            <ul v-if="rows.length" class="divide-y divide-default">
                                <li
                                    v-for="entry in rows"
                                    :key="entry.id"
                                    class="flex items-center gap-3 px-4 py-3"
                                    :class="entry.name === osrsUsername ? 'bg-primary/5' : ''"
                                >
                                    <!-- No rank for anyone we have no
                                         measurement for — they are listed as
                                         entrants, not placed. -->
                                    <span
                                        class="w-7 text-sm font-semibold tabular-nums shrink-0"
                                        :class="entry.rank !== null && entry.rank <= 3 && entry.gained > 0 ? 'text-primary' : 'text-muted'"
                                    >{{ entry.rank ?? '—' }}</span>

                                    <u-avatar :src="entry.avatarUrl ?? undefined" :alt="entry.name" size="sm" class="shrink-0" />

                                    <div class="flex-1 min-w-0">
                                        <p class="truncate font-medium">{{ entry.name }}</p>
                                        <p v-if="entry.displayName" class="truncate text-xs text-muted">{{ entry.displayName }}</p>
                                    </div>

                                    <!-- Three states, not two: a real gain, a genuine
                                         zero, and "we have never managed to look this
                                         name up" — which is not a score at all.
                                         A native title rather than u-tooltip: this
                                         page renders server-side and Nuxt UI's
                                         interactive components are the SSR hazard the
                                         backlog lists. -->
                                    <span
                                        v-if="entry.error"
                                        class="text-sm text-muted inline-flex items-center gap-1 shrink-0"
                                        :title="$t(`events.error_${entry.error}_hint`)"
                                    >
                                        <u-icon name="i-lucide-circle-help" class="size-4" />
                                        {{ $t(`events.error_${entry.error}`) }}
                                    </span>
                                    <!-- The number says what it is. "+412K"
                                         alone is a quantity of nothing in
                                         particular; the skill's own icon
                                         beside it keeps the race's subject in
                                         view on every row, and the unit is
                                         there for a boss race that has no
                                         icon to lean on. -->
                                    <span v-else-if="entry.syncedAt" class="text-sm font-medium text-highlighted tabular-nums inline-flex items-center gap-1.5 shrink-0">
                                        +{{ formatXp(entry.gained) }}
                                        <img v-if="metricIcon" :src="metricIcon" alt="" class="size-4 object-contain">
                                        <span v-else class="text-xs text-muted">{{ $t(isBossRace ? 'events.unit_kills' : 'events.unit_xp') }}</span>
                                    </span>
                                    <!-- An unstarted race has nothing to
                                         measure yet, which is a different
                                         thing from a sync that hasn't
                                         happened — and showing both as
                                         "waiting" makes a working event look
                                         stuck. -->
                                    <span v-else class="text-sm text-muted">
                                        {{ status === 'upcoming' ? $t('events.not_started') : $t('events.pending_sync') }}
                                    </span>
                                </li>
                            </ul>

                            <div v-else class="text-center py-14 px-6">
                                <u-icon name="i-lucide-trophy" class="size-10 text-dimmed mx-auto mb-3" />
                                <p class="text-sm font-medium">{{ $t('events.no_standings') }}</p>
                                <p class="text-sm text-muted mt-1 max-w-sm mx-auto">{{ $t('events.no_standings_desc') }}</p>
                            </div>
                        </u-card>
                    </div>

                    <div class="space-y-6">
                        <u-card>
                            <template #header>
                                <span class="font-semibold">{{ $t('board.information') }}</span>
                            </template>

                            <div class="space-y-2 text-sm">
                                <div class="flex items-center gap-2">
                                    <u-icon name="i-lucide-trophy" class="size-4 text-muted shrink-0" />
                                    <span>{{ rankedBy }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <u-icon name="i-lucide-calendar" class="size-4 text-muted shrink-0" />
                                    <span>{{ dateRange }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <u-icon name="i-lucide-users" class="size-4 text-muted shrink-0" />
                                    <span>{{ $t('events.participants', { count: rows.length }) }}</span>
                                </div>
                            </div>
                        </u-card>

                        <!-- Not decoration. The competition model, the metric names
                             and the start/end/gained shape are all Wise Old Man's. -->
                        <u-card>
                            <template #header>
                                <span class="font-semibold">{{ $t('events.data_source') }}</span>
                            </template>
                            <p class="text-sm text-muted">
                                <rich-text :text="$t('events.wom_credit')" />
                            </p>
                        </u-card>
                    </div>
                </div>
            </u-container>
        </u-page>
    </u-main>

        <!-- Async + client-only for the same reason BoardShow does it: the
             modal reaches @nuxt/ui composables that break the SSR build. -->
        <client-only>
            <board-settings-modal v-if="canEdit" v-model:open="showSettingsModal" :board="liveEvent" :webhook-url="webhookUrl" />
        </client-only>
</template>

<script setup>
import { computed, defineAsyncComponent, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import ClientOnly from '@/Components/ClientOnly.vue';
import EventTypeHeading from '@/Components/EventTypeHeading.vue';
import JoinEventButton from '@/Components/JoinEventButton.vue';
import { trans } from 'laravel-vue-i18n';
import RichText from '@/Components/RichText.vue';
import { eventStatus, formatDate } from '@/Support/board';
import { metricIconUrl, metricLabel, rankedByLabel } from '@/Support/metrics';
import { useEventStream } from '@/Composables/useEventStream';

// Async, exactly as BoardShow loads it: the modal reaches @nuxt/ui
// composables that break the SSR build if they enter the module graph.
const BoardSettingsModal = defineAsyncComponent(() => import('@/Components/BoardSettingsModal.vue'));

const showSettingsModal = ref(false);

const props = defineProps({
    event: { type: Object, required: true },
    standings: { type: Array, default: () => [] },
    osrsUsername: { type: String, default: null },
    isParticipant: { type: Boolean, default: false },
    canEdit: { type: Boolean, default: false },
    // True only when a site admin is reading a private event they were never
    // invited to — the heading says so rather than letting it be silent.
    viewingAsAdmin: { type: Boolean, default: false },
    // Editors only — see BoardSettingsModal's own note on why this is not
    // part of the event payload.
    webhookUrl: { type: String, default: null },
});

/**
 * The event as it is now.
 *
 * The prop for the first paint, then whatever the channel sends. The server
 * builds both from one place (App\Support\EventCard) so the page cannot tell
 * which one it is looking at — which is the point: a host moving the end date
 * has to reach everyone watching, and it reached nobody.
 */
const liveEvent = ref({ ...props.event });
watch(() => props.event, (value) => (liveEvent.value = { ...value }));

// Seeded from the server render so the table is complete before any
// JavaScript runs; the stream takes over from here.
const rows = ref([...props.standings]);
/**
 * A copy of a prop only stays right if something copies it again.
 *
 * The channel was the only thing that did, which made your OWN actions the
 * slowest ones on the page: the server sends fresh props straight back, and
 * this list kept the numbers from before until the stream got round to
 * saying so. Found on the bingo card — approving a claim left the standings
 * reading "nobody has marked a square yet" next to a counter saying 1 of 16.
 */
watch(() => props.standings, (value) => (rows.value = [...value]));

// A drop race counts boss kills, a skill race counts XP. Same table, same
// ranking, different noun — so the copy is chosen from the kind rather than
// the page assuming everything is a skill.
const isBossRace = computed(() => liveEvent.value.metricKind === 'boss');


const metricName = computed(() => metricLabel(liveEvent.value.metric, liveEvent.value.metricKind));

// Above this many entrants the standings stop being the roster and the
// participants page earns its link back.
const PARTICIPANTS_WORTH_A_LINK = 10;

const rankedBy = computed(() => rankedByLabel(liveEvent.value.metric, liveEvent.value.metricKind));

// Null for a boss race, which has no icon to draw — see Support/metrics.js.
const metricIcon = computed(() => metricIconUrl(liveEvent.value.metric, liveEvent.value.metricKind));

const status = computed(() => eventStatus(liveEvent.value));


const dateRange = computed(() => {
    if (!liveEvent.value.start_date && !liveEvent.value.end_date) return trans('boards.no_dates');

    return `${formatDate(liveEvent.value.start_date)} – ${formatDate(liveEvent.value.end_date)}`;
});

// Grouped thousands: XP gains run into the millions and an unbroken run of
// digits can't be read at a glance.
function formatXp(value) {
    return new Intl.NumberFormat('en-GB').format(value ?? 0);
}

function goToProfile() {
    router.visit('/settings/profile');
}

// One shared channel per event; this page listens for 'standings'. The
// connection handling lives in the composable because bingo and the board
// need exactly the same thing.
const { streaming, stale } = useEventStream({
    // A finished race's numbers cannot change. Holding a PHP worker open to
    // watch them not change is the one cost this feature has, so don't.
    url: () => (status.value === 'ended' ? null : `/events/${liveEvent.value.id}/stream`),
    event: 'standings',
    onMessage: (payload) => {
        rows.value = payload.standings;

        // Merged, not replaced, so a field the channel does not know about
        // survives the first push.
        if (payload.event) liveEvent.value = { ...liveEvent.value, ...payload.event };
    },
});

</script>
