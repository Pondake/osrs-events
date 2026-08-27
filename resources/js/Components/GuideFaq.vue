<template>
    <div>
        <details v-for="faq in faqs" :key="faq.question" class="group" :class="prose.faqRow" open>
            <summary class="flex items-center justify-between gap-3 cursor-pointer list-none marker:content-none" :class="prose.faqQuestion">
                <span>{{ faq.question }}</span>
                <u-icon name="i-lucide-chevron-down" class="size-4 text-muted shrink-0 transition-transform group-open:rotate-180" />
            </summary>
            <p :class="prose.faqAnswer">{{ faq.answer }}</p>
        </details>
    </div>
</template>

<script setup>
import { GUIDE_PROSE } from '@/Support/guides';

// Native <details>/<summary>, not a u-accordion: it needs no JS to work,
// nothing to wrap in <client-only> for SSR, and it's foldable for free —
// clicking a question collapses it, `open` just means every question
// starts expanded rather than hidden behind a click on a guide page whose
// whole point is to be read.
defineProps({
    faqs: { type: Array, required: true },
});

const prose = GUIDE_PROSE;
</script>
