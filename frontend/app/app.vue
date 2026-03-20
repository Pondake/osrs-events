<template>
  <u-app :toaster="{ position: 'top-center', expand: true }">
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
  title: 'OSRS Snakes & Ladders',
  description: 'An Old School RuneScape themed Snakes & Ladders bingo board for your clan.',
  ogTitle: 'OSRS Snakes & Ladders',
  ogDescription: 'An Old School RuneScape themed Snakes & Ladders bingo board for your clan.',
});
</script>
