<template>
    <seo-head :options="seo" />

    <u-main>
        <u-page>
            <!-- Copy from the CMS when a `home` page row exists, falling
                 back to the translations so the page still reads correctly
                 without one. The BUTTON below stays in code: which one you
                 get depends on whether you are signed in, and that is
                 behaviour rather than content. -->
            <u-page-hero :title="heroTitle" :description="heroDescription">
                <template #links>
                    <u-button v-if="isAuthenticated" href="/events" trailing-icon="i-lucide-arrow-right" size="xl" color="primary" :label="$t('home.cta_boards')" />
                    <u-button v-else :href="route('auth.discord.redirect')" size="xl" icon="i-simple-icons-discord" color="primary" :label="$t('home.cta_login')" />
                </template>
            </u-page-hero>

            <u-page-section :title="$t('home.preview_title')" :description="$t('home.preview_subtitle')">
                <u-container class="max-w-4xl">
                    <div class="relative">
                        <div class="absolute inset-0 -z-10 bg-primary/20 blur-3xl rounded-full scale-90" aria-hidden="true" />
                        <div class="rounded-xl border border-default shadow-2xl shadow-primary/10 overflow-hidden ring-1 ring-default">
                            <img
                                src="/images/demo/board-preview.png"
                                :alt="$t('home.preview_alt')"
                                width="1100"
                                height="1030"
                                loading="lazy"
                                class="w-full h-auto block"
                            />
                        </div>
                    </div>
                    <p class="mt-4 text-center text-sm text-muted">{{ $t('home.preview_caption') }}</p>
                </u-container>
            </u-page-section>

            <!-- The editable region. Anything an admin adds in
                 /admin/content/home lands here, between the preview and the
                 fixed "what's available" grid. -->
            <u-page-section v-if="blocks.length">
                <u-container class="max-w-4xl">
                    <page-renderer :blocks="blocks" />
                </u-container>
            </u-page-section>

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
import { computed } from 'vue';
import { trans } from 'laravel-vue-i18n';
import SeoHead from '@/Components/SeoHead.vue';
import PageRenderer from '@/Components/Cms/PageRenderer.vue';
import { useAuth } from '@/Composables/useAuth';

const { isAuthenticated, isAdmin } = useAuth();

/**
 * Unlike /about, /privacy and /terms, this page is only PARTLY editable.
 * The hero copy and the block region come from a `pages` row; the rest of
 * the page is behaviour — an auth-dependent call to action, an admin-only
 * section, and structured feature and guide lists the block vocabulary has
 * no equivalent for.
 */
const props = defineProps({
    page: { type: Object, default: null },
});

const heroTitle = computed(() => props.page?.title || trans('home.title'));
const heroDescription = computed(() => props.page?.subtitle || trans('home.description'));
const blocks = computed(() => props.page?.blocks ?? []);

const seo = {
    title: trans('seo.home_title'),
    description: trans('seo.home_desc'),
};

const features = [
    { icon: 'i-simple-icons-discord', title: trans('home.feature_discord_title'), description: trans('home.feature_discord_desc') },
    // Reordered so the first thing said about the product is that it runs
    // more than one kind of event. The old list opened with boards and then
    // spent two more entries on dice and snakes, which read as a Snakes &
    // Ladders site with some extras — true once, not any more.
    { icon: 'i-lucide-layers', title: trans('home.feature_types_title'), description: trans('home.feature_types_desc') },
    { icon: 'i-lucide-dice-6', title: trans('home.feature_snakes_title'), description: trans('home.feature_snakes_desc') },
    { icon: 'i-lucide-trophy', title: trans('home.feature_race_title'), description: trans('home.feature_race_desc') },
    { icon: 'i-lucide-list-checks', title: trans('home.feature_tasks_title'), description: trans('home.feature_tasks_desc') },
    { icon: 'i-lucide-user-round', title: trans('home.feature_profile_title'), description: trans('home.feature_profile_desc') },
];

const guideLinks = [
    { label: trans('nav.snakes'), to: '/osrs-snakes-and-ladders', icon: 'i-lucide-arrow-up-from-line', color: 'primary', variant: 'outline' },
    { label: trans('nav.clan_events'), to: '/osrs-clan-events', icon: 'i-lucide-users', color: 'neutral', variant: 'outline' },
    { label: trans('nav.event_ideas'), to: '/osrs-event-ideas', icon: 'i-lucide-lightbulb', color: 'neutral', variant: 'outline' },
];

const adminLinks = [
    { label: trans('nav.admin_boards'), to: '/admin/events', icon: 'i-lucide-settings', color: 'primary', variant: 'outline' },
    { label: trans('home.admin_manage_tasks'), to: '/admin/tasks', icon: 'i-lucide-list-checks', color: 'neutral', variant: 'outline' },
];
</script>
