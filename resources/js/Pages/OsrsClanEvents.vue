<template>
    <Head :title="resolved.title">
        <meta name="description" :content="resolved.description" />
        <link rel="canonical" :href="canonical" />
    </Head>

    <u-main>
        <u-page>
            <u-page-hero title="OSRS Clan Events" description="Everything you need to run an Old School RuneScape clan event: board building, Discord-based access, teams and live progress tracking. Free, with no ads.">
                <template #links>
                    <u-button v-if="isAuthenticated" href="/boards" size="xl" color="primary" icon="i-simple-icons-discord" label="Create a board" />
                    <!-- route() called directly in the template, not from script —
                         it's only bound on Vue's globalProperties (template-only
                         access) by the ZiggyVue plugin. A raw `import { route }
                         from 'ziggy-js'` call from script would resolve its own
                         Ziggy config independently of the plugin instance ssr.js
                         explicitly configured with page.props.ziggy, falling back
                         to a global `Ziggy` variable that doesn't exist in Node —
                         reintroducing the exact SSR crash fixed in
                         HandleInertiaRequests, just for this one page. -->
                    <u-button v-else :href="route('login')" size="xl" color="primary" icon="i-simple-icons-discord" label="Login with Discord" />
                    <u-button href="/osrs-snakes-and-ladders" size="xl" color="neutral" variant="outline" trailing-icon="i-lucide-arrow-right" label="See how Snakes & Ladders works" />
                </template>
            </u-page-hero>

            <u-page-section title="Why clans run events">
                <u-container class="max-w-3xl">
                    <p class="text-lg text-muted leading-relaxed">
                        A clan is at its healthiest when members log in with a reason to. Events give the roster a shared goal, pull
                        semi-inactive members back, and give newer players a way to contribute that does not depend on gear or kill
                        count. The idea was never the hard part — the tracking was. Most clans end up with a spreadsheet nobody
                        updates and a Discord thread of screenshots nobody reads.
                    </p>
                </u-container>
            </u-page-section>

            <u-page-section title="What you get" description="The parts of running an event that usually eat an organiser's week." :features="features" />

            <u-page-section title="Controlling who can join" description="Set this per board — a public community event and a private clan event can run side by side." :features="accessModes" />

            <u-page-section title="Setting up your first event">
                <u-container class="max-w-3xl">
                    <p class="text-lg text-muted leading-relaxed">
                        Start smaller than you think you should. A 5×5 board over a weekend tells you how your clan actually
                        engages, and costs an afternoon to build. Set a daily roll limit so the event paces itself rather than being
                        finished by Sunday. Write your tasks to be checkable from a screenshot — "get a Dragon Warhammer" settles
                        cleanly, "do some slayer" does not. Then post the board link in your Discord and pin it, because the single
                        most common reason a clan event dies is that half the roster never found the link.
                    </p>
                    <u-button href="/osrs-event-ideas" class="mt-6" color="primary" variant="outline" trailing-icon="i-lucide-arrow-right" label="OSRS Clan Event Ideas" />
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
import { useAuth } from '@/Composables/useAuth';

defineProps({
    faqs: { type: Array, required: true },
});

const { isAuthenticated } = useAuth();

const { resolved, canonical, Head } = useSeoData({
    title: 'OSRS Clan Events — Free Event Board Platform',
    description:
        "Run Old School RuneScape clan events for free. Build task boards, group members into teams from your Discord server and track everyone's progress in one place.",
});

const features = [
    { icon: 'i-lucide-layout-grid', title: 'Custom task boards', description: 'Build a 5×5, 7×7 or 9×9 board and put an OSRS task on every tile, pulled straight from the OSRS Wiki.' },
    { icon: 'i-simple-icons-discord', title: 'Discord-based access', description: "Lock a board to your clan's Discord server so only members can join. No whitelist to maintain by hand." },
    { icon: 'i-lucide-users', title: 'Teams', description: 'Split the roster into teams that share one board position, or let everyone play their own board solo.' },
    { icon: 'i-lucide-link', title: 'Invite links', description: 'Generate invite links with expiry dates and use limits, and revoke any of them at any time.' },
    { icon: 'i-lucide-trophy', title: 'Live leaderboard', description: 'Every board has a leaderboard, so the clan can see where everyone stands without asking in chat.' },
    { icon: 'i-lucide-heart', title: 'Free and ad-free', description: 'No paid tier, no ads and no player cap. Donations cover hosting.' },
];

const accessModes = [
    { icon: 'i-lucide-globe', title: 'Open', description: 'Anyone logged in can join. Best for community-wide or cross-clan events.' },
    { icon: 'i-lucide-shield-check', title: 'Discord server', description: 'Only members of a specific Discord server can join. The usual choice for a normal clan event.' },
    { icon: 'i-lucide-key-round', title: 'Invite only', description: 'Players need a link or six-character code that you generate, limit and revoke.' },
];
</script>
