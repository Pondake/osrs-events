<template>
    <!-- Renders parseInline()'s tokens as real elements. A component rather
         than a helper returning a string because the whole point is that
         nothing here is ever v-html'd — see Support/richtext.js. -->
    <span>
        <template v-for="(token, index) in tokens" :key="index">
            <a
                v-if="token.type === 'link'"
                :href="token.href"
                :target="isExternal(token.href) ? '_blank' : undefined"
                :rel="isExternal(token.href) ? 'noopener noreferrer' : undefined"
                class="font-medium underline underline-offset-2 hover:no-underline"
            >{{ token.value }}</a>
            <strong v-else-if="token.type === 'bold'" class="font-semibold">{{ token.value }}</strong>
            <template v-else>{{ token.value }}</template>
        </template>
    </span>
</template>

<script setup>
import { computed } from 'vue';
import { isExternal, parseInline } from '@/Support/richtext';

const props = defineProps({
    text: { type: String, default: '' },
});

const tokens = computed(() => parseInline(props.text));
</script>
