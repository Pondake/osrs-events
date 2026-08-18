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
            <u-page-hero :title="$t('home.title')" :description="$t('home.description')">
                <template #links>
                    <u-button v-if="isAuthenticated" href="/boards" trailing-icon="i-lucide-arrow-right" size="xl" color="primary" :label="$t('home.cta_boards')" />
                    <u-button v-else :href="route('login')" size="xl" icon="i-simple-icons-discord" color="primary" :label="$t('home.cta_login')" />
                </template>
            </u-page-hero>

            <u-page-section :title="$t('home.how_it_works')" :description="$t('home.how_subtitle')" :features="features" />

            <u-page-section :title="$t('home.guides_title')" :description="$t('home.guides_subtitle')" :links="guideLinks" />

            <u-page-section
                v-if="isAdmin"
                :title="$t('home.admin_title')"
                :description="$t('home.admin_subtitle')"
                :links="adminLinks"
            />
        </u-page>
    </u-main>
</template>

<script setup>
import { trans } from 'laravel-vue-i18n';
import { useSeoData } from '@/Composables/useSeo';
import { useAuth } from '@/Composables/useAuth';

const { isAuthenticated, isAdmin } = useAuth();

const { resolved, canonical, Head } = useSeoData({
    title: trans('seo.home_title'),
    description: trans('seo.home_desc'),
});

const features = [
    { icon: 'i-simple-icons-discord', title: trans('home.feature_discord_title'), description: trans('home.feature_discord_desc') },
    { icon: 'i-lucide-layout-grid', title: trans('home.feature_boards_title'), description: trans('home.feature_boards_desc') },
    { icon: 'i-lucide-dice-6', title: trans('home.feature_dice_title'), description: trans('home.feature_dice_desc') },
    { icon: 'i-lucide-list-checks', title: trans('home.feature_tasks_title'), description: trans('home.feature_tasks_desc') },
    { icon: 'i-lucide-arrow-up-from-line', title: trans('home.feature_snakes_title'), description: trans('home.feature_snakes_desc') },
    { icon: 'i-lucide-user-round', title: trans('home.feature_profile_title'), description: trans('home.feature_profile_desc') },
];

const guideLinks = [
    { label: trans('nav.snakes'), to: '/osrs-snakes-and-ladders', icon: 'i-lucide-arrow-up-from-line', color: 'primary', variant: 'outline' },
    { label: trans('nav.clan_events'), to: '/osrs-clan-events', icon: 'i-lucide-users', color: 'neutral', variant: 'outline' },
    { label: trans('nav.event_ideas'), to: '/osrs-event-ideas', icon: 'i-lucide-lightbulb', color: 'neutral', variant: 'outline' },
];

const adminLinks = [
    { label: trans('nav.admin_boards'), to: '/admin/boards', icon: 'i-lucide-settings', color: 'primary', variant: 'outline' },
    { label: trans('home.admin_manage_tasks'), to: '/admin/tasks', icon: 'i-lucide-list-checks', color: 'neutral', variant: 'outline' },
];
</script>
