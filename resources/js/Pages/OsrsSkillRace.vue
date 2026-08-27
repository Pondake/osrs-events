<template>
    <seo-head :options="seo" />

    <guide-layout
        current-path="/osrs-skill-race"
        :title="$t('landing.skill_race.title')"
        :description="$t('landing.skill_race.lead')"
        :sections="sections"
        :quick-facts="quickFacts"
    >
        <template #cta>
            <u-alert
                v-if="locked"
                color="neutral"
                variant="subtle"
                icon="i-lucide-lock"
                class="max-w-lg"
                :description="$t('lock.app_not_open')"
            />
            <template v-else>
                <u-button v-if="isAuthenticated" to="/events" color="primary" icon="i-lucide-plus" :label="$t('landing.cta_create')" />
                <u-button v-else href="/login" color="primary" icon="i-lucide-plus" :label="$t('landing.cta_create')" />
                <u-button to="/events" color="neutral" variant="outline" trailing-icon="i-lucide-arrow-right" :label="$t('landing.cta_browse')" />
            </template>
        </template>

        <section id="how-it-works">
            <h2 :class="prose.h2">{{ howItWorksTitle }}</h2>

            <div class="grid md:grid-cols-2 gap-x-8">
                <div>
                    <h3 :class="prose.h3">{{ $t('landing.skill_race.host_title') }}</h3>
                    <p :class="prose.p">{{ $t('landing.skill_race.host_subtitle') }}</p>

                    <ol class="list-decimal list-inside" :class="prose.list">
                        <li v-for="step in hostSteps" :key="step.title">
                            <span class="font-medium text-highlighted">{{ step.title }}</span>
                            <span class="text-muted"> — {{ step.description }}</span>
                        </li>
                    </ol>

                    <guide-screenshot class="mt-4" :alt="$t('landing.skill_race.screenshot_editor_alt')" />
                </div>

                <div>
                    <h3 :class="prose.h3">{{ $t('landing.skill_race.player_title') }}</h3>
                    <p :class="prose.p">{{ $t('landing.skill_race.player_subtitle') }}</p>

                    <ol class="list-decimal list-inside" :class="prose.list">
                        <li v-for="step in playerSteps" :key="step.title">
                            <span class="font-medium text-highlighted">{{ step.title }}</span>
                            <span class="text-muted"> — {{ step.description }}</span>
                        </li>
                    </ol>

                    <guide-screenshot class="mt-4" :alt="$t('landing.skill_race.screenshot_board_alt')" />
                </div>
            </div>
        </section>

        <section id="why">
            <h2 :class="prose.h2">{{ $t('landing.skill_race.why_title') }}</h2>
            <p :class="prose.p">{{ $t('landing.skill_race.why_body') }}</p>
        </section>

        <section id="tracking">
            <h2 :class="prose.h2">{{ $t('landing.skill_race.modes_title') }}</h2>
            <p :class="prose.p">{{ $t('landing.skill_race.modes_subtitle') }}</p>

            <ul class="space-y-3">
                <li v-for="mode in modes" :key="mode.title" class="flex gap-3">
                    <u-icon :name="mode.icon" class="size-5 text-primary shrink-0 mt-0.5" />
                    <span><span class="font-medium text-highlighted">{{ mode.title }}</span><span class="text-muted"> — {{ mode.description }}</span></span>
                </li>
            </ul>
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
import GuideScreenshot from '@/Components/GuideScreenshot.vue';
import GuideFaq from '@/Components/GuideFaq.vue';
import { useAuth } from '@/Composables/useAuth';
import { useSiteLock } from '@/Composables/useSiteLock';
import { GUIDE_PROSE } from '@/Support/guides';

defineProps({
    hostSteps: { type: Array, required: true },
    playerSteps: { type: Array, required: true },
    modes: { type: Array, required: true },
    faqs: { type: Array, required: true },
});

const { isAuthenticated } = useAuth();
const { locked } = useSiteLock();
const prose = GUIDE_PROSE;

const seo = {
    title: trans('landing.skill_race.meta_title'),
    description: trans('landing.skill_race.meta_desc'),
};

const howItWorksTitle = trans('landing.skill_race.how_title');
const faqTitle = trans('landing.faq_title');

const sections = [
    { id: 'how-it-works', label: howItWorksTitle },
    { id: 'why', label: trans('landing.skill_race.why_title') },
    { id: 'tracking', label: trans('landing.skill_race.modes_title') },
    { id: 'faq', label: faqTitle },
];

const quickFacts = [
    { label: trans('landing.skill_race.fact_metric'), value: trans('landing.skill_race.fact_metric_value') },
    { label: trans('landing.skill_race.fact_scoring'), value: trans('landing.skill_race.fact_scoring_value') },
    { label: trans('landing.skill_race.fact_setup'), value: trans('landing.skill_race.fact_setup_value') },
    { label: trans('landing.skill_race.fact_players'), value: trans('landing.skill_race.fact_players_value') },
];
</script>
