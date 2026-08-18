<template>
    <Head :title="resolved.title">
        <meta name="description" :content="resolved.description" />
        <link rel="canonical" :href="canonical" />
    </Head>

    <u-main>
        <u-page>
            <u-container class="max-w-4xl py-12">
                <div class="mb-10">
                    <h1 class="text-3xl font-bold text-highlighted">About</h1>
                    <p class="text-muted mt-2">An OSRS-themed events platform for clans, made with love for the community.</p>
                </div>

                <section class="mb-12">
                    <h2 class="text-2xl font-bold mb-6">What we offer</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <u-page-card
                            v-for="feature in features"
                            :key="feature.title"
                            :title="feature.title"
                            :description="feature.desc"
                            :icon="feature.icon"
                        />
                    </div>
                </section>

                <u-separator class="my-8" />

                <section class="mb-12 flex items-start gap-4">
                    <div class="shrink-0 w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center">
                        <u-icon name="i-lucide-shield-check" class="text-primary text-2xl" />
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold mb-3">Privacy First</h2>
                        <p class="text-muted leading-relaxed mb-4">
                            We only collect the minimum data needed to run the platform — your Discord username, ID and avatar. We
                            do not track you, show you ads, or sell your data to anyone. Full details are in our Privacy Policy.
                        </p>
                        <u-button href="/privacy" variant="outline" color="primary" size="sm" icon="i-lucide-arrow-right" trailing label="Read our Privacy Policy" />
                    </div>
                </section>

                <u-separator class="my-8" />

                <section class="mb-12 flex items-start gap-4">
                    <div class="shrink-0 w-12 h-12 rounded-xl bg-amber-500/10 flex items-center justify-center">
                        <u-icon name="i-lucide-heart" class="text-amber-500 text-2xl" />
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold mb-3">Free &amp; Community-Driven</h2>
                        <p class="text-muted leading-relaxed mb-4">
                            This project is run by a player, for players. Hosting and maintenance cost real money, so if you find
                            value in the platform, a small donation helps keep the lights on.
                        </p>
                        <u-button href="/donate" variant="outline" color="warning" size="sm" icon="i-lucide-coffee" trailing label="Support us on Ko-fi" />
                    </div>
                </section>

                <u-separator class="my-8" />

                <section class="mb-12 flex items-start gap-4">
                    <div class="shrink-0 w-12 h-12 rounded-xl bg-sky-500/10 flex items-center justify-center">
                        <u-icon name="i-lucide-life-buoy" class="text-sky-500 text-2xl" />
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold mb-3">Support</h2>
                        <p class="text-muted leading-relaxed mb-4">
                            Have a question, found a bug, or want to suggest a feature? Send us an email and we'll get back to you
                            as soon as we can.
                        </p>
                        <u-button href="mailto:dev@absolit.nl" variant="outline" color="primary" size="sm" icon="i-lucide-mail" label="Send us an email" />
                    </div>
                </section>

                <u-separator class="my-8" />

                <u-alert
                    color="warning"
                    variant="subtle"
                    icon="i-lucide-alert-triangle"
                    title="Not affiliated with Jagex"
                    description="OSRS Events is a fan-made project and is not affiliated with, endorsed by, or connected to Jagex Ltd. Old School RuneScape is a trademark of Jagex Ltd."
                />
            </u-container>
        </u-page>
    </u-main>
</template>

<script setup>
import { useSeoData } from '@/Composables/useSeo';

const { resolved, canonical, Head } = useSeoData({
    title: 'About OSRS Events',
    description:
        'OSRS Events is a free, ad-free platform for running Old School RuneScape clan events, with Discord login, custom boards and OSRS Wiki task integration.',
});

const features = [
    { icon: 'i-lucide-grid-3x3', title: 'Custom Boards', desc: 'Admins create event boards of any size and assign OSRS tasks to every tile.' },
    { icon: 'i-lucide-dice-6', title: 'Daily Dice Rolls', desc: 'Roll a d6 each day and advance across the board. Roll limits keep things competitive.' },
    { icon: 'i-lucide-check-square', title: 'OSRS Tasks', desc: 'Every tile has an OSRS task. Complete it in-game and mark it done to unlock your next roll.' },
    { icon: 'i-lucide-message-circle', title: 'Discord Login', desc: 'No passwords needed — log in with your Discord account and your progress is saved automatically.' },
    { icon: 'i-lucide-moon', title: 'Dark Mode', desc: 'Easy on the eyes whether you grind at noon or 2 AM. Dark mode is always available.' },
    { icon: 'i-lucide-heart', title: 'Free to Use', desc: 'No premium tiers, no paywalls. Everything is free. If you enjoy it, consider a donation!' },
];
</script>
