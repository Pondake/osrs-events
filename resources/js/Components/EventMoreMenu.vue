<template>
    <u-dropdown-menu :items="menuItems" :ui="{ content: 'w-56' }">
        <u-button
            color="neutral"
            variant="outline"
            size="sm"
            icon="i-lucide-ellipsis"
            :aria-label="$t('events.more_actions')"
            :title="$t('events.more_actions')"
        />
    </u-dropdown-menu>
</template>

<script setup>
import { computed } from 'vue';

/**
 * The player's occasional actions (leaving, showing other players' markers),
 * kept out of the header row so the actions everybody uses stay on one line
 * on a phone.
 */
const props = defineProps({
    // `[{ key, label, icon, color }]`
    items: { type: Array, required: true },
});

const emit = defineEmits(['select']);

const menuItems = computed(() => [props.items.map((item) => ({
    label: item.label,
    icon: item.icon,
    color: item.color,
    onSelect: () => emit('select', item.key),
}))]);
</script>
