<template>
    <component :is="ordered ? 'ol' : 'ul'" v-if="items.length" :class="listClass">
        <li v-for="(item, index) in items" :key="index" class="text-muted leading-relaxed">
            <rich-text :text="item.text" />
        </li>
    </component>
</template>

<script setup>
import { computed } from 'vue';
import RichText from '@/Components/RichText.vue';

/**
 * A bulleted or numbered list.
 *
 * Added because the prose block renders exactly one paragraph, and any real
 * content page — a policy, a set of rules, a "what we collect" section —
 * is mostly lists. Writing them as prose with dashes would have looked like
 * a list without being one, which is worse for anything read by a screen
 * reader.
 *
 * Item text goes through the same inline parser as prose (links and bold,
 * token-rendered, never v-html), so a list entry can carry a link without
 * the block needing its own escape hatch.
 */
const props = defineProps({
    items: { type: Array, default: () => [] },
    ordered: { type: Boolean, default: false },
});

// Written out per branch rather than interpolated: Tailwind scans source
// text, so a built-up class name is never generated.
const listClass = computed(() => (props.ordered
    ? 'list-decimal ps-5 space-y-1.5 marker:text-muted'
    : 'list-disc ps-5 space-y-1.5 marker:text-muted'));
</script>
