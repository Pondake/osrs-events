<template>
    <u-page-hero :title="title ?? undefined" :description="description ?? undefined">
        <template v-if="usableLinks.length" #links>
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
                size="lg"
            />
        </template>
    </u-page-hero>
</template>

<script setup>
import { computed } from 'vue';
import { isExternal } from '@/Support/richtext';

// Props are declared explicitly rather than accepted as attrs: this is the
// second line of defence behind Cms/blocks.js's schema, and it means an
// unexpected key lands nowhere instead of falling through onto the root.
const props = defineProps({
    title: { type: String, default: null },
    description: { type: String, default: null },
    links: { type: Array, default: () => [] },
});

// A link whose URL failed validation has `to: null`. Dropping it is better
// than rendering a button that goes nowhere.
const usableLinks = computed(() => props.links.filter((link) => link.to && link.label));
</script>
