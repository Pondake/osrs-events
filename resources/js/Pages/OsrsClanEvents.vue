<template>
    <seo-head :options="seo" />

    <u-main>
        <u-page>
            <u-page-hero :title="$t('landing.clan_events.title')" :description="$t('landing.clan_events.lead')">
                <template #links>
                    <u-button v-if="isAuthenticated" href="/events" size="xl" color="primary" icon="i-simple-icons-discord" :label="$t('landing.cta_create')" />
                    <!-- route() called directly in the template, not from script —
                         it's only bound on Vue's globalProperties (template-only
                         access) by the ZiggyVue plugin. A raw `import { route }
                         from 'ziggy-js'` call from script would resolve its own
                         Ziggy config independently of the plugin instance ssr.js
                         explicitly configured with page.props.ziggy, falling back
                         to a global `Ziggy` variable that doesn't exist in Node —
                         reintroducing the exact SSR crash fixed in
                         HandleInertiaRequests, just for this one page. -->
                    <u-button v-else :href="route('auth.discord.redirect')" size="xl" color="primary" icon="i-simple-icons-discord" :label="$t('landing.cta_login')" />
                    <u-button href="/osrs-snakes-and-ladders" size="xl" color="neutral" variant="outline" trailing-icon="i-lucide-arrow-right" :label="$t('landing.event_ideas.supported_cta')" />
                </template>
            </u-page-hero>

            <u-page-section :title="$t('landing.clan_events.why_title')">
                <u-container class="max-w-3xl">
                    <p class="text-lg text-muted leading-relaxed">
                        {{ $t('landing.clan_events.why_body') }}
                    </p>
                </u-container>
            </u-page-section>

            <u-page-section :title="$t('landing.clan_events.what_title')" :description="$t('landing.clan_events.what_subtitle')" :features="features" />

            <u-page-section :title="$t('landing.clan_events.access_title')" :description="$t('landing.clan_events.access_subtitle')" :features="accessModes" />

            <u-page-section :title="$t('landing.clan_events.setup_title')">
                <u-container class="max-w-3xl">
                    <p class="text-lg text-muted leading-relaxed">
                        {{ $t('landing.clan_events.setup_body') }}
                    </p>
                    <u-button href="/osrs-event-ideas" class="mt-6" color="primary" variant="outline" trailing-icon="i-lucide-arrow-right" :label="$t('landing.event_ideas.title')" />
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
import SeoHead from '@/Components/SeoHead.vue';
import { useAuth } from '@/Composables/useAuth';

defineProps({
    faqs: { type: Array, required: true },
});

const { isAuthenticated } = useAuth();

const seo = {
    title: trans('landing.clan_events.meta_title'),
    description: trans('landing.clan_events.meta_desc'),
};

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
