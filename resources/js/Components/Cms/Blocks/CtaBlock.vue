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
                :target="isExternal(link.to) ? '_blank' : undefined"
                :rel="isExternal(link.to) ? 'noopener noreferrer' : undefined"
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
import { isExternal } from '@/Support/richtext';

const props = defineProps({
    title: { type: String, default: null },
    description: { type: String, default: null },
    links: { type: Array, default: () => [] },
});

const usableLinks = computed(() => props.links.filter((link) => link.to && link.label));
</script>
