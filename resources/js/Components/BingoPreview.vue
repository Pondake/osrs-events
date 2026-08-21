<template>
    <!-- A read-only shape of the card, the way BoardPreview is for a Snakes
         & Ladders grid. A bingo row on /my-events had nothing beside it while
         its neighbour had a whole board drawn, which made the same kind of
         event read as less of one.

         No labels, no icons: at this size they would be unreadable, and what
         the preview is for is the shape and how far along it is. -->
    <div
        class="grid gap-1 rounded-lg ring ring-default bg-default p-2"
        :style="{ gridTemplateColumns: `repeat(${size}, minmax(0, 1fr))` }"
        role="img"
        :aria-label="$t('bingo.preview_alt', { done: completed.length, total: size * size })"
    >
        <span
            v-for="position in size * size"
            :key="position"
            class="aspect-square rounded-sm ring-1"
            :class="done.has(position - 1)
                ? 'bg-success/30 ring-success/50'
                : 'bg-elevated ring-default'"
        />
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    size: { type: Number, required: true },
    // Positions already approved for this viewer — zero-based, matching the
    // card itself.
    completed: { type: Array, default: () => [] },
});

// A Set because a 10x10 card asks this question a hundred times per render.
const done = computed(() => new Set(props.completed));
</script>
