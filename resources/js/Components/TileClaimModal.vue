<template>
    <u-modal v-model:open="isOpen" :title="tileTitle" :dismissible="false">
        <template #body>
            <!-- Already claimed: what you submitted, what the host said, and
                 the one destructive action — same shape as BingoClaimModal,
                 the same trust problem solved a second time on this board
                 type. -->
            <div v-if="claim" class="space-y-4 py-2">
                <div class="flex items-center gap-2">
                    <u-icon :name="statusIcon" class="size-5 shrink-0" :class="statusClass" />
                    <span class="font-medium" :class="statusClass">{{ $t(`board.status_${claim.status.toLowerCase()}`) }}</span>
                    <span v-if="claim.reviewedAt" class="text-xs text-muted">{{ reviewedAt }}</span>
                </div>

                <div v-if="claim.reviewNote" class="rounded-lg ring ring-default px-3 py-2">
                    <p class="text-xs font-medium text-muted uppercase tracking-wide mb-1">{{ $t('board.host_said') }}</p>
                    <p class="text-sm">{{ claim.reviewNote }}</p>
                </div>

                <div v-if="claim.note" class="rounded-lg ring ring-default px-3 py-2">
                    <p class="text-xs font-medium text-muted uppercase tracking-wide mb-1">{{ $t('board.your_note') }}</p>
                    <p class="text-sm">{{ claim.note }}</p>
                </div>

                <u-button
                    v-if="claim.proofUrl"
                    :href="claim.proofUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    size="sm"
                    color="neutral"
                    variant="outline"
                    icon="i-lucide-image"
                    :label="$t('board.view_proof')"
                />

                <u-alert
                    :color="canWithdraw ? 'warning' : 'neutral'"
                    variant="subtle"
                    :icon="canWithdraw ? 'i-lucide-triangle-alert' : 'i-lucide-lock'"
                    :description="withdrawNotice"
                />
            </div>

            <div v-else class="space-y-4 py-2">
                <p class="text-sm text-muted">{{ $t('board.claim_intro') }}</p>

                <u-form-field :label="$t('board.proof_url')" :description="$t('board.proof_url_desc')" :error="form.errors.proof_url" required>
                    <u-input v-model="form.proof_url" class="w-full" placeholder="https://" />
                </u-form-field>

                <u-form-field :label="$t('board.claim_note')" :error="form.errors.note">
                    <u-input v-model="form.note" class="w-full" />
                </u-form-field>

                <!-- A preview of Phase 4's plan (docs/runelite-plugin.md,
                     ROADMAP.md), not a working control — completed_via
                     already has a RUNELITE case in the schema with nowhere
                     that said so. Disabled and inert on purpose: nothing
                     here should look clickable before the plugin exists to
                     answer it. -->
                <div class="rounded-lg ring ring-default/60 px-3 py-2.5 opacity-60">
                    <div class="flex items-center gap-2 mb-1.5">
                        <u-icon name="i-lucide-puzzle" class="size-4 text-muted shrink-0" />
                        <span class="text-xs font-medium text-muted">{{ $t('board.runelite_teaser_title') }}</span>
                        <u-badge color="neutral" variant="subtle" size="xs">{{ $t('board.runelite_teaser_badge') }}</u-badge>
                    </div>
                    <u-input disabled size="sm" class="w-full pointer-events-none" :placeholder="$t('board.runelite_teaser_placeholder')" />
                </div>
            </div>
        </template>

        <template #footer>
            <div class="flex items-center justify-end gap-2 w-full">
                <u-button
                    color="neutral"
                    variant="ghost"
                    :label="$t(claim && !canWithdraw ? 'common.close' : 'common.cancel')"
                    @click="isOpen = false"
                />

                <u-button
                    v-if="claim && canWithdraw"
                    :color="isRetry ? 'primary' : 'error'"
                    :loading="withdrawing"
                    :icon="isRetry ? 'i-lucide-rotate-ccw' : 'i-lucide-undo-2'"
                    :label="$t(isRetry ? 'board.try_again_claim' : 'board.withdraw_claim')"
                    @click="withdraw"
                />

                <u-button
                    v-if="!claim"
                    color="primary"
                    :disabled="!form.proof_url.trim()"
                    :loading="form.processing"
                    :label="$t('board.submit_claim')"
                    @click="submit"
                />
            </div>
        </template>
    </u-modal>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';

/**
 * One tile's claim: making it, or looking at the one that is already there.
 *
 * Mirrors BingoClaimModal.vue — same trust problem (a self-reported
 * completion needs proof and a host's sign-off), same shape of dialog, on
 * the other board type. See docs/backlog.md, "Snakes & Ladders task tiles
 * have no claim/approve flow".
 */
const props = defineProps({
    open: { type: Boolean, default: false },
    eventId: { type: String, required: true },
    tile: { type: Object, default: null },
    tileTitle: { type: String, default: '' },
    // The existing claim for this tile, or null to make a new one.
    claim: { type: Object, default: null },
});

const emit = defineEmits(['update:open']);

const isOpen = computed({ get: () => props.open, set: (v) => emit('update:open', v) });

const form = useForm({ proof_url: '', note: '' });
const withdrawing = ref(false);

// Reset on every OPENING, not only when the tile changes — same fix
// BingoClaimModal needed for the same reason.
watch(() => [props.open, props.tile], () => form.reset());

const STATUS_ICON = {
    PENDING: 'i-lucide-clock',
    APPROVED: 'i-lucide-circle-check',
    REJECTED: 'i-lucide-circle-x',
};

const STATUS_CLASS = {
    PENDING: 'text-warning',
    APPROVED: 'text-success',
    REJECTED: 'text-error',
};

const statusIcon = computed(() => STATUS_ICON[props.claim?.status] ?? 'i-lucide-circle-dot');
const statusClass = computed(() => STATUS_CLASS[props.claim?.status] ?? 'text-muted');

const reviewedAt = computed(() => (
    props.claim?.reviewedAt ? new Date(props.claim.reviewedAt).toLocaleString() : null
));

/**
 * Undoing an APPROVAL is the host's call — the server refuses it too.
 *
 * A REJECTION is deliberately not locked the same way BingoClaimModal locks
 * one: a bingo square is optional, so a stuck one still lets the card
 * finish, but this tile is the one the player is standing on — refusing a
 * retry here would brick the whole board, forever, since nothing past it is
 * reachable without completing it first.
 */
const canWithdraw = computed(() => props.claim?.status !== 'APPROVED');

/** "Withdraw" undoes a pending claim; a rejected one is cleared to try again with better proof. */
const isRetry = computed(() => props.claim?.status === 'REJECTED');

const withdrawNotice = computed(() => {
    if (!canWithdraw.value) return trans('board.already_reviewed');
    if (isRetry.value) return trans('board.try_again_notice');

    return trans(props.claim?.status === 'PENDING' ? 'board.withdraw_pending_warning' : 'board.withdraw_warning');
});

function submit() {
    form.post(`/events/${props.eventId}/tiles/${props.tile.id}/toggle`, {
        preserveScroll: true,
        onSuccess: () => (isOpen.value = false),
    });
}

function withdraw() {
    withdrawing.value = true;

    // The same endpoint: posting a claim that already exists clears it —
    // withdraws a pending one, or opens the way to retry a rejected one.
    // Left open on a retry rather than closed: the claim prop goes null on
    // the next render and this same dialog turns straight into the
    // submission form, so trying again is one continuous flow instead of
    // closing and reopening.
    router.post(`/events/${props.eventId}/tiles/${props.tile.id}/toggle`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            if (!isRetry.value) isOpen.value = false;
        },
        onError: (errors) => console.error(errors),
        onFinish: () => (withdrawing.value = false),
    });
}
</script>
