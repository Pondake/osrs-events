<template>
    <div class="py-2 space-y-4">
        <p class="text-sm text-muted">{{ $t('admin.invite_links_desc') }}</p>

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

        <div class="divide-y divide-default rounded-md ring ring-default">
            <div v-for="invite in invites" :key="invite.id" class="flex items-center justify-between gap-3 px-3 py-2">
                <div class="min-w-0">
                    <div class="font-mono text-sm">{{ invite.short_code }}</div>
                    <div class="text-xs text-muted">
                        {{ invite.use_count ?? 0 }}{{ invite.max_uses ? ` / ${invite.max_uses}` : '' }} {{ $t('admin.invite_uses_suffix') }}
                        <span v-if="invite.expires_at"> · {{ $t('admin.invite_expires', { date: new Date(invite.expires_at).toLocaleDateString() }) }}</span>
                    </div>
                </div>
                <u-button icon="i-lucide-trash-2" size="xs" color="error" variant="ghost" :aria-label="$t('common.delete')" @click="emit('revoke', invite)" />
            </div>
            <p v-if="!invites.length" class="px-3 py-4 text-center text-sm text-muted">{{ $t('admin.no_invites') }}</p>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

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
    invites: { type: Array, default: () => [] },
    openCount: { type: Number, default: 0 },
    maxOpen: { type: Number, default: null },
    creating: { type: Boolean, default: false },
});

const emit = defineEmits(['create', 'revoke']);

const limitReached = computed(() => props.maxOpen !== null && props.openCount >= props.maxOpen);
</script>
