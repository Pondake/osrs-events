<template>
<u-main>
    <u-container>
        <u-page-header v-if="title" class="mb-8" :title="title" :description="description || ''">
            <template #links>
                <div class="flex gap-2 shrink-0 flex-wrap">
                    <slot name="links" />
                </div>
            </template> 
        </u-page-header>
    </u-container>
        <u-page>
        <slot />
    </u-page />
</u-main>
</template>

<script setup lang="ts">
const props = defineProps<{
  title?: string
  description?: string
}>()
</script> 