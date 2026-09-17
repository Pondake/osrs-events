<template>
    <u-modal
        v-model:open="isOpen"
        :title="$t('bingo.review_title')"
        :description="$t('bingo.review_desc')"
        :ui="{ content: 'max-w-2xl' }"
    >
        <template #body>
            <div v-if="!claims.length" class="py-12 text-center">
                <u-icon name="i-lucide-check-check" class="size-10 text-muted mx-auto mb-3" />
                <p class="text-sm text-muted">{{ $t('bingo.no_pending') }}</p>
            </div>

            <div v-else class="space-y-4">
                <!-- One claim at a time, with the screenshot at full width.
                     The queue used to sit in a narrow column beside the card
                     with the proof behind an "open in new tab" link, which
                     made judging a claim a two-window job. -->
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2 min-w-0">
                        <img v-if="claim.iconUrl" :src="claim.iconUrl" alt="" class="size-6 object-contain shrink-0" />
                        <div class="min-w-0">
                            <!-- "Soaked page ×25". The bar the square sets is
                                 part of what a host is judging against, and
                                 it applies to a screenshot exactly as it
                                 does to a plugin report. -->
                            <p class="font-medium truncate">
                                {{ claim.label || $t('bingo.empty_square') }}
                                <span v-if="claim.minQuantity > 1" class="text-muted tabular-nums">
                                    {{ $t('common.min_quantity_badge', { n: claim.minQuantity }) }}
                                </span>
                            </p>
                            <p class="text-xs text-muted flex items-center gap-2">
                                {{ $t('bingo.square_number', { n: claim.position + 1 }) }}
                                <claim-source-badge :via="claim.completedVia" />
                            </p>
                            <!-- One line under the square's own name, not an
                                 alert: it is a fact about what is being
                                 judged, not news about this claim. -->
                            <p v-if="claim.minQuantity > 1" class="text-xs text-muted truncate">
                                {{ $t('common.min_quantity_notice', { n: claim.minQuantity }) }}
                            </p>
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


                <!-- A claim already ruled on shows the verdict first, not a
                     fresh Approve/Reject pair — a host re-opening a settled
                     square needs to see what happened before being asked to
                     redo it. Changing the verdict is a deliberate second
                     step (the footer's "Change verdict" button), not the
                     default state of this dialog. -->
                <div v-if="isSettled && !changingVerdict" class="rounded-lg ring ring-default px-3 py-2 space-y-1">
                    <div class="flex items-center gap-2">
                        <u-icon :name="verdictIcon" class="size-4 shrink-0" :class="verdictClass" />
                        <span class="font-medium" :class="verdictClass">{{ $t(`bingo.status_${claim.status.toLowerCase()}`) }}</span>
                        <span v-if="claim.reviewedByName" class="text-xs text-muted">— {{ claim.reviewedByName }}</span>
                        <span v-if="claim.reviewedAt" class="text-xs text-muted ms-auto">{{ reviewedAt }}</span>
                    </div>
                    <p v-if="claim.reviewNote" class="text-sm text-muted">{{ claim.reviewNote }}</p>
                </div>

                <!-- Who is asking. Both identities side by side, because the
                     check a host performs is "does the name in this
                     screenshot belong to the person claiming it" — and that
                     needs the OSRS name next to the Discord one.

                     Not shown for a RuneLite claim — that identity now sits
                     inside the plugin card below, so a host is not reading
                     the same name twice in two different boxes. -->
                <div v-if="claim.completedVia !== 'RUNELITE'" class="flex items-center gap-3 rounded-lg bg-elevated px-3 py-2">
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

                <!-- Approving this one completes the card. Said before the
                     click, and — when two competitors are both one square
                     away — saying which of them got in first, because places
                     go by submission and not by the order a host works
                     through the queue. Same notice the tile review carries,
                     for the same reason. -->
                <u-alert
                    v-if="claim.winsCard"
                    icon="i-lucide-flag"
                    color="primary"
                    variant="subtle"
                    :title="$t('bingo.review_winning_claim')"
                    :description="claim.raceOrder
                        ? $t('bingo.review_race_order', { place: ordinal(claim.raceOrder), total: claim.raceTotal })
                        : $t('bingo.review_winning_desc')"
                />

                <p v-if="claim.note" class="text-sm text-muted rounded-lg ring ring-default px-3 py-2">{{ claim.note }}</p>

                <!-- The proof, inline. Claims carry a URL rather than an
                     upload (clans already post screenshots to Discord or
                     Imgur), so this is a remote image that may not load —
                     hence the fallback link rather than a broken frame. -->
                <div v-if="claim.proofUrl" class="rounded-lg ring ring-default overflow-hidden bg-elevated">
                    <img
                        v-if="!proofFailed"
                        :src="claim.proofUrl"
                        :alt="$t('bingo.view_proof')"
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
                            :label="$t('bingo.view_proof')"
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

                <!-- Everything the plugin claim is, in one card: who,
                     no-screenshot note, what was killed, the kill count, the
                     rest of the loot, when — instead of the claimant box,
                     a "no screenshot" alert and this card stacked on top of
                     each other, which used to be three separate reads for
                     one fact. -->
                <runelite-context-card
                    v-else-if="claim.completedVia === 'RUNELITE'"
                    :context="claim.runeliteContext"
                    :submitted-by="claim.submittedBy"
                    :submitted-by-avatar="claim.submittedByAvatar"
                    :submitted-by-osrs="claim.submittedByOsrs"
                    no-proof-notice
                />

                <u-alert
                    v-else
                    color="warning"
                    variant="subtle"
                    icon="i-lucide-image-off"
                    :description="$t('bingo.no_proof_desc')"
                />

                <u-form-field v-if="showingVerdictButtons" :label="$t('bingo.review_note')" :description="$t('bingo.review_note_desc')">
                    <u-input v-model="reviewNote" class="w-full" :placeholder="$t('bingo.review_note_placeholder')" />
                </u-form-field>
            </div>
        </template>

        <template #footer>
            <div class="flex items-center justify-between gap-2 w-full">
                <u-button color="neutral" variant="ghost" :label="$t('common.close')" @click="isOpen = false" />

                <div v-if="claims.length" class="flex items-center gap-2">
                    <template v-if="showingVerdictButtons">
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
                    </template>
                    <!-- A settled claim gets a deliberate second step before
                         a verdict changes, not the same two buttons shown as
                         if nothing had been decided yet. -->
                    <u-button
                        v-else
                        color="neutral"
                        variant="outline"
                        icon="i-lucide-rotate-ccw"
                        :label="$t('bingo.change_verdict')"
                        @click="changingVerdict = true"
                    />
                </div>
            </div>
        </template>
    </u-modal>
</template>

<script setup>
import ClaimSourceBadge from '@/Components/ClaimSourceBadge.vue';
import RuneliteContextCard from '@/Components/RuneliteContextCard.vue';
import { computed, ref, watch } from 'vue';
import { ordinal } from '@/Support/board';
import { router } from '@inertiajs/vue3';

/**
 * The host's review queue, as its own dialog.
 *
 * It used to be a card wedged into the sidebar of the event page, which put
 * an admin job in the middle of the thing everyone else came to look at —
 * and left the proof, the one part a host actually has to see, behind a link
 * to another tab. This is reachable from a button that carries the count, so
 * the page says how much is waiting without spending a column on it.
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
// A settled claim opens showing its verdict, not the buttons that would
// change it — this flips true once a host deliberately asks to change it.
const changingVerdict = ref(false);

// Clamped rather than assumed in range: ruling on the last claim shortens
// the list under the cursor, and the parent's reload replaces it wholesale.
const claim = computed(() => props.claims[Math.min(index.value, props.claims.length - 1)] ?? {});

// A claim reaching this dialog from the pending queue has no `status` at
// all (pendingQueue() only ever returns PENDING rows) — undefined is not
// APPROVED or REJECTED, so it falls through to the plain buttons exactly
// like an explicit PENDING would.
const isSettled = computed(() => ['APPROVED', 'REJECTED'].includes(claim.value.status));
const showingVerdictButtons = computed(() => !isSettled.value || changingVerdict.value);

const VERDICT_ICON = { APPROVED: 'i-lucide-circle-check', REJECTED: 'i-lucide-circle-x' };
const VERDICT_CLASS = { APPROVED: 'text-success', REJECTED: 'text-error' };
const verdictIcon = computed(() => VERDICT_ICON[claim.value.status] ?? 'i-lucide-circle-dot');
const verdictClass = computed(() => VERDICT_CLASS[claim.value.status] ?? 'text-muted');

watch(() => props.claims, () => {
    if (index.value > props.claims.length - 1) index.value = Math.max(0, props.claims.length - 1);
});

// A note belongs to one claim, and the screenshot to one URL — carrying
// either across to the next claim in the queue would attach a host's reason
// to the wrong person.
watch(index, () => {
    reviewNote.value = '';
    proofFailed.value = false;
    changingVerdict.value = false;
});

watch(() => props.open, (open) => {
    if (!open) return;

    index.value = 0;
    reviewNote.value = '';
    proofFailed.value = false;
    changingVerdict.value = false;
});

function review(status) {
    if (!claim.value.id) return;

    submitting.value = status;

    router.patch(`/events/${props.eventId}/bingo/claims/${claim.value.id}`, {
        status,
        review_note: reviewNote.value || null,
    }, {
        preserveScroll: true,
        // Stays open on purpose. A queue is worked through, and closing after
        // every verdict would mean reopening it for each of twenty claims.
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

const reviewedAt = computed(() => (
    claim.value.reviewedAt ? new Date(claim.value.reviewedAt).toLocaleString() : null
));
</script>
