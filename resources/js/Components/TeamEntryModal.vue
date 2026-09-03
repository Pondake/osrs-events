<template>
    <u-modal v-model:open="isOpen" :title="canPick ? $t('events.team_pick_title') : $t('events.team_required_title')">
        <template #body>
            <div v-if="canPick" class="space-y-4">
                <p class="text-sm text-muted leading-relaxed">{{ $t('events.team_pick_body') }}</p>

                <u-form-field :label="$t('events.team_pick_label')">
                    <u-select v-model="teamId" :items="options" class="w-full" />
                </u-form-field>
            </div>

            <p v-else class="text-sm text-muted leading-relaxed">{{ $t('events.team_required_body') }}</p>
        </template>

        <template #footer>
            <div class="flex justify-end gap-2 w-full">
                <u-button color="neutral" variant="ghost" :label="$t('common.cancel')" @click="isOpen = false" />
                <u-button
                    v-if="canPick"
                    color="primary"
                    icon="i-lucide-users"
                    :label="$t('events.team_pick_confirm')"
                    :loading="busy"
                    :disabled="!teamId"
                    @click="confirm"
                />
                <u-button v-else color="primary" icon="i-lucide-users" :label="$t('board.go_to_teams')" href="/teams" />
            </div>
        </template>
    </u-modal>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * Bringing a team into a team event, from the player's side.
 *
 * A team event is played per team, so somebody whose teams are all outside it
 * has nothing to play as — and until this existed, nothing they could do
 * about it either: teams were assigned by the host, and an open event had no
 * way in at all. Reported from exactly that state, on an event that said it
 * was open to everyone.
 *
 * Only teams this account OWNS or MANAGES are offered (the server decides
 * that too — see EventParticipationService::enterTeam). Entering a team
 * commits its whole score to the event, which is not a plain member's call,
 * so they get the explanation instead of a picker.
 *
 * Posts the ordinary join with a `team_id`: one endpoint, one access check,
 * and joining and entering the team stay a single action rather than two
 * that can half-succeed.
 */
const props = defineProps({
    open: { type: Boolean, default: false },
    eventId: { type: String, required: true },
    /** @type {{id: string, name: string, iconUrl: ?string, guildIconUrl: ?string}[]} */
    teams: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:open']);

const isOpen = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value),
});

const canPick = computed(() => props.teams.length > 0);

const teamId = ref(props.teams[0]?.id ?? null);

// The list arrives with the page, so a team entered elsewhere (or a team
// created in another tab) changes it under an open dialog.
watch(() => props.teams, (teams) => {
    if (!teams.some((team) => team.id === teamId.value)) teamId.value = teams[0]?.id ?? null;
});

// Own icon first, then the linked Discord server's, then initials from
// `alt` — the same chain TeamAvatar walks, in the shape USelect wants.
const options = computed(() => props.teams.map((team) => ({
    label: team.name,
    value: team.id,
    avatar: { src: team.iconUrl || team.guildIconUrl || undefined, alt: team.name },
})));

const busy = ref(false);

function confirm() {
    if (!teamId.value) return;

    busy.value = true;
    router.post(`/events/${props.eventId}/join`, { team_id: teamId.value }, {
        preserveScroll: true,
        onSuccess: () => (isOpen.value = false),
        onError: (errors) => console.error(errors),
        onFinish: () => (busy.value = false),
    });
}
</script>
