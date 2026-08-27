<template>
    <!-- :title as a Head PROP, not a literal <title> child element — the
         latter double-applies against createInertiaApp's global `title`
         template callback (app.js/ssr.js) during client-side hydration.
         SSR output looked correct either way (the server-side head merge
         collapses it), but the client-rendered document.title after
         hydration came out as "X - X - OSRS Events" until switched to the
         prop form, which is also Inertia's documented pattern. -->
    <Head :title="resolved.title">
        <meta name="description" :content="resolved.description" />
        <link rel="canonical" :href="canonical" />

        <meta name="robots" :content="robots" />

        <meta property="og:title" :content="resolved.title" />
        <meta property="og:description" :content="resolved.description" />
        <meta property="og:type" content="website" />
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

        <!-- JSON-LD is intentionally NOT emitted here — see
             LandingController::snakesAndLadders()'s View::share('jsonLd', ...)
             and app.blade.php. Putting it through Inertia's <Head> component
             (v-html on a <script> tag) renders as a literal innerHTML="..."
             HTML ATTRIBUTE during SSR, not the tag's text content — invisible
             to crawlers reading raw pre-hydration HTML. Confirmed by curling
             this exact route's SSR output; see the branch's evaluation notes. -->
    </Head>

    <guide-layout
        current-path="/osrs-snakes-and-ladders"
        :title="$t('landing.snakes.title')"
        :description="$t('landing.snakes.lead')"
        :sections="sections"
        :quick-facts="quickFacts"
    >
        <template #cta>
            <!-- Sent to /events, where the create modal actually lives.
                 Guests get the login page, which offers Discord and email
                 side by side — neither is required to make a board. Both go
                 into the app, so both go while the site is locked. -->
            <u-alert
                v-if="locked"
                color="neutral"
                variant="subtle"
                icon="i-lucide-lock"
                class="max-w-lg"
                :description="$t('lock.app_not_open')"
            />
            <template v-else>
                <u-button v-if="isAuthenticated" to="/events" color="primary" icon="i-lucide-plus" :label="$t('landing.cta_create')" />
                <u-button v-else href="/login" color="primary" icon="i-lucide-plus" :label="$t('landing.cta_create')" />
                <u-button to="/events" color="neutral" variant="outline" trailing-icon="i-lucide-arrow-right" :label="$t('landing.cta_browse')" />
            </template>
        </template>

        <!-- Two tracks, not one flat list of five steps — reported as
             reading like a feature-selling landing page rather than an
             actual guide, and a big part of that was mixing "what a host
             sets up" and "what a player does" into one numbered list with
             no signal that the audience changes partway through. Each
             track keeps its own numbering so "step 3" means something
             different depending which one you're reading, on purpose.

             Screenshots aren't ready yet (see docs/backlog.md), so each
             track ends in a <guide-screenshot> instead of a bare <img>
             or a code comment — a visible dashed box naming what will go
             there. Its `alt` text doubles as the placeholder's own
             content now and becomes the real image's alt text plus a
             visible caption once a screenshot is dropped in. -->
        <section id="how-it-works">
            <h2 :class="prose.h2">{{ howItWorksTitle }}</h2>

            <div class="grid md:grid-cols-2 gap-x-8">
                <div>
                    <h3 :class="prose.h3">{{ $t('landing.snakes.host_title') }}</h3>
                    <p :class="prose.p">{{ $t('landing.snakes.host_subtitle') }}</p>

                    <ol class="list-decimal list-inside" :class="prose.list">
                        <li v-for="step in hostSteps" :key="step.title">
                            <span class="font-medium text-highlighted">{{ step.title }}</span>
                            <span class="text-muted"> — {{ step.description }}</span>
                        </li>
                    </ol>

                    <guide-screenshot class="mt-4" :alt="$t('landing.snakes.screenshot_editor_alt')" />
                </div>

                <div>
                    <h3 :class="prose.h3">{{ $t('landing.snakes.player_title') }}</h3>
                    <p :class="prose.p">{{ $t('landing.snakes.player_subtitle') }}</p>

                    <ol class="list-decimal list-inside" :class="prose.list">
                        <li v-for="step in playerSteps" :key="step.title">
                            <span class="font-medium text-highlighted">{{ step.title }}</span>
                            <span class="text-muted"> — {{ step.description }}</span>
                        </li>
                    </ol>

                    <guide-screenshot class="mt-4" :alt="$t('landing.snakes.screenshot_board_alt')" />
                </div>
            </div>
        </section>

        <section id="why">
            <h2 :class="prose.h2">{{ $t('landing.snakes.why_title') }}</h2>
            <p :class="prose.p">{{ $t('landing.snakes.why_body') }}</p>
        </section>

        <section id="sizes">
            <h2 :class="prose.h2">{{ $t('landing.snakes.sizes_title') }}</h2>
            <p :class="prose.p">{{ $t('landing.snakes.sizes_subtitle') }}</p>

            <ul class="space-y-3">
                <li v-for="size in sizes" :key="size.title" class="flex gap-3">
                    <u-icon :name="size.icon" class="size-5 text-primary shrink-0 mt-0.5" />
                    <span><span class="font-medium text-highlighted">{{ size.title }}</span><span class="text-muted"> — {{ size.description }}</span></span>
                </li>
            </ul>
        </section>

        <section id="modes">
            <h2 :class="prose.h2">{{ $t('landing.snakes.modes_title') }}</h2>
            <p :class="prose.p">{{ $t('landing.snakes.modes_body') }}</p>
        </section>

        <section id="faq">
            <h2 :class="prose.h2">{{ faqTitle }}</h2>
            <guide-faq :faqs="faqs" />
        </section>
    </guide-layout>
</template>

<script setup>
import { trans } from 'laravel-vue-i18n';
import { useSeoData } from '@/Composables/useSeo';
import { useAuth } from '@/Composables/useAuth';
import { useSiteLock } from '@/Composables/useSiteLock';
import GuideLayout from '@/Components/GuideLayout.vue';
import GuideScreenshot from '@/Components/GuideScreenshot.vue';
import GuideFaq from '@/Components/GuideFaq.vue';
import { GUIDE_PROSE } from '@/Support/guides';

const { isAuthenticated } = useAuth();

// Everything this page invites you to do is behind the pre-launch door.
const { locked } = useSiteLock();

const props = defineProps({
    hostSteps: { type: Array, required: true },
    playerSteps: { type: Array, required: true },
    sizes: { type: Array, required: true },
    faqs: { type: Array, required: true },
});

const prose = GUIDE_PROSE;

// No `jsonLd` option here — the real FAQPage/HowTo structured data for this
// page is built and shared entirely in LandingController::snakesAndLadders()
// via View::share('jsonLd', ...) straight into app.blade.php, specifically
// to sidestep Inertia's own JSON-LD SSR gotcha (see that controller method's
// own comment).
const { resolved, canonical, imageUrl, robots, Head } = useSeoData({
    title: trans('landing.snakes.meta_title'),
    description: trans('landing.snakes.meta_desc'),
});

const howItWorksTitle = trans('landing.snakes.how_title');
const faqTitle = trans('landing.faq_title');

const sections = [
    { id: 'how-it-works', label: howItWorksTitle },
    { id: 'why', label: trans('landing.snakes.why_title') },
    { id: 'sizes', label: trans('landing.snakes.sizes_title') },
    { id: 'modes', label: trans('landing.snakes.modes_title') },
    { id: 'faq', label: faqTitle },
];

const quickFacts = [
    { label: trans('landing.snakes.fact_sizes'), value: '5×5 – 9×9' },
    { label: trans('landing.snakes.fact_rolls'), value: trans('landing.snakes.fact_rolls_value') },
    { label: trans('landing.snakes.fact_players'), value: trans('landing.snakes.fact_players_value') },
    { label: trans('landing.snakes.fact_access'), value: trans('landing.snakes.fact_access_value') },
];
</script>
