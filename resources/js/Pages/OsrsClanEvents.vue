<template>
    <seo-head :options="seo" />

    <guide-layout
        current-path="/osrs-clan-events"
        :title="$t('landing.clan_events.title')"
        :description="$t('landing.clan_events.lead')"
        :sections="sections"
        :quick-facts="quickFacts"
    >
        <template #cta>
            <!-- Behind the door while the site is locked. A button that
                 lands on a password box reads as broken; a sentence saying
                 "not yet" reads as not yet. -->
            <u-alert
                v-if="locked"
                color="neutral"
                variant="subtle"
                icon="i-lucide-lock"
                class="max-w-lg"
                :description="$t('lock.app_not_open')"
            />
            <template v-else>
                <u-button v-if="isAuthenticated" href="/events" color="primary" icon="i-simple-icons-discord" :label="$t('landing.cta_create')" />
                <!-- route() called directly in the template, not from script —
                     it's only bound on Vue's globalProperties (template-only
                     access) by the ZiggyVue plugin. -->
                <u-button v-else to="/login" color="primary" icon="i-lucide-log-in" :label="$t('landing.cta_login')" />
            </template>
        </template>

        <section id="why">
            <h2 :class="prose.h2">{{ $t('landing.clan_events.why_title') }}</h2>
            <p :class="prose.p">{{ $t('landing.clan_events.why_body') }}</p>
        </section>

        <section id="what-you-get">
            <h2 :class="prose.h2">{{ $t('landing.clan_events.what_title') }}</h2>
            <p :class="prose.p">{{ $t('landing.clan_events.what_subtitle') }}</p>

            <ul class="space-y-3">
                <li v-for="feature in features" :key="feature.title" class="flex gap-3">
                    <u-icon :name="feature.icon" class="size-5 text-primary shrink-0 mt-0.5" />
                    <span><span class="font-medium text-highlighted">{{ feature.title }}</span><span class="text-muted"> — {{ feature.description }}</span></span>
                </li>
            </ul>
        </section>

        <section id="access">
            <h2 :class="prose.h2">{{ $t('landing.clan_events.access_title') }}</h2>
            <p :class="prose.p">{{ $t('landing.clan_events.access_subtitle') }}</p>

            <ul class="space-y-3">
                <li v-for="mode in accessModes" :key="mode.title" class="flex gap-3">
                    <u-icon :name="mode.icon" class="size-5 text-primary shrink-0 mt-0.5" />
                    <span><span class="font-medium text-highlighted">{{ mode.title }}</span><span class="text-muted"> — {{ mode.description }}</span></span>
                </li>
            </ul>
        </section>

        <section id="setup">
            <h2 :class="prose.h2">{{ $t('landing.clan_events.setup_title') }}</h2>
            <p :class="prose.p">{{ $t('landing.clan_events.setup_body') }}</p>
            <u-button href="/osrs-event-ideas" color="primary" variant="outline" trailing-icon="i-lucide-arrow-right" :label="$t('landing.event_ideas.title')" />
        </section>

        <section id="faq">
            <h2 :class="prose.h2">{{ faqTitle }}</h2>
            <guide-faq :faqs="faqs" />
        </section>
    </guide-layout>
</template>

<script setup>
import { trans } from 'laravel-vue-i18n';
import SeoHead from '@/Components/SeoHead.vue';
import GuideLayout from '@/Components/GuideLayout.vue';
import GuideFaq from '@/Components/GuideFaq.vue';
import { useAuth } from '@/Composables/useAuth';
import { useSiteLock } from '@/Composables/useSiteLock';
import { GUIDE_PROSE } from '@/Support/guides';

defineProps({
    faqs: { type: Array, required: true },
});

const { isAuthenticated } = useAuth();
const { locked } = useSiteLock();
const prose = GUIDE_PROSE;

const seo = {
    title: trans('landing.clan_events.meta_title'),
    description: trans('landing.clan_events.meta_desc'),
};

const faqTitle = trans('landing.faq_title');

const sections = [
    { id: 'why', label: trans('landing.clan_events.why_title') },
    { id: 'what-you-get', label: trans('landing.clan_events.what_title') },
    { id: 'access', label: trans('landing.clan_events.access_title') },
    { id: 'setup', label: trans('landing.clan_events.setup_title') },
    { id: 'faq', label: faqTitle },
];

const quickFacts = [
    { label: trans('landing.clan_events.fact_price'), value: trans('landing.clan_events.fact_price_value') },
    { label: trans('landing.clan_events.fact_formats'), value: trans('landing.clan_events.fact_formats_value') },
    { label: trans('landing.clan_events.fact_login'), value: trans('landing.clan_events.fact_login_value') },
];

const features = [
    { icon: 'i-lucide-layout-grid', title: trans('landing.clan_events.feature_boards_title'), description: trans('landing.clan_events.feature_boards_desc') },
    { icon: 'i-simple-icons-discord', title: trans('landing.clan_events.feature_discord_title'), description: trans('landing.clan_events.feature_discord_desc') },
    { icon: 'i-lucide-users', title: trans('landing.clan_events.feature_teams_title'), description: trans('landing.clan_events.feature_teams_desc') },
    { icon: 'i-lucide-link', title: trans('landing.clan_events.feature_invites_title'), description: trans('landing.clan_events.feature_invites_desc') },
    { icon: 'i-lucide-trophy', title: trans('landing.clan_events.feature_leaderboard_title'), description: trans('landing.clan_events.feature_leaderboard_desc') },
    { icon: 'i-lucide-heart', title: trans('landing.clan_events.feature_free_title'), description: trans('landing.clan_events.feature_free_desc') },
];

const accessModes = [
    { icon: 'i-lucide-globe', title: trans('landing.clan_events.access_open_title'), description: trans('landing.clan_events.access_open_desc') },
    { icon: 'i-lucide-shield-check', title: trans('landing.clan_events.access_guild_title'), description: trans('landing.clan_events.access_guild_desc') },
    { icon: 'i-lucide-key-round', title: trans('landing.clan_events.access_invite_title'), description: trans('landing.clan_events.access_invite_desc') },
];
</script>
