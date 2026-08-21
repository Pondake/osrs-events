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

    <u-main>
        <u-page>
            <u-page-hero
                :title="$t('landing.snakes.title')"
                :description="$t('landing.snakes.lead')"
            >
                <template #links>
                    <!-- Sent to /events, where the create modal actually
                         lives. This used to point at route('login') for
                         everyone, unconditionally — back when that name
                         meant the DISCORD kickoff rather than the login
                         page. So "Create a board" threw you into an OAuth
                         consent screen, and did it even when you were
                         already signed in. The
                         comment here justified it by saying the create modal
                         was not ported yet; it has been for some time.
                         Guests get the login page, which offers Discord and
                         email side by side — neither is required to make a
                         board. -->
                    <u-button v-if="isAuthenticated" to="/events" size="xl" color="primary" icon="i-lucide-plus" :label="$t('landing.cta_create')" />
                    <u-button v-else href="/login" size="xl" color="primary" icon="i-lucide-plus" :label="$t('landing.cta_create')" />
                    <u-button
                        to="/events"
                        size="xl"
                        color="neutral"
                        variant="outline"
                        trailing-icon="i-lucide-arrow-right"
                        :label="$t('landing.cta_browse')"
                    />
                </template>
            </u-page-hero>

            <u-page-section
                :title="$t('landing.snakes.how_title')"
                :description="$t('landing.snakes.how_subtitle')"
                :features="steps"
            />

            <u-page-section :title="$t('landing.snakes.why_title')">
                <u-container class="max-w-3xl">
                    <p class="text-lg text-muted leading-relaxed">
                        {{ $t('landing.snakes.why_body') }}
                    </p>
                </u-container>
            </u-page-section>

            <u-page-section :title="$t('landing.snakes.sizes_title')" :description="$t('landing.snakes.sizes_subtitle')" :features="sizes" />

            <u-page-section :title="$t('landing.snakes.modes_title')">
                <u-container class="max-w-3xl">
                    <p class="text-lg text-muted leading-relaxed">
                        {{ $t('landing.snakes.modes_body') }}
                    </p>
                </u-container>
            </u-page-section>

            <u-page-section :title="$t('landing.faq_title')">
                <u-container class="max-w-3xl">
                    <dl class="divide-y divide-default">
                        <div v-for="faq in faqs" :key="faq.question" class="py-6 first:pt-0 last:pb-0">
                            <dt class="text-lg font-semibold">{{ faq.question }}</dt>
                            <dd class="mt-2 text-muted leading-relaxed">{{ faq.answer }}</dd>
                        </div>
                    </dl>
                </u-container>
            </u-page-section>
        </u-page>
    </u-main>
</template>

<script setup>
import { trans } from 'laravel-vue-i18n';
import { useSeoData } from '@/Composables/useSeo';
import { useAuth } from '@/Composables/useAuth';

const { isAuthenticated } = useAuth();

const props = defineProps({
    steps: { type: Array, required: true },
    sizes: { type: Array, required: true },
    faqs: { type: Array, required: true },
});

const { resolved, canonical, imageUrl, robots, Head } = useSeoData({
    title: trans('landing.snakes.meta_title'),
    description: trans('landing.snakes.meta_desc'),
    jsonLd: [
        {
            '@type': 'FAQPage',
            mainEntity: props.faqs.map((faq) => ({
                '@type': 'Question',
                name: faq.question,
                acceptedAnswer: { '@type': 'Answer', text: faq.answer },
            })),
        },
        {
            '@type': 'HowTo',
            name: trans('landing.snakes.how_title'),
            description: trans('landing.snakes.how_subtitle'),
            step: props.steps.map((step, i) => ({
                '@type': 'HowToStep',
                position: i + 1,
                name: step.title,
                text: step.description,
            })),
        },
    ],
});
</script>
