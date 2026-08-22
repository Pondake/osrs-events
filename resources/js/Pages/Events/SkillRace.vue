<template>
    <Head :title="event.title" />

    <u-main>
        <u-page>
            <u-container class="py-8 sm:py-12">
                <div class="flex items-start justify-between gap-4 flex-wrap mb-6">
                    <event-type-heading :event="event" :can-edit="canEdit">
                        <template #meta>
                            <span class="inline-flex items-center gap-1.5">
                                <u-icon name="i-lucide-trophy" class="size-4 shrink-0" />
                                {{ rankedBy }}
                            </span>
                        </template>
                    </event-type-heading>

                    <div class="flex items-center gap-3 shrink-0">
                        <!-- Reports the live channel rather than offering a
                             refresh: the table updates itself, and a refresh
                             button would imply it can't be trusted to. -->
                        <span v-if="streaming" class="inline-flex items-center gap-1.5 text-xs" :class="stale ? 'text-muted' : 'text-success'">
                            <span class="relative flex size-2">
                                <span class="relative inline-flex size-2 rounded-full" :class="stale ? 'bg-muted' : 'bg-success'" />
                            </span>
                            {{ stale ? $t('events.reconnecting') : $t('events.auto_updating') }}
                        </span>

                        <u-button
                            :href="`/events/${event.id}/participants`"
                            color="neutral"
                            variant="outline"
                            size="sm"
                            icon="i-lucide-users-round"
                            :label="$t('participants.open')"
                        />

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
                                    <span v-else-if="entry.syncedAt" class="text-sm font-medium text-highlighted tabular-nums">
                                        +{{ formatXp(entry.gained) }}
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
            <board-settings-modal v-if="canEdit" v-model:open="showSettingsModal" :board="event" />
        </client-only>
</template>

<script setup>
import { computed, defineAsyncComponent, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import ClientOnly from '@/Components/ClientOnly.vue';
import EventTypeHeading from '@/Components/EventTypeHeading.vue';
import { trans } from 'laravel-vue-i18n';
import RichText from '@/Components/RichText.vue';
import { boardEventStatus, formatDate } from '@/Support/board';
import { metricLabel, rankedByLabel } from '@/Support/metrics';
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
const isBossRace = computed(() => props.event.metricKind === 'boss');


const metricName = computed(() => metricLabel(props.event.metric, props.event.metricKind));

const rankedBy = computed(() => rankedByLabel(props.event.metric, props.event.metricKind));

const status = computed(() => boardEventStatus(props.event.start_date, props.event.end_date));


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

// One shared channel per event; this page listens for 'standings'. The
// connection handling lives in the composable because bingo and the board
// need exactly the same thing.
const { streaming, stale } = useEventStream({
    // A finished race's numbers cannot change. Holding a PHP worker open to
    // watch them not change is the one cost this feature has, so don't.
    url: () => (status.value === 'ended' ? null : `/events/${props.event.id}/stream`),
    event: 'standings',
    onMessage: (payload) => (rows.value = payload.standings),
});

</script>
