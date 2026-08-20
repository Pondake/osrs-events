<template>
    <figure v-if="src" :class="widthClass">
        <img
            :src="src"
            :alt="alt ?? ''"
            class="w-full h-auto"
            :class="rounded ? 'rounded-lg' : ''"
            loading="lazy"
            decoding="async"
        />
        <figcaption v-if="caption" class="text-xs text-muted mt-2 text-center">{{ caption }}</figcaption>
    </figure>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    src: { type: String, default: null },
    alt: { type: String, default: null },
    caption: { type: String, default: null },
    width: { type: String, default: 'full' },
    rounded: { type: Boolean, default: true },
});

/**
 * alt falls back to an empty string rather than being omitted. An <img> with
 * no alt attribute at all is announced by screen readers as its filename;
 * alt="" marks it as decorative, which is the honest default for a picture
 * an author didn't describe.
 *
 * loading="lazy" because these are stored URLs — often remote, often below
 * the fold, and never worth blocking first paint for.
 */

// Written out per option: Tailwind scans source text, so an interpolated
// max-w-${width} is never generated.
const WIDTH_CLASS = {
    full: 'w-full',
    wide: 'max-w-2xl mx-auto',
    narrow: 'max-w-sm mx-auto',
};

const widthClass = computed(() => WIDTH_CLASS[props.width] ?? WIDTH_CLASS.full);
</script>
