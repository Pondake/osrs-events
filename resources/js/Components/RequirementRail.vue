<template>
    <!-- Decorative: every number here is also in the button's own title and
         aria-label, and in full sentences in the claim dialog. A screen
         reader reading "2 / 5 × 25" out of a rail would be worse than the
         sentence it already gets. -->
    <span
        v-if="shown"
        class="absolute inset-x-0 bottom-0 flex items-center justify-center gap-1 overflow-hidden border-t border-default bg-elevated/95 h-3 sm:h-4 px-0.5"
        aria-hidden="true"
    >
        <!-- The fill is the "how many times" half of the rail, and it is the
             half that does not need reading: a rail that is a third full is
             a third of the way there at any size the board is rendered at.
             The number on top of it is the same fact for anyone who wants
             it exactly. -->
        <span
            v-if="counted"
            class="absolute inset-y-0 left-0 bg-primary/25"
            :style="{ width: `${fillPercent}%` }"
        />

        <!-- Both numbers at once do not fit a small square, and half of
             "0 / 10 ×100" clipped is worse than neither: under 64px the
             count falls back to the fill behind it, which says the same
             thing without asking for room.
             A container query, not a breakpoint: what decides this is how
             wide the square is — a 3x3 card on a phone has wider squares
             than a 9x9 one on a desktop. -->
        <span
            v-if="counted"
            class="relative items-center text-muted tabular-nums font-semibold leading-none text-[9px] sm:text-[10px] whitespace-nowrap"
            :class="perDrop ? 'hidden @min-[64px]:inline-flex' : 'inline-flex'"
        >
            {{ $t('common.progress_badge', { done: clampedProgress, total: requiredCount }) }}
        </span>

        <!-- "×25" keeps the multiplication sign it has always had: the two
             requirements have to be told apart at a glance on a 33px square,
             and a bar that fills versus a × is a difference in shape, not a
             difference in label. The icons that named the two live in the
             legend instead — on the square they cost more room than the
             numbers do, and at 72px they were what pushed "0 / 4 ×100" over
             the edge. -->
        <span v-if="perDrop" class="relative inline-flex items-center text-muted tabular-nums font-semibold leading-none text-[9px] sm:text-[10px] whitespace-nowrap">
            {{ $t('common.min_quantity_badge', { n: minQuantity }) }}
        </span>
    </span>
</template>

<script setup>
import { computed } from 'vue';

/**
 * What a square or tile asks for, in one strip along its bottom edge.
 *
 * Two different requirements landed in the corners of the same square and
 * fought the title, the points and the claimant's face for the room. They
 * live here instead: one place, same order, on both board types, and the
 * square's own content keeps the middle.
 */
const props = defineProps({
    // "Do this N times." 1 means the first one claims it.
    requiredCount: { type: Number, default: 1 },
    // "One drop of at least N." 1 accepts any amount.
    minQuantity: { type: Number, default: 1 },
    // Qualifying reports so far, for this competitor.
    progress: { type: Number, default: 0 },
});

const counted = computed(() => props.requiredCount > 1);
const perDrop = computed(() => props.minQuantity > 1);
const shown = computed(() => counted.value || perDrop.value);

const clampedProgress = computed(() => Math.max(0, Math.min(props.progress ?? 0, props.requiredCount)));
const fillPercent = computed(() => (clampedProgress.value / props.requiredCount) * 100);
</script>
