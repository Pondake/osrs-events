<template>
    <!-- :title as a Head PROP, not a literal <title> child — the latter
         double-applies against createInertiaApp's global title callback
         during client hydration ("X - X - OSRS Events"). See useSeo.js. -->
    <Head :title="resolved.title">
        <meta name="description" :content="resolved.description" />
        <link rel="canonical" :href="canonical" />
        <meta name="robots" :content="robots" />

        <meta property="og:title" :content="resolved.title" />
        <meta property="og:description" :content="resolved.description" />
        <meta property="og:type" :content="resolved.ogType ?? 'website'" />
        <meta property="og:url" :content="canonical" />
        <meta property="og:site_name" content="OSRS Events" />
        <meta property="og:image" :content="imageUrl" />
        <meta property="og:image:width" content="1200" />
        <meta property="og:image:height" content="630" />
        <meta property="og:image:alt" :content="resolved.title" />

        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="resolved.title" />
        <meta name="twitter:description" :content="resolved.description" />
        <meta name="twitter:image" :content="imageUrl" />

        <!-- JSON-LD is deliberately NOT emitted here — putting it through
             Inertia's <Head> renders it as an innerHTML ATTRIBUTE during
             SSR, not as the tag's text content, so crawlers reading raw
             pre-hydration HTML never see it. It goes through the controller
             instead: View::share('jsonLd', ...) plus app.blade.php. See
             LandingController for the full explanation. -->
    </Head>
</template>

<script setup>
import { useSeoData } from '@/Composables/useSeo';

/**
 * The full meta block every indexable page should carry, in one place.
 * Before this existed each page hand-rolled its own <Head>, and only
 * SnakesAndLadders had the complete set — /osrs-clan-events and
 * /osrs-event-ideas shipped with zero og:/twitter: tags, so sharing either
 * one produced a bare link with no preview card.
 */
const props = defineProps({
    // { title, description, image?, noindex?, ogType? }
    options: { type: Object, required: true },
});

const { resolved, canonical, imageUrl, robots, Head } = useSeoData(() => props.options);
</script>
