<template>
    <Head :title="resolved.title">
        <meta name="description" :content="resolved.description" />
        <link rel="canonical" :href="canonical" />
    </Head>

    <u-main>
        <u-page>
            <u-page-hero title="OSRS Clan Event Ideas" description="Eight event formats OSRS clans actually run, what each one rewards, and how to pick the one that fits your roster.">
                <template #links>
                    <u-button href="/osrs-snakes-and-ladders" size="xl" color="primary" icon="i-lucide-arrow-up-from-line" label="See how Snakes & Ladders works" />
                </template>
            </u-page-hero>

            <u-page-section>
                <u-container class="max-w-3xl">
                    <p class="text-lg text-muted leading-relaxed">
                        The best clan event is the one your members finish. A format that carries a 200-member PvM clan will die
                        quietly in a 15-person social clan, and the reverse is just as true. Below is what each format actually
                        rewards, so you can choose on purpose instead of copying whatever the last clan did.
                    </p>
                </u-container>
            </u-page-section>

            <u-page-section title="Eight formats, compared">
                <u-container class="max-w-3xl">
                    <div class="divide-y divide-default">
                        <div v-for="idea in ideas" :key="idea.title" class="py-6 first:pt-0 last:pb-0">
                            <div class="flex items-center justify-between gap-3 mb-2">
                                <h3 class="text-lg font-semibold">{{ idea.title }}</h3>
                                <u-badge :label="idea.meta" color="neutral" variant="subtle" size="sm" />
                            </div>
                            <p class="text-muted leading-relaxed">{{ idea.desc }}</p>
                        </div>
                    </div>
                </u-container>
            </u-page-section>

            <u-page-section title="Picking the right one">
                <u-container class="max-w-3xl">
                    <p class="text-lg text-muted leading-relaxed">
                        Two questions settle it. First: how much time does your median member have — not your most active one?
                        Formats that reward raw hours reward the top ten percent of your roster and quietly tell everybody else not
                        to bother. Second: who is going to run it? Every format above needs somebody approving completions and
                        answering questions daily. If nobody has volunteered for that yet, run a shorter event than the one you
                        want.
                    </p>
                </u-container>
            </u-page-section>

            <u-page-section title="What OSRS Events supports today">
                <u-container class="max-w-3xl">
                    <p class="text-lg text-muted leading-relaxed mb-6">
                        OSRS Events currently runs Snakes &amp; Ladders boards with OSRS Wiki task integration, Discord-based
                        access control, teams and live leaderboards. Bingo boards are the next event type on the roadmap.
                    </p>
                    <u-button href="/osrs-snakes-and-ladders" color="primary" variant="outline" trailing-icon="i-lucide-arrow-right" label="See how Snakes & Ladders works" />
                </u-container>
            </u-page-section>
        </u-page>
    </u-main>
</template>

<script setup>
import { useSeoData } from '@/Composables/useSeo';

const { resolved, canonical, Head } = useSeoData({
    title: 'OSRS Clan Event Ideas — 8 Formats Compared',
    description: 'Eight Old School RuneScape clan event ideas compared: what each format rewards, how long to run it, and which clan sizes it actually suits.',
});

const ideas = [
    { title: 'Snakes & Ladders', meta: 'Mixed-ability clans · 1–4 weeks', desc: 'Every tile is an OSRS task and players roll a daily d6 to advance. Ladders jump you forward, snakes drop you back. Because a dice roll sits between a player and their next task, hours played matter less than in any other format here — a casual member can genuinely beat a maxed main, which keeps the event competitive to the final day.' },
    { title: 'Bingo', meta: 'Large, active clans · 1–2 weeks', desc: 'A grid of drop or achievement tiles; teams race to complete rows, columns or the full board. Far and away the most popular OSRS event format, and the most demanding. It rewards raw hours, so a few grinders usually decide it early unless you weight the tiles carefully.' },
    { title: 'Drop log race', meta: 'PvM clans · a weekend', desc: 'Pick a boss list and a point value per unique, then let teams log drops for a fixed window. Short, trivial to explain and almost setup-free — but it heavily favours whoever can commit the whole weekend.' },
    { title: 'Skill race', meta: 'Skilling and ironman clans · 1 week', desc: 'Everyone starts from their current XP in a chosen skill and races for the biggest gain. Fair by construction, because it measures change rather than total: a level 60 and a level 99 compete on equal terms.' },
    { title: 'Speedrun ladder', meta: 'PvM clans · ongoing', desc: 'Post a fixed set of encounters and let members submit times. It runs indefinitely with no end date, which makes it the lowest-maintenance option on this list — good as a permanent background event between bigger ones.' },
    { title: 'Achievement diary or quest race', meta: 'Newer and returning players · 2–4 weeks', desc: 'Points for diary tiers or quests completed inside the event window. One of the few formats where newer accounts have the advantage, since veterans have already finished everything. Excellent for onboarding a wave of new members.' },
    { title: 'Battleship', meta: 'Two rival clans · 1–2 weeks', desc: 'Each team hides ships on a grid and fires by completing tasks. Very social and very chat-heavy, but it needs an organiser watching it daily — the format lives or dies on somebody refereeing it.' },
    { title: 'Collection log push', meta: 'Long-term clans · a month or more', desc: 'Track collection log slots filled across the whole clan against one shared target. Cooperative rather than competitive, which suits clans where a leaderboard would put people off rather than draw them in.' },
];
</script>
