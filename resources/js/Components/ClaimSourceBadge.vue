<template>
    <u-badge
        :color="isRunelite ? 'primary' : 'neutral'"
        variant="subtle"
        size="sm"
        :icon="isRunelite ? 'i-lucide-puzzle' : 'i-lucide-hand'"
        :label="$t(isRunelite ? 'board.completed_via_runelite' : 'board.completed_via_manual')"
    />
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    via: { type: String, default: 'MANUAL' },
});

const isRunelite = computed(() => props.via === 'RUNELITE');
</script>
