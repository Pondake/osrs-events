<template>
    <template v-for="(entry, index) in resolved" :key="index">
        <component :is="entry.component" v-bind="entry.props">
            <!-- Self-reference by filename rather than importing PageRenderer
                 here: importing it would be a cycle (renderer → blocks.js →
                 SectionBlock → renderer). Vue resolves an SFC's own name in
                 its template, so container blocks nest without one. -->
            <page-renderer v-if="entry.children.length" :blocks="entry.children" :depth="depth + 1" />
        </component>
    </template>
</template>

<script setup>
import { computed } from 'vue';
import { resolveBlock } from './blocks';

/**
 * Renders a stored block list. The list is data — eventually rows from the
 * CMS — so everything about what may reach a component is decided in
 * blocks.js, not here. This file only walks the list.
 */
const props = defineProps({
    blocks: { type: Array, default: () => [] },
    depth: { type: Number, default: 0 },
});

// Guards against a stored document that nests into itself — without a stop,
// that's a render loop which hangs the browser AND the SSR process.
// Children render one level deeper than their container, so this allows four
// nested containers and drops whatever the fifth would hold. Verified by
// feeding the renderer a five-deep section chain.
const MAX_DEPTH = 3;

// A page is a handful of blocks; a five-figure list is corrupt data, not a
// long page. Capped rather than trusted for the same reason listOf() is.
const MAX_BLOCKS = 200;

const resolved = computed(() => {
    if (props.depth > MAX_DEPTH) return [];

    // Unknown types drop out here — resolveBlock returns null for them, so a
    // page authored against a newer deploy renders the blocks this one
    // understands instead of failing whole.
    return props.blocks
        .slice(0, MAX_BLOCKS)
        .map((block) => resolveBlock(block))
        .filter(Boolean);
});
</script>
