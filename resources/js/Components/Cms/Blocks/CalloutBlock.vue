<template>
    <u-alert
        :color="color"
        variant="subtle"
        :icon="icon ?? undefined"
        :title="title ?? undefined"
        :description="description ?? undefined"
    />
</template>

<script setup>
defineProps({
    title: { type: String, default: null },
    description: { type: String, default: null },
    icon: { type: String, default: null },
    color: { type: String, default: 'primary' },
});
</script>
