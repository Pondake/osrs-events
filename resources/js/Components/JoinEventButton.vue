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
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

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
});

const busy = ref(false);

function join() {
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
