<template>
    <Head :title="event.title" />

    <u-main>
        <u-page>
            <u-container class="py-8 sm:py-12">
                <div class="flex items-start justify-between gap-4 flex-wrap mb-6">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <u-badge :label="$t('events.type_skill_race')" color="primary" variant="subtle" size="sm" />
                            <u-badge :label="$t(`boards.status_${status}`)" :color="statusColor" variant="subtle" size="sm" />
                        </div>
                        <h1 class="text-3xl font-bold text-highlighted mt-2">{{ event.title }}</h1>
                        <p v-if="event.description" class="text-muted mt-1">{{ event.description }}</p>
                    </div>

                    <div class="flex items-center gap-3 shrink-0">
                        <!-- Reports the live channel rather than offering a
                             refresh: the table updates itself, and a refresh
                             button would imply it can't be trusted to. -->
                        <span v-if="streaming" class="inline-flex items-center gap-1.5 text-xs" :class="stale ? 'text-muted' : 'text-success'">
                            <span class="relative flex size-2">
                                <span v-if="!stale" class="absolute inline-flex size-full rounded-full bg-success opacity-60 animate-ping" />
                                <span class="relative inline-flex size-2 rounded-full" :class="stale ? 'bg-muted' : 'bg-success'" />
                            </span>
                            {{ stale ? $t('events.reconnecting') : $t('events.live') }}
                        </span>

                        <!-- Entering is a decision, so it's a button. Looking
                             at a public leaderboard must not enrol anyone. -->
                        <u-button
                            v-if="!isParticipant && status !== 'ended'"
                            color="primary"
                            icon="i-lucide-swords"
                            :label="$t('events.enter')"
                            :loading="entering"
                            @click="enterRace"
                        />
                        <u-button
                            v-else-if="isParticipant"
                            color="neutral"
                            variant="outline"
                            icon="i-lucide-log-out"
                            :label="$t('events.leave')"
                            :loading="entering"
                            @click="leaveRace"
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
                                    <span class="text-xs text-muted">{{ $t('events.ranked_by', { skill: skillLabel }) }}</span>
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
                                        :title="$t('events.not_tracked_hint')"
                                    >
                                        <u-icon name="i-lucide-circle-help" class="size-4" />
                                        {{ $t('events.not_tracked') }}
                                    </span>
                                    <span v-else-if="entry.syncedAt" class="text-sm font-medium text-highlighted tabular-nums">
                                        +{{ formatXp(entry.gained) }}
                                    </span>
                                    <span v-else class="text-sm text-muted">{{ $t('events.pending_sync') }}</span>
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
                                    <span>{{ $t('events.ranked_by', { skill: skillLabel }) }}</span>
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
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import RichText from '@/Components/RichText.vue';
import { boardEventStatus, formatDate } from '@/Support/board';

const props = defineProps({
    event: { type: Object, required: true },
    standings: { type: Array, default: () => [] },
    osrsUsername: { type: String, default: null },
    isParticipant: { type: Boolean, default: false },
    canEdit: { type: Boolean, default: false },
});

const entering = ref(false);

function enterRace() {
    entering.value = true;
    router.post(`/events/${props.event.id}/enter`, {}, {
        preserveScroll: true,
        onFinish: () => (entering.value = false),
        onError: (errors) => console.error(errors),
    });
}

function leaveRace() {
    entering.value = true;
    router.delete(`/events/${props.event.id}/enter`, {
        preserveScroll: true,
        onFinish: () => (entering.value = false),
        onError: (errors) => console.error(errors),
    });
}

// Seeded from the server render so the table is complete before any
// JavaScript runs; the stream takes over from here.
const rows = ref([...props.standings]);

// Whether a live channel was opened at all (it isn't for a finished event, or
// where EventSource doesn't exist), and whether it has stopped keeping up.
const streaming = ref(false);
const stale = ref(false);

const skillLabel = computed(() => (props.event.metric ? trans(`skills.${props.event.metric}`) : '—'));

const status = computed(() => boardEventStatus(props.event.start_date, props.event.end_date));

const STATUS_COLOR = { upcoming: 'info', live: 'success', ended: 'neutral' };
const statusColor = computed(() => STATUS_COLOR[status.value] ?? 'neutral');

const dateRange = computed(() => {
    if (!props.event.start_date && !props.event.end_date) return trans('boards.no_dates');

    return `${formatDate(props.event.start_date)} – ${formatDate(props.event.end_date)}`;
});

// Grouped thousands: XP gains run into the millions and an unbroken run of
// digits can't be read at a glance.
function formatXp(value) {
    return new Intl.NumberFormat('en-GB').format(value ?? 0);
}

function goToProfile() {
    router.visit('/settings/profile');
}

// EventSource rather than a WebSocket: nothing on this page sends anything
// back, so a return channel would be a second service to run for no gain.
// The browser reconnects on its own, including after the server closes the
// stream on its own timer — see SkillRaceController.
let source = null;
let staleTimer = null;

// The server ends every stream after ~45 seconds by design, so a disconnect
// is the normal case, not a fault. Flipping the indicator the instant one
// happens would report a problem roughly every 45 seconds while the page is
// working perfectly — so a disconnect only counts once a reconnect has failed
// to land within this window.
const STALE_AFTER_MS = 6000;

function markLive() {
    clearTimeout(staleTimer);
    staleTimer = null;
    stale.value = false;
}

onMounted(() => {
    // Guarded: SSR has no EventSource, and neither do a few old browsers. The
    // page is already fully rendered without it, so there is nothing to fall
    // back to — it just stops updating by itself, and the indicator stays
    // hidden rather than claiming to be live.
    if (typeof window === 'undefined' || !('EventSource' in window)) return;

    // A finished event's numbers cannot change. Holding a PHP worker open to
    // watch them not change is the one cost this feature has, so don't.
    if (status.value === 'ended') return;

    streaming.value = true;
    source = new EventSource(`/events/${props.event.id}/standings/stream`);

    source.addEventListener('open', markLive);

    source.addEventListener('standings', (message) => {
        try {
            rows.value = JSON.parse(message.data).standings;
            markLive();
        } catch (error) {
            console.error(error);
        }
    });

    // Fires on every disconnect, the scheduled one included. EventSource
    // reconnects by itself, so this starts a grace period rather than tearing
    // anything down.
    source.addEventListener('error', (error) => {
        console.error(error);

        if (staleTimer === null) {
            staleTimer = setTimeout(() => (stale.value = true), STALE_AFTER_MS);
        }
    });
});

onBeforeUnmount(() => {
    // Without this the browser keeps reconnecting to a page nobody is on,
    // and every reconnect takes a PHP worker for 45 seconds.
    clearTimeout(staleTimer);
    source?.close();
    source = null;
});
</script>
