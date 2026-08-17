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
                title="OSRS Snakes and Ladders for your clan"
                description="Turn any Old School RuneScape clan event into a Snakes and Ladders board — set the tiles, invite your team, and race to the top."
            >
                <template #links>
                    <!-- Board creation lives behind a modal on /boards (not yet
                         ported — see docs/backlog.md), so unauthenticated visitors
                         go straight through Discord login rather than to a page
                         they can't act on yet, matching the old Nuxt page's
                         behavior (frontend/app/pages/osrs-snakes-and-ladders.vue,
                         kept in stale/ for reference). -->
                    <u-button size="xl" color="primary" icon="i-lucide-plus" label="Start a board" :href="route('login')" />
                    <u-button
                        to="/boards"
                        size="xl"
                        color="neutral"
                        variant="outline"
                        trailing-icon="i-lucide-arrow-right"
                        label="Browse boards"
                    />
                </template>
            </u-page-hero>

            <u-page-section
                title="How it works"
                description="Five steps from empty board to a running clan event."
                :features="steps"
            />

            <u-page-section title="Why Snakes and Ladders">
                <u-container class="max-w-3xl">
                    <p class="text-lg text-muted leading-relaxed">
                        Clan events are more fun with structure and a little bit of luck. Snakes and Ladders boards
                        give every member a shared goal, a visible race, and the chance of a lucky climb — or an
                        unlucky slide back down.
                    </p>
                </u-container>
            </u-page-section>

            <u-page-section title="Board sizes" description="Pick the size that fits your event." :features="sizes" />

            <u-page-section title="Solo or team mode">
                <u-container class="max-w-3xl">
                    <p class="text-lg text-muted leading-relaxed">
                        Boards support both individual players racing independently and teams pooling progress
                        together — pick whichever fits your clan's event.
                    </p>
                </u-container>
            </u-page-section>

            <u-page-section title="Frequently asked questions">
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
import { useSeoData } from '@/Composables/useSeo';

const props = defineProps({
    steps: { type: Array, required: true },
    sizes: { type: Array, required: true },
    faqs: { type: Array, required: true },
});

const { resolved, canonical, imageUrl, robots, Head } = useSeoData({
    title: 'OSRS Snakes and Ladders — clan event boards',
    description:
        'Create a Snakes and Ladders board for your Old School RuneScape clan. Set custom tiles, invite your team via Discord, and race to the top.',
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
            name: 'How it works',
            description: 'Five steps from empty board to a running clan event.',
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
