<template>
    <u-card v-if="show">
        <template #header>
            <span class="font-semibold">{{ $t('admin.invite_links') }}</span>
        </template>

        <p class="text-sm text-muted">{{ $t('events.invite_card_desc') }}</p>

        <u-button
            color="primary"
            variant="outline"
            size="sm"
            icon="i-lucide-key-round"
            class="mt-3"
            :label="$t('events.manage_invites')"
            @click="showModal = true"
        />

        <!-- ClientOnly + async, like every other modal here: u-modal reaches
             the '#imports' virtual specifier that breaks the SSR build. -->
        <client-only>
            <invite-manager-modal v-model:open="showModal" :event-id="eventId" />
        </client-only>
    </u-card>
</template>

<script setup>
/**
 * A host's way to the invite links, from the event page itself.
 *
 * Sits with the editors in the sidebar because it is about who is in the
 * event, not about how the event is set up. Renders for nobody else: a link
 * is a credential, and an invite-only event that shows one to a player is
 * not invite-only any more.
 */
import { computed, defineAsyncComponent, ref } from 'vue';
import ClientOnly from '@/Components/ClientOnly.vue';

const InviteManagerModal = defineAsyncComponent(() => import('@/Components/InviteManagerModal.vue'));

const props = defineProps({
    eventId: { type: String, required: true },
    // 'OPEN' | 'GUILD' | 'INVITE' — read from the live event, so a host who
    // switches the mode in the settings modal gets this card without a
    // reload (EventCard carries access_mode on every push).
    accessMode: { type: String, default: null },
    canEdit: { type: Boolean, default: false },
});

const showModal = ref(false);

const show = computed(() => props.canEdit && props.accessMode === 'INVITE');
</script>
