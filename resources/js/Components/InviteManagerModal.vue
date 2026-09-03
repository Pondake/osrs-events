<template>
    <u-modal
        v-model:open="isOpen"
        :title="$t('admin.invite_links')"
        :ui="{ content: 'max-w-lg' }"
    >
        <template #body>
            <invite-fields
                :event-id="eventId"
                :invites="invites"
                :open-count="openCount"
                :max-open="maxOpen"
                :creating="creating"
                @create="createInvite"
                @revoke="revokeInvite"
            />
        </template>

        <template #footer>
            <u-button block color="neutral" variant="outline" :label="$t('common.close')" @click="isOpen = false" />
        </template>
    </u-modal>
</template>

<script setup>
/**
 * The invite links of one event, on the event's own page.
 *
 * The same panel the settings modal shows under "Invite links" — one
 * component, one composable, so the two cannot say different things. Handing
 * out a link is running the event, not configuring it, and it used to be
 * three clicks into a settings dialog on a tab that only exists in one
 * access mode.
 */
import { computed, watch } from 'vue';
import InviteFields from '@/Components/BoardSettings/InviteFields.vue';
import { useInvites } from '@/Composables/useInvites';

const props = defineProps({
    open: { type: Boolean, default: false },
    eventId: { type: String, required: true },
});

const emit = defineEmits(['update:open']);

const isOpen = computed({ get: () => props.open, set: (v) => emit('update:open', v) });

const { invites, openCount, maxOpen, creating, fetchInvites, createInvite, revokeInvite } = useInvites(
    () => `/events/${props.eventId}`,
);

// Fetched on opening rather than with the page: the card that owns this modal
// is there for the whole visit, and a host who never opens it should not be
// asking the server for credentials they did not come for.
//
// `immediate` matters because this component is loaded lazily inside
// <client-only>: the first click flips `open` and only then does the chunk
// arrive, so the watcher can come into existence after the change it was
// meant to catch — and the list then stayed empty until something else
// refilled it. Running it once on creation covers that case without the
// second hook that covering it separately would need, which fired alongside
// the watcher and asked for the same list twice.
watch(isOpen, (open) => {
    if (open) fetchInvites();
}, { immediate: true });
</script>
