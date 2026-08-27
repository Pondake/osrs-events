<template>
    <seo-head :options="seo" />

    <guide-layout
        current-path="/osrs-bingo"
        :title="$t('landing.bingo.title')"
        :description="$t('landing.bingo.lead')"
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
                    <h3 :class="prose.h3">{{ $t('landing.bingo.host_title') }}</h3>
                    <p :class="prose.p">{{ $t('landing.bingo.host_subtitle') }}</p>

                    <ol class="list-decimal list-inside space-y-2" :class="prose.list">
                        <li v-for="step in hostSteps" :key="step.title">
                            <span class="font-medium text-highlighted">{{ step.title }}</span>
                            <span class="text-muted"> — {{ step.description }}</span>
                        </li>
                    </ol>

                    <guide-screenshot class="mt-4" :alt="$t('landing.bingo.screenshot_editor_alt')" />
                </div>

                <div>
                    <h3 :class="prose.h3">{{ $t('landing.bingo.player_title') }}</h3>
                    <p :class="prose.p">{{ $t('landing.bingo.player_subtitle') }}</p>

                    <ol class="list-decimal list-inside space-y-2" :class="prose.list">
                        <li v-for="step in playerSteps" :key="step.title">
                            <span class="font-medium text-highlighted">{{ step.title }}</span>
                            <span class="text-muted"> — {{ step.description }}</span>
                        </li>
                    </ol>

                    <guide-screenshot class="mt-4" :alt="$t('landing.bingo.screenshot_board_alt')" />
                </div>
            </div>
        </section>

        <section id="why">
            <h2 :class="prose.h2">{{ $t('landing.bingo.why_title') }}</h2>
            <p :class="prose.p">{{ $t('landing.bingo.why_body') }}</p>
        </section>

        <section id="win-conditions">
            <h2 :class="prose.h2">{{ $t('landing.bingo.modes_title') }}</h2>
            <p :class="prose.p">{{ $t('landing.bingo.modes_subtitle') }}</p>

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

const prose = GUIDE_PROSE;

defineProps({
    hostSteps: { type: Array, required: true },
    playerSteps: { type: Array, required: true },
    modes: { type: Array, required: true },
    faqs: { type: Array, required: true },
});

const { isAuthenticated } = useAuth();
const { locked } = useSiteLock();

const seo = {
    title: trans('landing.bingo.meta_title'),
    description: trans('landing.bingo.meta_desc'),
};

const howItWorksTitle = trans('landing.bingo.how_title');
const faqTitle = trans('landing.faq_title');

const sections = [
    { id: 'how-it-works', label: howItWorksTitle },
    { id: 'why', label: trans('landing.bingo.why_title') },
    { id: 'win-conditions', label: trans('landing.bingo.modes_title') },
    { id: 'faq', label: faqTitle },
];

const quickFacts = [
    { label: trans('landing.bingo.fact_grid'), value: '3×3 – 10×10' },
    { label: trans('landing.bingo.fact_win'), value: trans('landing.bingo.fact_win_value') },
    { label: trans('landing.bingo.fact_approval'), value: trans('landing.bingo.fact_approval_value') },
    { label: trans('landing.bingo.fact_players'), value: trans('landing.bingo.fact_players_value') },
];
</script>
