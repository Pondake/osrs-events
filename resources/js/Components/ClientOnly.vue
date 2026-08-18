<!--
    Renders its default slot only after the component has mounted in the
    browser — never during SSR. Nuxt ships this as a built-in; outside Nuxt
    it's this ~10-line pattern instead.

    Exists specifically to keep @nuxt/ui's interactive form components
    (u-select, u-switch, u-modal, u-tabs — anything that reaches
    useComponentIcons.js, see vite.config.js's comment on ssr.noExternal) out
    of the SSR render entirely. They're wrapped around BoardSettingsModal,
    which starts closed on every page anyway (v-model:open is false until a
    user clicks a button) — there is no SEO/crawler value in server-rendering
    a closed modal's form fields, so excluding it from SSR costs nothing and
    sidesteps a real SSR-breaking bug in the bundler-vs-@nuxt/ui interaction.
-->
<template>
    <slot v-if="mounted" />
</template>

<script setup>
import { onMounted, ref } from 'vue';

const mounted = ref(false);
onMounted(() => {
    mounted.value = true;
});
</script>
