<template>
    <u-button
        v-if="!joined"
        color="primary"
        :icon="icon"
        :size="size"
        :label="joinLabel || $t('events.join')"
        :loading="busy"
        @click="join"
    />
    <u-button
        v-else
        color="neutral"
        variant="outline"
        icon="i-lucide-log-out"
        :size="size"
        :label="leaveLabel || $t('events.leave_event')"
        :loading="busy"
        @click="leave"
    />

    <!-- Said here, at the moment of joining, rather than by the page — a
         team event stays fully readable to somebody with no team, and the
         missing team only matters once they have decided they want in.
         There is no "join anyway": a team event is played per team, so a
         membership without one buys nothing. The dialog offers the way in
         instead — a team of their own to bring with them. -->
    <client-only>
        <team-entry-modal v-model:open="teamPromptOpen" :event-id="eventId" :teams="teams" />
    </client-only>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ClientOnly from '@/Components/ClientOnly.vue';
import TeamEntryModal from '@/Components/TeamEntryModal.vue';

/**
 * Join or leave, on any event.
 *
 * One component because it is one decision. A race used to be the only type
 * you could join at all, which left bingo and snakes & ladders with no way to
 * say you were playing — so nothing could answer "which events am I in", and
 * on a board merely looking at it enrolled you.
 *
 * The endpoint is the same for every type; what joining means for a race
 * (entering the standings and baselining them) is decided on the server, in
 * EventParticipationService. Nothing here knows what type it is looking at,
 * beyond the label a page may want to phrase its own way.
 */
const props = defineProps({
    eventId: { type: String, required: true },
    joined: { type: Boolean, default: false },
    /** Pages that call it something else — "Enter the race", not "Join". */
    joinLabel: { type: String, default: '' },
    leaveLabel: { type: String, default: '' },
    icon: { type: String, default: 'i-lucide-user-plus' },
    size: { type: String, default: undefined },
    /** A team event this account is on no team in — asks before joining. */
    needsTeam: { type: Boolean, default: false },
    /** Teams this account runs that could be brought in — see TeamEntryModal. */
    teams: { type: Array, default: () => [] },
});

const busy = ref(false);
const teamPromptOpen = ref(false);

function join() {
    if (props.needsTeam) {
        teamPromptOpen.value = true;

        return;
    }

    post();
}

function post() {
    busy.value = true;
    router.post(`/events/${props.eventId}/join`, {}, {
        preserveScroll: true,
        onFinish: () => (busy.value = false),
        onError: (errors) => console.error(errors),
    });
}

function leave() {
    busy.value = true;
    router.delete(`/events/${props.eventId}/join`, {
        preserveScroll: true,
        onFinish: () => (busy.value = false),
        onError: (errors) => console.error(errors),
    });
}
</script>
