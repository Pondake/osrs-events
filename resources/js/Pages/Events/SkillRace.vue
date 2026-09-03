<template>
    <Head :title="liveEvent.title" />

    <u-main>
        <u-page>
            <u-container class="py-8 sm:py-12">
                <u-breadcrumb :items="breadcrumbs" class="mb-4" />

                <!-- `flex-col sm:flex-row`, not `flex flex-wrap` — see the
                     identical note on this row in BoardShow.vue. `flex-1`'s
                     zero flex-basis makes the browser's wrap decision think
                     the heading wants no space at all, so at 375px it never
                     wrapped and instead got squeezed to ~5px — its title
                     rendered one letter per line. Stacking below `sm`
                     sidesteps the heuristic instead of fighting it. -->
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
                    <event-type-heading
                        :event="liveEvent"
                        :streaming="streaming"
                        :stale="stale"
                    >
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
                            <span
                                class="inline-flex items-center gap-1.5"
                                :title="isBossRace ? $t('events.meta_ranked_by_boss_hint') : $t('events.meta_ranked_by_skill_hint')"
                            >
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
                            size="sm"
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
                        <!-- The answer to "is what I am looking at true?".
                             A host who has just moved the dates or changed
                             the metric is reading a table measured against
                             the old ones, and the scheduled sync runs on its
                             own clock. Hosts only: one press is one outbound
                             request per entrant. -->
                        <u-button
                            v-if="canEdit"
                            color="neutral"
                            variant="outline"
                            size="sm"
                            icon="i-lucide-refresh-cw"
                            :label="$t('events.sync_standings')"
                            :loading="syncing"
                            @click="syncStandings"
                        />

                        <!-- `sm`, like every other control in this bar and on
                             the other two event pages. Left at the default it
                             stood 32px tall beside 28px siblings — the same
                             mismatch the Manage badge caused. -->
                        <u-button
                            v-if="canEdit"
                            color="neutral"
                            variant="outline"
                            size="sm"
                            icon="i-lucide-settings"
                            :label="$t('board.event_settings')"
                            @click="showSettingsModal = true"
                        />
                    </div>
                </div>

                <!-- Full page width, not the heading's half: these are
                     page-wide news and used to stop where the action bar
                     began. -->
                <event-notices
                    :event="liveEvent"
                    :can-edit="canEdit"
                    :viewing-as-admin="viewingAsAdmin"
                    :admin-edit-url="adminEditUrl"
                    class="mb-6"
                />

                <!-- The way in, for a listed invite-only event. Since
                     2026-08-31 such an event opens for everyone, so it no
                     longer renders Boards/AccessGate — and the code field
                     lived there. Directly under the header, because it is the
                     first thing a reader without access needs. -->
                <invite-code-card v-if="needsInvite" :event-id="event.id" class="mb-6" />

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

                <!-- The dates or the metric moved after these numbers were
                     read, so every row is measured against a window that no
                     longer exists. Said before the table rather than inside
                     it: it is true of all of them at once, and a host who
                     just changed the dates is the person most likely to
                     believe what they are looking at. -->
                <u-alert
                    v-if="standingsStale"
                    color="warning"
                    variant="subtle"
                    icon="i-lucide-triangle-alert"
                    class="mb-6"
                    :title="$t('events.stale_title')"
                    :description="canEdit ? $t('events.stale_desc_host') : $t('events.stale_desc_player')"
                >
                    <template v-if="canEdit" #actions>
                        <u-button
                            color="warning"
                            variant="solid"
                            size="xs"
                            icon="i-lucide-refresh-cw"
                            :label="$t('events.sync_standings')"
                            :loading="syncing"
                            @click="syncStandings"
                        />
                    </template>
                </u-alert>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                    <div class="lg:col-span-2">
                        <u-card :ui="{ body: 'p-0 sm:p-0' }">
                            <template #header>
                                <div class="flex items-center justify-between gap-3 flex-wrap">
                                    <span class="font-semibold">{{ $t('events.standings') }}</span>
                                    <div class="flex items-center gap-3 text-xs text-muted">
                                        <!-- How old the numbers are, said where
                                             the numbers are. A table that reads
                                             as live is worth doubting only when
                                             it is not, and the only way to know
                                             was to trust it. -->
                                        <span :title="lastSynced ?? undefined">
                                            {{ lastSynced ? $t('events.sync_last', { when: syncedLabel }) : $t('events.sync_never') }}
                                        </span>
                                        <span>{{ rankedBy }}</span>
                                    </div>
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

                                    <!-- An icon rather than initials when there is
                                         nobody to initial: UAvatar derives them from
                                         `alt`, so the anonymous label came back as a
                                         monogram of itself ("Ap"). -->
                                    <u-avatar
                                        :src="entry.avatarUrl ?? undefined"
                                        :alt="entry.name ?? undefined"
                                        :icon="entry.name === null ? 'i-lucide-user' : undefined"
                                        size="sm"
                                        class="shrink-0"
                                    />

                                    <div class="flex-1 min-w-0">
                                        <!-- Faceless on purpose when the roster is
                                             not public — see Bingo.vue for the rule. -->
                                        <p class="truncate font-medium" :class="entry.name === null ? 'text-muted italic font-normal' : ''">
                                            {{ entry.name ?? $t('events.anonymous_player') }}
                                        </p>
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
                                        +{{ formatMetricValue(entry.gained) }}
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
import EventNotices from '@/Components/EventNotices.vue';
import InviteCodeCard from '@/Components/InviteCodeCard.vue';
import JoinEventButton from '@/Components/JoinEventButton.vue';
import { trans } from 'laravel-vue-i18n';
import RichText from '@/Components/RichText.vue';
import { eventStatus, formatDate } from '@/Support/board';
import { formatMetricValue, metricLabel, rankedByLabel } from '@/Support/metrics';
import { useMetricIcon } from '@/Composables/useMetricIcon';
import { useEventStream } from '@/Composables/useEventStream';

// Async, exactly as BoardShow loads it: the modal reaches @nuxt/ui
// composables that break the SSR build if they enter the module graph.
const BoardSettingsModal = defineAsyncComponent(() => import('@/Components/BoardSettingsModal.vue'));

const showSettingsModal = ref(false);

const syncing = ref(false);

/**
 * Read from the live event, so a host changing the dates in one tab turns
 * this on in every other tab watching the race — the fingerprint carries it
 * (see SignalsEventEdits).
 */
const standingsStale = computed(() => Boolean(liveEvent.value.standings_stale_since));

/**
 * Runs the same refresh the scheduled command does, for this race only.
 *
 * `preserveScroll` because the answer arrives as a toast and a re-rendered
 * table, and a host who pressed this while looking at row twelve should still
 * be looking at row twelve.
 */
function syncStandings() {
    syncing.value = true;

    router.post(`/events/${liveEvent.value.id}/standings/sync`, {}, {
        preserveScroll: true,
        onError: (errors) => console.error(errors),
        onFinish: () => { syncing.value = false; },
    });
}


const props = defineProps({
    // True when this reader is looking at an invite-only event they are
    // not in yet — the code field lives on the page now, not on a gate.
    needsInvite: { type: Boolean, default: false },
    event: { type: Object, required: true },
    standings: { type: Array, default: () => [] },
    osrsUsername: { type: String, default: null },
    isParticipant: { type: Boolean, default: false },
    canEdit: { type: Boolean, default: false },
    // True only when a site admin is reading a private event they were never
    // invited to — the heading says so rather than letting it be silent.
    viewingAsAdmin: { type: Boolean, default: false },
    adminEditUrl: { type: String, default: null },
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
 * When the numbers on screen were last read from Wise Old Man.
 *
 * The newest of the rows, not the oldest: the question a host is asking is
 * "has this been looked at since I changed it", and one entrant nobody has
 * been able to measure for a week should not make the whole table read as
 * stale.
 */
const lastSynced = computed(() => {
    const stamps = rows.value.map((row) => row.syncedAt).filter(Boolean).sort();

    return stamps.length ? stamps[stamps.length - 1] : null;
});

/** "12:04" today, a date once it is older than that. */
const syncedLabel = computed(() => {
    if (! lastSynced.value) return '';

    const when = new Date(lastSynced.value);
    const sameDay = when.toDateString() === new Date().toDateString();

    return sameDay
        ? when.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' })
        : when.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
});



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
// Through the composable, not the bare helper: an admin can override a
// boss icon and that override is a prop, not a committed file.
const iconFor = useMetricIcon();
const metricIcon = computed(() => iconFor(liveEvent.value.metric, liveEvent.value.metricKind));

const status = computed(() => eventStatus(liveEvent.value));

const breadcrumbs = computed(() => [
    { label: trans('nav.home'), icon: 'i-lucide-house', href: '/' },
    { label: trans('nav.events'), href: '/events' },
    { label: liveEvent.value.title },
]);

const dateRange = computed(() => {
    if (!liveEvent.value.start_date && !liveEvent.value.end_date) return trans('boards.no_dates');

    return `${formatDate(liveEvent.value.start_date)} – ${formatDate(liveEvent.value.end_date)}`;
});

function goToProfile() {
    router.visit('/settings/connections');
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
