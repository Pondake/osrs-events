<template>
    <seo-head :options="seo" />

    <guide-layout
        current-path="/osrs-hardcore-worlds-clan-events"
        :title="$t('landing.hardcore.title')"
        :description="$t('landing.hardcore.lead')"
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

        <section id="what">
            <h2 :class="prose.h2">{{ $t('landing.hardcore.what_title') }}</h2>
            <p :class="prose.p">{{ $t('landing.hardcore.what_body') }}</p>

            <ul class="list-disc list-inside" :class="prose.list">
                <li v-for="fact in facts" :key="fact" class="text-muted leading-relaxed">{{ fact }}</li>
            </ul>

            <!-- The facts are pre-launch reporting, and the page says so
                 rather than presenting them as the rulebook. -->
            <p class="text-sm text-dimmed mb-4">{{ $t('landing.hardcore.what_source') }}</p>
        </section>

        <section id="why">
            <h2 :class="prose.h2">{{ $t('landing.hardcore.why_title') }}</h2>
            <p :class="prose.p">{{ $t('landing.hardcore.why_body') }}</p>
        </section>

        <section id="formats">
            <h2 :class="prose.h2">{{ $t('landing.hardcore.formats_title') }}</h2>
            <p :class="prose.p">{{ $t('landing.hardcore.formats_subtitle') }}</p>

            <ul class="space-y-5">
                <li v-for="format in formats" :key="format.title" class="flex gap-3">
                    <u-icon :name="format.icon" class="size-5 text-primary shrink-0 mt-0.5" />
                    <div>
                        <h3 class="font-medium text-highlighted">{{ format.title }}</h3>
                        <p class="text-muted leading-relaxed">{{ format.description }}</p>
                        <a
                            v-if="format.guide"
                            :href="format.guide"
                            class="inline-flex items-center gap-1 text-sm text-primary hover:underline mt-1"
                        >
                            {{ $t('landing.hardcore.format_guide_link') }}
                            <u-icon name="i-lucide-arrow-right" class="size-3.5" />
                        </a>
                    </div>
                </li>
            </ul>
        </section>

        <section id="deaths">
            <h2 :class="prose.h2">{{ $t('landing.hardcore.deaths_title') }}</h2>
            <p :class="prose.p">{{ $t('landing.hardcore.deaths_subtitle') }}</p>

            <ol class="list-decimal list-inside space-y-2" :class="prose.list">
                <li v-for="rule in deathRules" :key="rule.title">
                    <span class="font-medium text-highlighted">{{ rule.title }}</span>
                    <span class="text-muted"> — {{ rule.description }}</span>
                </li>
            </ol>

            <p :class="prose.p">{{ $t('landing.hardcore.deaths_footer') }}</p>
        </section>

        <section id="squares">
            <h2 :class="prose.h2">{{ $t('landing.hardcore.squares_title') }}</h2>
            <p :class="prose.p">{{ $t('landing.hardcore.squares_subtitle') }}</p>

            <ul class="grid sm:grid-cols-2 gap-x-6 gap-y-2" :class="prose.list">
                <li v-for="square in squares" :key="square.title" class="flex gap-2">
                    <u-icon name="i-lucide-square-check" class="size-4 text-primary shrink-0 mt-1" />
                    <span>
                        <span class="text-default">{{ square.title }}</span>
                        <span v-if="square.note" class="block text-sm text-muted">{{ square.note }}</span>
                    </span>
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
import GuideFaq from '@/Components/GuideFaq.vue';
import { useAuth } from '@/Composables/useAuth';
import { useSiteLock } from '@/Composables/useSiteLock';
import { GUIDE_PROSE } from '@/Support/guides';

const prose = GUIDE_PROSE;

defineProps({
    facts: { type: Array, required: true },
    formats: { type: Array, required: true },
    deathRules: { type: Array, required: true },
    squares: { type: Array, required: true },
    faqs: { type: Array, required: true },
});

const { isAuthenticated } = useAuth();
const { locked } = useSiteLock();

// No `image` of its own yet: the site-wide preview is used until
// scripts/og-images.mjs grows a Hardcore Worlds variant.
const seo = {
    title: trans('landing.hardcore.meta_title'),
    description: trans('landing.hardcore.meta_desc'),
};

const faqTitle = trans('landing.faq_title');

const sections = [
    { id: 'what', label: trans('landing.hardcore.what_title') },
    { id: 'why', label: trans('landing.hardcore.why_title') },
    { id: 'formats', label: trans('landing.hardcore.formats_title') },
    { id: 'deaths', label: trans('landing.hardcore.deaths_title') },
    { id: 'squares', label: trans('landing.hardcore.squares_title') },
    { id: 'faq', label: faqTitle },
];

const quickFacts = [
    { label: trans('landing.hardcore.qf_opens'), value: trans('landing.hardcore.qf_opens_value') },
    { label: trans('landing.hardcore.qf_lives'), value: trans('landing.hardcore.qf_lives_value') },
    { label: trans('landing.hardcore.qf_economy'), value: trans('landing.hardcore.qf_economy_value') },
    { label: trans('landing.hardcore.qf_day_one'), value: trans('landing.hardcore.qf_day_one_value') },
];
</script>
