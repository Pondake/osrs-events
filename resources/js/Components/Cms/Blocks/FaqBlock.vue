<template>
    <div v-if="items.length" class="space-y-6">
        <!-- Plain headings and paragraphs rather than an accordion. Two
             reasons, both about this block's actual job: the answers are the
             page's FAQPage structured data, so they must be in the served
             HTML rather than behind a click, and Nuxt UI's interactive
             components are the SSR hazard the backlog catalogues. -->
        <div v-for="(item, index) in items" :key="index">
            <h3 class="font-semibold text-highlighted">{{ item.question }}</h3>
            <p class="text-muted leading-relaxed mt-1">
                <rich-text :text="item.answer" />
            </p>
        </div>
    </div>
</template>

<script setup>
import RichText from '@/Components/RichText.vue';

defineProps({
    items: { type: Array, default: () => [] },
});
</script>
