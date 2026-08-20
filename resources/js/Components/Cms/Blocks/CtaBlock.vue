<template>
    <u-page-c-t-a
        :title="title ?? undefined"
        :description="description ?? undefined"
        variant="subtle"
    >
        <template v-if="usableLinks.length" #links>
            <u-button
                v-for="(link, index) in usableLinks"
                :key="index"
                :to="link.to"
                :label="link.label"
                :icon="link.icon ?? undefined"
                :color="link.color"
                :variant="link.variant"
            />
        </template>
    </u-page-c-t-a>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, default: null },
    description: { type: String, default: null },
    links: { type: Array, default: () => [] },
});

const usableLinks = computed(() => props.links.filter((link) => link.to && link.label));
</script>
