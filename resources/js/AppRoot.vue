<template>
    <u-app>
        <component :is="page" v-bind="pageProps" />
    </u-app>
</template>

<script setup>
defineProps({
    page: Object,
    pageProps: Object,
});
</script>
