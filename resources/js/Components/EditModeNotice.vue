<!--
    "You are editing this", with the way out of it attached.

    One component for both formats. The two pages each had their own version
    of this — the board a pill with a Done button, the bingo card a line of
    small text you could only switch off again from the Manage menu — so the
    same mode announced itself differently and ended differently depending on
    which event you were looking at.
-->
<template>
    <div class="relative z-10 flex justify-start sm:justify-center mb-2" :class="overlap ? 'sm:-mb-4' : ''">
        <div class="inline-flex max-w-full items-center gap-2 rounded-full bg-default ring-1 ring-primary/50 shadow-sm py-1 pl-3 pr-1">
            <u-icon name="i-lucide-grid-2x2-plus" class="size-3.5 shrink-0 text-primary" />
            <span class="text-xs font-semibold truncate">{{ label }}</span>
            <!-- Round, and inset from the pill's own edge: a square-cornered
                 button flush against a fully rounded pill reads as two shapes
                 fighting rather than one control. -->
            <u-button
                size="xs"
                color="neutral"
                variant="solid"
                class="rounded-full shrink-0"
                icon="i-lucide-check"
                :label="$t('common.done_editing')"
                @click="emit('done')"
            />
        </div>
    </div>
</template>

<script setup>
defineProps({
    // What is being edited, in the words of the format: a board has tiles, a
    // card has squares.
    label: { type: String, required: true },
    // Sits the pill down into the top of whatever follows it. The board has a
    // bordered parchment to dip into; a bingo grid has no edge to overlap, so
    // there the pill stays above it.
    overlap: { type: Boolean, default: false },
});

const emit = defineEmits(['done']);
</script>
