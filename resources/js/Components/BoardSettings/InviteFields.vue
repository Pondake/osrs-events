<template>
    <div class="py-2 space-y-4">
        <p class="text-sm text-muted">{{ $t('admin.invite_links_desc') }}</p>

        <p class="text-xs text-muted">{{ $t('admin.invite_link_or_code') }}</p>

        <div class="flex items-center justify-between gap-3">
            <p class="text-xs text-muted">
                <span v-if="maxOpen !== null">{{ $t('admin.invite_open_count', { open: openCount, max: maxOpen }) }}</span>
            </p>
            <u-button
                size="sm"
                color="primary"
                icon="i-lucide-plus"
                :label="$t('admin.create_invite')"
                :loading="creating"
                :disabled="limitReached"
                @click="emit('create')"
            />
        </div>

        <!-- Said before the click rather than after it: the server refuses
             past the limit either way, and a disabled button with no reason
             is the same dead end as an empty dropdown. -->
        <u-alert
            v-if="limitReached"
            color="neutral"
            variant="subtle"
            icon="i-lucide-info"
            :description="$t('admin.invite_limit_reached', { max: maxOpen })"
        />

        <!-- A link and a code are one invite in two shapes, which the app
             asked people to work out for themselves: the gate says "invite
             code or link", and the host's own list showed a six-character
             code with no way to get the link at all. Both are here now, both
             copyable, and the sentence above says they are the same thing. -->
        <div class="divide-y divide-default rounded-md ring ring-default">
            <div v-for="invite in invites" :key="invite.id" class="flex items-center justify-between gap-3 px-3 py-2">
                <div class="min-w-0">
                    <div class="font-mono text-sm">{{ invite.short_code }}</div>
                    <div class="text-xs text-muted">
                        {{ invite.use_count ?? 0 }}{{ invite.max_uses ? ` / ${invite.max_uses}` : '' }} {{ $t('admin.invite_uses_suffix') }}
                        <span v-if="invite.expires_at"> · {{ $t('admin.invite_expires', { date: new Date(invite.expires_at).toLocaleDateString() }) }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-1 shrink-0">
                    <u-button
                        icon="i-lucide-hash"
                        size="xs"
                        color="neutral"
                        variant="ghost"
                        :label="$t('admin.invite_copy_code')"
                        @click="copy(invite.short_code, 'code')"
                    />
                    <u-button
                        icon="i-lucide-link"
                        size="xs"
                        color="neutral"
                        variant="ghost"
                        :label="$t('admin.invite_copy_link')"
                        @click="copy(linkFor(invite), 'link')"
                    />
                    <u-button icon="i-lucide-trash-2" size="xs" color="error" variant="ghost" :aria-label="$t('common.delete')" @click="emit('revoke', invite)" />
                </div>
            </div>
            <p v-if="!invites.length" class="px-3 py-4 text-center text-sm text-muted">{{ $t('admin.no_invites') }}</p>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { trans } from 'laravel-vue-i18n';

/**
 * Invite links for an invite-only event.
 *
 * The tab this sits in only exists when access_mode is INVITE and the event
 * already exists — it used to render on every event in every mode, saying
 * "invites appear once the event exists" on events that plainly did. It is
 * not a placeholder for the Teams tab or for anything else; it is a section
 * that applies to one access mode.
 */
const props = defineProps({
    // Needed to build the join URL, which is the event's id plus the
    // invite's token — the code alone cannot produce it.
    eventId: { type: String, default: null },
    invites: { type: Array, default: () => [] },
    openCount: { type: Number, default: 0 },
    maxOpen: { type: Number, default: null },
    creating: { type: Boolean, default: false },
});

const emit = defineEmits(['create', 'revoke']);

const limitReached = computed(() => props.maxOpen !== null && props.openCount >= props.maxOpen);

// Imported on mount, never statically: useToast pulls in the virtual
// '#imports' specifier, and having that in the SSR module graph crashes the
// SSR process at startup for every page — see the note in AppRoot.vue and
// the SSR gotchas in docs/backlog.md. Optional-called below.
let toast;
onMounted(async () => {
    const { useToast } = await import('@nuxt/ui/composables/useToast');
    toast = useToast();
});

/** The one-click shape of the same invite. */
function linkFor(invite) {
    return `${window.location.origin}/events/${props.eventId}/join/${invite.token}`;
}

/**
 * Clipboard writes need a secure context and a permission that can be
 * refused, so the failure path is real rather than theoretical — and a copy
 * button that silently does nothing is worse than no button.
 */
async function copy(value, kind) {
    try {
        await navigator.clipboard.writeText(value);
        toast?.add({ id: 'invite-copy', title: trans(`admin.invite_copied_${kind}`), color: 'success' });
    } catch (error) {
        console.error(error);
        toast?.add({ id: 'invite-copy', title: trans('errors.copy_failed'), color: 'error' });
    }
}
</script>
