<template>
    <!-- Renders parseAnnouncement()'s tokens as real elements. Kept as a
         component rather than a helper returning a string because the whole
         point is that nothing here is ever v-html'd — see Support/announcement.js. -->
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
import { parseAnnouncement } from '@/Support/announcement';

const props = defineProps({
    text: { type: String, default: '' },
});

const tokens = computed(() => parseAnnouncement(props.text));

// Site-relative links stay in the tab; anything off-site opens a new one so
// a banner pointing at Discord doesn't navigate the user out of the app.
function isExternal(href) {
    return !href.startsWith('/');
}
</script>
