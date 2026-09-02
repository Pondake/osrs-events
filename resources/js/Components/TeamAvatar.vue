<template>
    <u-avatar :src="src ?? undefined" :alt="name" :size="size" />
</template>

<script setup>
import { computed } from 'vue';

/**
 * A team's avatar, with the fallback chain in one place.
 *
 * Own icon first, then the linked Discord server's, then the initials
 * `UAvatar` derives from `alt` on its own. The third step is why `alt` is
 * always passed rather than left off: without it the component renders an
 * empty box, which is what this replaces.
 *
 * Both icon props are optional and accept either casing's absence — the
 * payloads on this site are snake_case on the boards pages and camelCase on
 * the events pages, and the caller maps whichever it has.
 */
const props = defineProps({
    name: { type: String, required: true },
    iconUrl: { type: String, default: null },
    guildIconUrl: { type: String, default: null },
    size: { type: String, default: 'sm' },
});

const src = computed(() => props.iconUrl || props.guildIconUrl || null);
</script>
