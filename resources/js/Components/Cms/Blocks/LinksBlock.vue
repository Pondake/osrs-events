<template>
    <div v-if="usableLinks.length" class="flex flex-wrap gap-2">
        <u-button
            v-for="(link, index) in usableLinks"
            :key="index"
            :to="link.to"
            :target="isExternal(link.to) ? '_blank' : undefined"
            :rel="isExternal(link.to) ? 'noopener noreferrer' : undefined"
            :label="link.label"
            :icon="link.icon ?? undefined"
            :color="link.color"
            :variant="link.variant"
            size="sm"
        />
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { isExternal } from '@/Support/richtext';

/**
 * A row of buttons — the "read more" under a paragraph.
 *
 * Distinct from the `cta` block on purpose: u-page-cta draws a full panel
 * built around its own title and description, which is the wrong element
 * for a single inline link and renders as a large empty card without them.
 */
const props = defineProps({
    links: { type: Array, default: () => [] },
});

// A link whose URL failed validation has `to: null`. Dropping it beats
// rendering a button that goes nowhere.
const usableLinks = computed(() => props.links.filter((link) => link.to && link.label));
</script>
