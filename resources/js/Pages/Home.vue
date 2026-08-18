<template>
    <Head :title="resolved.title">
        <meta name="description" :content="resolved.description" />
        <link rel="canonical" :href="canonical" />
        <meta property="og:title" :content="resolved.title" />
        <meta property="og:description" :content="resolved.description" />
        <meta property="og:type" content="website" />
        <meta property="og:url" :content="canonical" />
    </Head>

    <u-main>
        <u-page>
            <u-page-hero title="Free Event Boards for OSRS Clans" description="The Old School RuneScape events platform for your clan. Create boards, roll dice and complete tasks together — login with Discord to get started.">
                <template #links>
                    <u-button v-if="isAuthenticated" href="/boards" trailing-icon="i-lucide-arrow-right" size="xl" color="primary" label="View Boards" />
                    <u-button v-else :href="route('login')" size="xl" icon="i-simple-icons-discord" color="primary" label="Login with Discord" />
                </template>
            </u-page-hero>

            <u-page-section title="What's available" description="Everything you need to run OSRS events with your clan — all in one place." :features="features" />

            <u-page-section title="Guides" description="How to run an Old School RuneScape clan event, and which format to pick." :links="guideLinks" />

            <u-page-section
                v-if="isAdmin"
                title="Admin Tools"
                description="Manage boards, tasks and players from your admin dashboard."
                :links="adminLinks"
            />
        </u-page>
    </u-main>
</template>

<script setup>
import { useSeoData } from '@/Composables/useSeo';
import { useAuth } from '@/Composables/useAuth';

const { isAuthenticated, isAdmin } = useAuth();

const { resolved, canonical, Head } = useSeoData({
    title: 'OSRS Events — Free Snakes & Ladders Boards for Clans',
    description:
        'Run free Old School RuneScape clan events. Build a Snakes & Ladders board, assign OSRS tasks to every tile, roll a daily d6 and track your clan\'s progress.',
});

const features = [
    { icon: 'i-simple-icons-discord', title: 'Discord Login', description: 'Log in with your Discord account. No passwords needed — your progress is saved automatically.' },
    { icon: 'i-lucide-layout-grid', title: 'Custom Event Boards', description: 'Admins create Snakes & Ladders boards of any size (5×5, 7×7, 9×9) and assign OSRS tasks to every tile.' },
    { icon: 'i-lucide-dice-6', title: 'Daily Dice Rolls', description: 'Roll a d6 each day to advance across the board. Configurable daily roll limits keep things competitive.' },
    { icon: 'i-lucide-list-checks', title: 'OSRS Task Integration', description: 'Every tile links to an OSRS task. Search the wiki directly to auto-fill task icons and titles.' },
    { icon: 'i-lucide-arrow-up-from-line', title: 'Snakes & Ladders', description: 'Land on a ladder to jump ahead, or hit a snake and slide back. Every board is unique.' },
    { icon: 'i-lucide-user-round', title: 'Player Profiles', description: 'Track your progress, completed tiles and boards. Set a custom display name separate from your Discord username.' },
];

const guideLinks = [
    { label: 'Snakes & Ladders', to: '/osrs-snakes-and-ladders', icon: 'i-lucide-arrow-up-from-line', color: 'primary', variant: 'outline' },
    { label: 'Clan Events', to: '/osrs-clan-events', icon: 'i-lucide-users', color: 'neutral', variant: 'outline' },
    { label: 'Event Ideas', to: '/osrs-event-ideas', icon: 'i-lucide-lightbulb', color: 'neutral', variant: 'outline' },
];

const adminLinks = [
    { label: 'Manage boards', to: '/admin/boards', icon: 'i-lucide-settings', color: 'primary', variant: 'outline' },
    { label: 'Manage tasks', to: '/admin/tasks', icon: 'i-lucide-list-checks', color: 'neutral', variant: 'outline' },
];
</script>
