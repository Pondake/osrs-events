<template>
    <p v-if="text" class="text-muted leading-relaxed">
        <rich-text :text="text" />
    </p>
</template>

<script setup>
import RichText from '@/Components/RichText.vue';

// Through RichText, never v-html — this copy is the most likely block to be
// authored freely, so it is the one that most needs the token renderer.
defineProps({
    text: { type: String, default: null },
});
</script>
