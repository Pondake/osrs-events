<template>
  <u-app
    :toaster="{
      position: 'top-center',
      expand: true,
      class: 'top-[calc(var(--ui-header-height)+var(--spacing)*6)]',
    }"
  >
    <app-header />

    <nuxt-page />

    <app-footer />
  </u-app>
</template>

<script setup lang="ts">
useHead({
  // Pages set a bare title via useSeo; the brand is appended here so it is not
  // repeated in every page's copy. Titles that already carry the brand (the
  // home page) are left alone rather than doubling it up.
  titleTemplate: title =>
    !title ? 'OSRS Events' : title.includes('OSRS Events') ? title : `${title} · OSRS Events`,
  meta: [{ name: 'viewport', content: 'width=device-width, initial-scale=1' }],
  // Icon links live in nuxt.config.ts app.head — keep one source of truth.
  htmlAttrs: { lang: 'en' },
});
</script>
