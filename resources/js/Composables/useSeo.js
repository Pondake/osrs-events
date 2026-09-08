import { computed } from 'vue';
import { usePage, Head } from '@inertiajs/vue3';

const SITE_URL = 'https://osrs-events.com';
const SITE_NAME = 'OSRS Events';
const DEFAULT_OG_IMAGE = '/og-image.png';

/**
 * Inertia equivalent of frontend/app/composables/useSeo.ts. Same contract —
 * title, description, image, noindex, ogType, jsonLd — but instead of Nuxt's
 * useSeoMeta()/useHead() composables (which mutate a shared head singleton
 * outside the render tree), this returns a render function that emits an
 * Inertia <Head> block. Head's tag-diffing during renderToString is what
 * actually gets these tags into the SSR HTML — the same job @inertiaHead
 * does in resources/views/app.blade.php on the Laravel side.
 *
 * Usage in a page's <template>:
 *   <SeoHead :options="{ title, description, jsonLd }" />
 *
 * The one behavioural difference from the Nuxt version worth flagging: Nuxt's
 * useRoute().path drives the canonical URL automatically. Inertia has no
 * router-owned "current path" composable in the same sense — usePage().url
 * is the closest equivalent and is used here, but it includes the query
 * string, unlike Nuxt's route.path. A real port would need to strip it the
 * same way the Nuxt version deliberately does (see useSeo.ts's own comment
 * on why it uses route.path over fullPath).
 */
export function useSeoData(options) {
    const page = usePage();

    const rawOptions = computed(() => (typeof options === 'function' ? options() : options));
    // Site-name suffix lives here, and ONLY here — not also in a
    // createInertiaApp `title` template callback (app.js/ssr.js). Having it
    // in both places double-applies during client-side hydration: Inertia's
    // client head manager re-runs the callback against the already-templated
    // SSR title as its input, producing "Page - Page - Site" instead of
    // "Page - Site". Confirmed by curling raw SSR output (correct, single
    // application) vs document.title after hydration (doubled) with the
    // callback still wired up in app.js/ssr.js.
    const resolved = computed(() => ({
        ...rawOptions.value,
        // Skipped where the title already names the site. The home page's is
        // "OSRS Events — Free Snakes & Ladders and Skill Races for Clans",
        // which the suffix turned into "… for Clans - OSRS Events" — the
        // brand twice in one line, in the browser tab and in every Discord
        // embed. Not the hydration bug the note above describes (this one is
        // in the SSR output too); just copy that overlaps with the suffix.
        title: rawOptions.value.title?.includes(SITE_NAME)
            ? rawOptions.value.title
            : `${rawOptions.value.title} - ${SITE_NAME}`,
    }));

    /**
     * The origin this page is actually being served from.
     *
     * Was hardcoded to SITE_URL, which meant every non-production
     * environment described production in its own meta: staging served a
     * page whose canonical, og:url and og:image all named
     * `https://osrs-events.com`. Found by posting a staging link into
     * Discord — the embed named production, because that is what the page it
     * fetched said about itself.
     *
     * Ziggy already shares this (`'ziggy' => (new Ziggy)->toArray()` in
     * HandleInertiaRequests, there for the reason gotcha 8 explains), and it
     * is the request's own root, so no new prop and no second source of
     * truth. SITE_URL stays as the fallback and as the answer to "is this
     * production", which `robots` below needs.
     */
    const origin = computed(() => (page.props.ziggy?.url ?? SITE_URL).replace(/\/$/, ''));
    const path = computed(() => page.url.split('?')[0]);
    const canonical = computed(() => new URL(path.value, origin.value).toString());
    const imageUrl = computed(() => new URL(resolved.value.image ?? DEFAULT_OG_IMAGE, origin.value).toString());

    /**
     * Anything that is not production says noindex, whatever the page asked.
     *
     * This is the half that makes the change above safe. A production
     * canonical on a staging page was doing this job by accident — badly,
     * since a canonical is a hint a crawler may ignore while `robots.txt`
     * here says `Allow: /`. Saying it outright is both more honest and
     * stronger, and it stops staging competing with production for the
     * landing pages, which are the ones the pre-launch door leaves open.
     */
    const robots = computed(() =>
        resolved.value.noindex || origin.value !== SITE_URL ? 'noindex, follow' : 'index, follow',
    );

    const jsonLdBlocks = computed(() => {
        const { jsonLd } = resolved.value;
        if (!jsonLd) return [];
        return (Array.isArray(jsonLd) ? jsonLd : [jsonLd]).map((block) => ({
            '@context': 'https://schema.org',
            ...block,
        }));
    });

    return { resolved, canonical, imageUrl, robots, jsonLdBlocks, Head };
}
