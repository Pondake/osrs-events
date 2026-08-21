<template>
    <u-modal v-model:open="isOpen" :title="square?.label || $t('bingo.empty_square')" :dismissible="false">
        <template #body>
            <!-- Already claimed: what you submitted, what the host said, and
                 the one destructive action, behind a button rather than
                 behind a second click on the square.

                 Clicking a claimed square used to withdraw it outright —
                 no hover state saying so, no confirmation, so a stray click
                 quietly undid a claim and (on a reviewed card) sent its
                 place in the queue with it. -->
            <div v-if="claim" class="space-y-4 py-2">
                <div class="flex items-center gap-2">
                    <u-icon :name="statusIcon" class="size-5 shrink-0" :class="statusClass" />
                    <span class="font-medium" :class="statusClass">{{ $t(`bingo.status_${claim.status.toLowerCase()}`) }}</span>
                    <span v-if="claim.reviewedAt" class="text-xs text-muted">{{ reviewedAt }}</span>
                </div>

                <!-- Shown whatever the verdict. The note field is offered to
                     a host on an approval too, and only rejections ever
                     surfaced it — so a "nice one, that was quick" was
                     written and then discarded. -->
                <div v-if="claim.reviewNote" class="rounded-lg ring ring-default px-3 py-2">
                    <p class="text-xs font-medium text-muted uppercase tracking-wide mb-1">{{ $t('bingo.host_said') }}</p>
                    <p class="text-sm">{{ claim.reviewNote }}</p>
                </div>

                <div v-if="claim.note" class="rounded-lg ring ring-default px-3 py-2">
                    <p class="text-xs font-medium text-muted uppercase tracking-wide mb-1">{{ $t('bingo.your_note') }}</p>
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
                    :label="$t('bingo.view_proof')"
                />

                <!-- Says what withdrawing costs before it costs it — or,
                     once a host has ruled, why it is no longer on offer. A
                     red button that silently does nothing reads as broken,
                     and a reason that lives only in a title attribute is a
                     reason nobody on a touchscreen ever sees. -->
                <u-alert
                    :color="canWithdraw ? 'warning' : 'neutral'"
                    variant="subtle"
                    :icon="canWithdraw ? 'i-lucide-triangle-alert' : 'i-lucide-lock'"
                    :description="withdrawNotice"
                />
            </div>

            <div v-else class="space-y-4 py-2">
                <p class="text-sm text-muted">{{ $t('bingo.claim_intro') }}</p>

                <u-form-field :label="$t('bingo.proof_url')" :description="$t('bingo.proof_url_desc')" :error="form.errors.proof_url">
                    <u-input v-model="form.proof_url" class="w-full" placeholder="https://" />
                </u-form-field>

                <u-form-field :label="$t('bingo.claim_note')" :error="form.errors.note">
                    <u-input v-model="form.note" class="w-full" />
                </u-form-field>
            </div>
        </template>

        <template #footer>
            <div class="flex items-center justify-end gap-2 w-full">
                <!-- Nothing left to do but read it, so the way out stops
                     being a "cancel". -->
                <u-button
                    color="neutral"
                    variant="ghost"
                    :label="$t(claim && !canWithdraw ? 'common.close' : 'common.cancel')"
                    @click="isOpen = false"
                />

                <u-button
                    v-if="claim && canWithdraw"
                    color="error"
                    :loading="withdrawing"
                    icon="i-lucide-undo-2"
                    :label="$t('bingo.withdraw_claim')"
                    @click="withdraw"
                />

                <!-- v-if, not v-else: the withdraw button next to it is
                     conditional on more than "is there a claim", and an
                     else-branch would have offered "Submit claim" on a square
                     that already has one. -->
                <u-button
                    v-if="!claim"
                    color="primary"
                    :loading="form.processing"
                    :label="$t('bingo.submit_claim')"
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
 * One square's claim: making it, or looking at the one that is already there.
 *
 * A dialog rather than a bare click, in both directions. Claiming on a card
 * that requires approval is a submission to a queue, and the thing that makes
 * it reviewable is the screenshot — asked for at the only moment the player
 * still has it to hand. Withdrawing is destructive, and used to happen on an
 * unannounced second click.
 */
const props = defineProps({
    open: { type: Boolean, default: false },
    eventId: { type: String, required: true },
    square: { type: Object, default: null },
    // The existing claim for this square, or null to make a new one.
    claim: { type: Object, default: null },
});

const emit = defineEmits(['update:open']);

const isOpen = computed({ get: () => props.open, set: (v) => emit('update:open', v) });

const form = useForm({ proof_url: '', note: '' });
const withdrawing = ref(false);

watch(() => props.square, () => form.reset());

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
 * Undoing a host's decision is the host's call, not the claimant's — the
 * server refuses it too (BingoController::claim).
 */
const canWithdraw = computed(() => props.claim?.status === 'PENDING' || !props.claim?.reviewedAt);

/**
 * Either what withdrawing will cost, or why it is not on offer. The second
 * case is the one worth spelling out: the button is simply gone, and without
 * this the dialog would show a verdict and no account of why it is final.
 */
const withdrawNotice = computed(() => {
    if (!canWithdraw.value) return trans('bingo.already_reviewed');

    return trans(props.claim?.status === 'PENDING' ? 'bingo.withdraw_pending_warning' : 'bingo.withdraw_warning');
});

function submit() {
    form.post(`/events/${props.eventId}/bingo/squares/${props.square.id}/claim`, {
        preserveScroll: true,
        onSuccess: () => (isOpen.value = false),
    });
}

function withdraw() {
    withdrawing.value = true;

    // The same endpoint: posting a claim that already exists withdraws it.
    router.post(`/events/${props.eventId}/bingo/squares/${props.square.id}/claim`, {}, {
        preserveScroll: true,
        onSuccess: () => (isOpen.value = false),
        onError: (errors) => console.error(errors),
        onFinish: () => (withdrawing.value = false),
    });
}
</script>
