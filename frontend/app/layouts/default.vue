<template>
  <u-main>
    <u-page>
      <u-container>
        <u-page-header v-if="title !== undefined" class="mb-2">
          <template #title>
            <u-skeleton v-if="pending" class="h-6 w-48" />

            <span v-else>{{ title }}</span>
          </template>

          <template v-if="pending || description" #description>
            <u-skeleton v-if="pending" class="h-4 w-72" />

            <span v-else>{{ description || '' }}</span>
          </template>

          <template #links>
            <div class="flex gap-2 shrink-0 flex-wrap">
              <template v-if="pending">
                <u-skeleton class="h-8 w-20" />

                <u-skeleton class="h-8 w-24" />
              </template>

              <slot v-else name="links" />
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
  title?: string;
  description?: string;
  pending?: boolean;
}>();
</script>
