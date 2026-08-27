<template>
    <seo-head :options="seo" />

    <guide-layout
        current-path="/osrs-event-ideas"
        :title="$t('landing.event_ideas.title')"
        :description="$t('landing.event_ideas.lead')"
        :sections="sections"
        :quick-facts="quickFacts"
    >
        <template #cta>
            <u-button href="/osrs-snakes-and-ladders" color="primary" icon="i-lucide-arrow-up-from-line" :label="$t('landing.event_ideas.supported_cta')" />
        </template>

        <p :class="prose.p">{{ $t('landing.event_ideas.intro') }}</p>

        <section id="formats">
            <h2 :class="prose.h2">{{ $t('landing.event_ideas.formats_title') }}</h2>

            <div class="divide-y divide-default">
                <div v-for="idea in ideas" :key="idea.title" class="py-5 first:pt-0 last:pb-0">
                    <div class="flex flex-col items-start gap-1 mb-1 sm:flex-row sm:items-baseline sm:justify-between sm:gap-3">
                        <span class="flex items-center gap-2">
                            <h3 class="font-semibold text-highlighted">{{ idea.title }}</h3>
                            <!-- Four of these eight formats aren't built —
                                 the page used to list them indistinguishably
                                 from Snakes & Ladders/Bingo/Skill Race/Drop
                                 Race, which reads as a promise rather than an
                                 idea. Same badge and copy as the nav's
                                 "Soon" entries, for the same reason. -->
                            <u-badge v-if="idea.soon" :label="$t('nav.badge_soon')" color="neutral" variant="subtle" size="sm" />
                        </span>
                        <span class="text-xs text-dimmed shrink-0">{{ idea.meta }}</span>
                    </div>
                    <p class="text-muted leading-relaxed">{{ idea.desc }}</p>
                </div>
            </div>
        </section>

        <section id="pick">
            <h2 :class="prose.h2">{{ $t('landing.event_ideas.pick_title') }}</h2>
            <p :class="prose.p">{{ $t('landing.event_ideas.pick_body') }}</p>
        </section>

        <section id="supported">
            <h2 :class="prose.h2">{{ $t('landing.event_ideas.supported_title') }}</h2>
            <p :class="prose.p">{{ $t('landing.event_ideas.supported_body') }}</p>
            <u-button href="/osrs-snakes-and-ladders" color="primary" variant="outline" trailing-icon="i-lucide-arrow-right" :label="$t('landing.event_ideas.supported_cta')" />
        </section>
    </guide-layout>
</template>

<script setup>
import { trans } from 'laravel-vue-i18n';
import SeoHead from '@/Components/SeoHead.vue';
import GuideLayout from '@/Components/GuideLayout.vue';
import { GUIDE_PROSE } from '@/Support/guides';

const prose = GUIDE_PROSE;

const seo = {
    title: trans('landing.event_ideas.meta_title'),
    description: trans('landing.event_ideas.meta_desc'),
};

const sections = [
    { id: 'formats', label: trans('landing.event_ideas.formats_title') },
    { id: 'pick', label: trans('landing.event_ideas.pick_title') },
    { id: 'supported', label: trans('landing.event_ideas.supported_title') },
];

const quickFacts = [
    { label: trans('landing.event_ideas.fact_compared'), value: trans('landing.event_ideas.fact_compared_value') },
    { label: trans('landing.event_ideas.fact_supported'), value: trans('landing.event_ideas.fact_supported_value') },
];

// Ideas 5–8 aren't built — Speedrun ladder, Achievement diary/quest race,
// Battleship and Collection log push have no event type behind them (see
// Event::EVENT_TYPES). Filed to docs/backlog.md as real future work rather
// than left as a page that quietly implied all eight already existed.
const ideas = [
    { title: trans('landing.event_ideas.idea1_title'), meta: trans('landing.event_ideas.idea1_meta'), desc: trans('landing.event_ideas.idea1_desc') },
    { title: trans('landing.event_ideas.idea2_title'), meta: trans('landing.event_ideas.idea2_meta'), desc: trans('landing.event_ideas.idea2_desc') },
    { title: trans('landing.event_ideas.idea3_title'), meta: trans('landing.event_ideas.idea3_meta'), desc: trans('landing.event_ideas.idea3_desc') },
    { title: trans('landing.event_ideas.idea4_title'), meta: trans('landing.event_ideas.idea4_meta'), desc: trans('landing.event_ideas.idea4_desc') },
    { title: trans('landing.event_ideas.idea5_title'), meta: trans('landing.event_ideas.idea5_meta'), desc: trans('landing.event_ideas.idea5_desc'), soon: true },
    { title: trans('landing.event_ideas.idea6_title'), meta: trans('landing.event_ideas.idea6_meta'), desc: trans('landing.event_ideas.idea6_desc'), soon: true },
    { title: trans('landing.event_ideas.idea7_title'), meta: trans('landing.event_ideas.idea7_meta'), desc: trans('landing.event_ideas.idea7_desc'), soon: true },
    { title: trans('landing.event_ideas.idea8_title'), meta: trans('landing.event_ideas.idea8_meta'), desc: trans('landing.event_ideas.idea8_desc'), soon: true },
];
</script>
