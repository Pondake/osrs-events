<template>
    <u-modal
        v-model:open="isOpen"
        :title="$t('board.review_title')"
        :description="$t('board.review_desc')"
        :ui="{ content: 'max-w-2xl' }"
    >
        <template #body>
            <div v-if="!claims.length" class="py-12 text-center">
                <u-icon name="i-lucide-check-check" class="size-10 text-muted mx-auto mb-3" />
                <p class="text-sm text-muted">{{ $t('board.no_pending') }}</p>
            </div>

            <div v-else class="space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2 min-w-0">
                        <img v-if="claim.iconUrl" :src="claim.iconUrl" alt="" class="size-6 object-contain shrink-0" />
                        <div class="min-w-0">
                            <p class="font-medium truncate">{{ claim.label || $t('tile_editor.no_task') }}</p>
                            <p class="text-xs text-muted">{{ $t('board.tile', { n: claim.position + 1 }) }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-1 shrink-0">
                        <u-button
                            icon="i-lucide-chevron-left"
                            size="xs"
                            color="neutral"
                            variant="ghost"
                            :disabled="index === 0"
                            :aria-label="$t('bingo.review_previous')"
                            @click="index--"
                        />
                        <span class="text-xs text-muted tabular-nums px-1">{{ index + 1 }} / {{ claims.length }}</span>
                        <u-button
                            icon="i-lucide-chevron-right"
                            size="xs"
                            color="neutral"
                            variant="ghost"
                            :disabled="index >= claims.length - 1"
                            :aria-label="$t('bingo.review_next')"
                            @click="index++"
                        />
                    </div>
                </div>

                <div class="flex items-center gap-3 rounded-lg bg-elevated px-3 py-2">
                    <u-avatar :src="claim.submittedByAvatar ?? claim.competitorAvatar ?? undefined" :alt="claim.submittedBy ?? claim.competitor ?? ''" size="sm" />
                    <div class="min-w-0 text-sm">
                        <p class="truncate">
                            {{ claim.submittedBy ?? $t('common.unknown') }}
                            <span v-if="claim.competitor && claim.competitor !== claim.submittedBy" class="text-muted">
                                · {{ claim.competitor }}
                            </span>
                        </p>
                        <p class="text-xs text-muted truncate">
                            <span v-if="claim.submittedByOsrs" class="inline-flex items-center gap-1">
                                <u-icon name="i-lucide-user-round" class="size-3" />
                                {{ claim.submittedByOsrs }}
                            </span>
                            <span v-else class="italic">{{ $t('bingo.no_osrs_name') }}</span>
                            <span v-if="claim.submittedAt"> · {{ submittedAt }}</span>
                        </p>
                    </div>
                </div>

                <!-- This claim ends somebody's run, and possibly the whole
                     event. Said before the click rather than after it.

                     The second line is the one that matters when two people
                     get home minutes apart and both send a screenshot: the
                     podium is ordered by when a claim was SUBMITTED, so the
                     order these are approved in cannot change who won. A host
                     who cannot see that will assume the opposite and agonise
                     over a decision they are not actually making. -->
                <u-alert
                    v-if="claim.finishesBoard"
                    icon="i-lucide-flag"
                    color="primary"
                    variant="subtle"
                    :title="$t('board.review_finishing_claim')"
                    :description="claim.raceOrder
                        ? $t('board.review_race_order', { place: ordinal(claim.raceOrder), total: claim.raceTotal })
                        : $t('board.review_finishing_desc')"
                />

                <p v-if="claim.note" class="text-sm text-muted rounded-lg ring ring-default px-3 py-2">{{ claim.note }}</p>

                <div v-if="claim.proofUrl" class="rounded-lg ring ring-default overflow-hidden bg-elevated">
                    <img
                        v-if="!proofFailed"
                        :src="claim.proofUrl"
                        :alt="$t('board.view_proof')"
                        class="w-full max-h-96 object-contain bg-black/20"
                        @error="proofFailed = true"
                    />
                    <div v-else class="px-3 py-8 text-center space-y-2">
                        <p class="text-sm text-muted">{{ $t('bingo.proof_not_embeddable') }}</p>
                        <u-button
                            :href="claim.proofUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            size="xs"
                            color="neutral"
                            variant="outline"
                            icon="i-lucide-external-link"
                            :label="$t('board.view_proof')"
                        />
                    </div>

                    <a
                        v-if="!proofFailed"
                        :href="claim.proofUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="flex items-center gap-1.5 px-3 py-2 text-xs text-muted hover:text-primary transition-colors"
                    >
                        <u-icon name="i-lucide-external-link" class="size-3.5" />
                        {{ $t('bingo.open_proof_full') }}
                    </a>
                </div>

                <u-alert
                    v-else
                    color="warning"
                    variant="subtle"
                    icon="i-lucide-image-off"
                    :description="$t('bingo.no_proof_desc')"
                />

                <u-form-field :label="$t('bingo.review_note')" :description="$t('bingo.review_note_desc')">
                    <u-input v-model="reviewNote" class="w-full" :placeholder="$t('bingo.review_note_placeholder')" />
                </u-form-field>
            </div>
        </template>

        <template #footer>
            <div class="flex items-center justify-between gap-2 w-full">
                <u-button color="neutral" variant="ghost" :label="$t('common.close')" @click="isOpen = false" />

                <div v-if="claims.length" class="flex items-center gap-2">
                    <u-button
                        color="error"
                        variant="soft"
                        icon="i-lucide-x"
                        :label="$t('bingo.reject')"
                        :loading="submitting === 'REJECTED'"
                        @click="review('REJECTED')"
                    />
                    <u-button
                        color="success"
                        icon="i-lucide-check"
                        :label="$t('bingo.approve')"
                        :loading="submitting === 'APPROVED'"
                        @click="review('APPROVED')"
                    />
                </div>
            </div>
        </template>
    </u-modal>
</template>

<script setup>
import { ordinal } from '@/Support/board';
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * The host's review queue for tile claims — mirrors BingoReviewModal.vue.
 * See docs/backlog.md, "Snakes & Ladders task tiles have no claim/approve
 * flow": same reasoning, same shape, the other board type.
 */
const props = defineProps({
    open: { type: Boolean, default: false },
    eventId: { type: String, required: true },
    claims: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:open']);

const isOpen = computed({ get: () => props.open, set: (v) => emit('update:open', v) });

const index = ref(0);
const reviewNote = ref('');
const proofFailed = ref(false);
const submitting = ref(null);

const claim = computed(() => props.claims[Math.min(index.value, props.claims.length - 1)] ?? {});

watch(() => props.claims, () => {
    if (index.value > props.claims.length - 1) index.value = Math.max(0, props.claims.length - 1);
});

watch(index, () => {
    reviewNote.value = '';
    proofFailed.value = false;
});

watch(() => props.open, (open) => {
    if (!open) return;

    index.value = 0;
    reviewNote.value = '';
    proofFailed.value = false;
});

function review(status) {
    if (!claim.value.id) return;

    submitting.value = status;

    router.patch(`/events/${props.eventId}/tiles/completions/${claim.value.id}`, {
        status,
        review_note: reviewNote.value || null,
    }, {
        preserveScroll: true,
        // Stays open on purpose — a queue is worked through.
        onSuccess: () => {
            reviewNote.value = '';
            proofFailed.value = false;
        },
        onError: (errors) => console.error(errors),
        onFinish: () => (submitting.value = null),
    });
}

const submittedAt = computed(() => (
    claim.value.submittedAt ? new Date(claim.value.submittedAt).toLocaleString() : null
));
</script>
