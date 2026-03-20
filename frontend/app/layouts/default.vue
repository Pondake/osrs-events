<template>
  <u-main>
    <u-page>
      <u-container>
        <u-page-header
          v-if="title"
          :title="title"
          :description="description || ''"
          class="mb-2"
        >
          <template #links>
            <div class="flex gap-2 shrink-0 flex-wrap">
              <slot name="links" />
            </div>
          </template>
        </u-page-header>
      </u-container>

      <slot />
    </u-page>
  </u-main>
</template>

<script setup lang="ts">
defineProps<{
  title?: string
  description?: string
}>()
</script>
