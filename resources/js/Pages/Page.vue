<template>
    <seo-head :options="seo" />

    <u-main>
        <u-page>
            <u-container class="max-w-4xl py-12">
                <div class="mb-10">
                    <h1 class="text-3xl font-bold text-highlighted">{{ header.title }}</h1>
                    <p v-if="header.subtitle" class="text-muted mt-2">{{ header.subtitle }}</p>
                </div>

                <!-- The page body is data, not markup: the block list comes
                     from the `pages` table and the renderer maps it onto
                     Nuxt UI page components. Nothing here knows which page
                     it is — the slug decides that server-side. -->
                <page-renderer :blocks="blocks" />
            </u-container>
        </u-page>
    </u-main>
</template>

<script setup>
import SeoHead from '@/Components/SeoHead.vue';
import PageRenderer from '@/Components/Cms/PageRenderer.vue';

defineProps({
    seo: { type: Object, required: true },
    header: { type: Object, required: true },
    blocks: { type: Array, default: () => [] },
});
</script>
