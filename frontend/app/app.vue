<template>
  <u-app :toaster="{ position: 'top-center', expand: true, class: 'top-[calc(var(--ui-header-height)+var(--spacing)*6)]' }">
    <app-header />

    <nuxt-page />

    <app-footer />
  </u-app>
</template>

<script setup lang="ts">
useHead({
  meta: [{ name: 'viewport', content: 'width=device-width, initial-scale=1' }],
  link: [{ rel: 'icon', href: '/favicon.ico' }],
  htmlAttrs: { lang: 'en' },
});

useSeoMeta({
  title: 'OSRS Events',
  description:
    'An Old School RuneScape themed events platform. Join boards, roll dice and complete OSRS tasks with your clan.',
  ogTitle: 'OSRS Events',
  ogDescription:
    'An Old School RuneScape themed events platform. Join boards, roll dice and complete OSRS tasks with your clan.',
});

</script>
