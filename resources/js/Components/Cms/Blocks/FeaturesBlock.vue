<template>
    <div class="grid gap-4" :class="columnClass">
        <u-page-card
            v-for="(item, index) in usableItems"
            :key="index"
            :title="item.title ?? undefined"
            :description="item.description ?? undefined"
            :icon="item.icon ?? undefined"
            :to="item.to ?? undefined"
        />
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    columns: { type: Number, default: 3 },
    items: { type: Array, default: () => [] },
});

// Written out per column count rather than built as `lg:grid-cols-${n}`:
// Tailwind scans source text, so an interpolated class is never generated
// and the grid would silently collapse to one column.
const COLUMN_CLASS = {
    2: 'grid-cols-1 sm:grid-cols-2',
    3: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
    4: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
};

const columnClass = computed(() => COLUMN_CLASS[props.columns] ?? COLUMN_CLASS[3]);

// A card with neither title nor description is an empty box on the page.
const usableItems = computed(() => props.items.filter((item) => item.title || item.description));
</script>
