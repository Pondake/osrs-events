<template>
    <seo-head :options="seo" />

    <u-main>
        <u-page>
            <u-container class="max-w-4xl py-12">
                <div class="mb-10">
                    <h1 class="text-3xl font-bold text-highlighted">{{ header.title }}</h1>
                    <p class="text-muted mt-2">{{ header.subtitle }}</p>
                </div>

                <!-- The page body is data, not markup: PageController sends a
                     block list and the renderer maps it onto Nuxt UI page
                     components. Hardcoded in the controller for now — see the
                     note there for why that's the deliberate first step. -->
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
