<template>
    <seo-head :options="seo" />

    <u-main>
        <u-page>
            <u-page-hero :title="$t('landing.event_ideas.title')" :description="$t('landing.event_ideas.lead')">
                <template #links>
                    <u-button href="/osrs-snakes-and-ladders" size="xl" color="primary" icon="i-lucide-arrow-up-from-line" :label="$t('landing.event_ideas.supported_cta')" />
                </template>
            </u-page-hero>

            <u-page-section>
                <u-container class="max-w-3xl">
                    <p class="text-lg text-muted leading-relaxed">
                        {{ $t('landing.event_ideas.intro') }}
                    </p>
                </u-container>
            </u-page-section>

            <u-page-section :title="$t('landing.event_ideas.formats_title')">
                <u-container class="max-w-3xl">
                    <div class="divide-y divide-default">
                        <div v-for="idea in ideas" :key="idea.title" class="py-6 first:pt-0 last:pb-0">
                            <!-- Stacks on a phone. The badge carries a whole
                                 phrase ("Newer and returning players · 2–4
                                 weeks"), which a u-badge will not break — so
                                 beside the title it pushed the page six
                                 pixels wider than the screen. -->
                            <div class="flex flex-col items-start gap-2 mb-2 sm:flex-row sm:items-center sm:justify-between sm:gap-3">
                                <h3 class="text-lg font-semibold">{{ idea.title }}</h3>
                                <u-badge :label="idea.meta" color="neutral" variant="subtle" size="sm" class="shrink-0" />
                            </div>
                            <p class="text-muted leading-relaxed">{{ idea.desc }}</p>
                        </div>
                    </div>
                </u-container>
            </u-page-section>

            <u-page-section :title="$t('landing.event_ideas.pick_title')">
                <u-container class="max-w-3xl">
                    <p class="text-lg text-muted leading-relaxed">
                        {{ $t('landing.event_ideas.pick_body') }}
                    </p>
                </u-container>
            </u-page-section>

            <u-page-section :title="$t('landing.event_ideas.supported_title')">
                <u-container class="max-w-3xl">
                    <p class="text-lg text-muted leading-relaxed mb-6">
                        {{ $t('landing.event_ideas.supported_body') }}
                    </p>
                    <u-button href="/osrs-snakes-and-ladders" color="primary" variant="outline" trailing-icon="i-lucide-arrow-right" :label="$t('landing.event_ideas.supported_cta')" />
                </u-container>
            </u-page-section>
        </u-page>
    </u-main>
</template>

<script setup>
import { trans } from 'laravel-vue-i18n';
import SeoHead from '@/Components/SeoHead.vue';

const seo = {
    title: trans('landing.event_ideas.meta_title'),
    description: trans('landing.event_ideas.meta_desc'),
};

const ideas = [
    { title: trans('landing.event_ideas.idea1_title'), meta: trans('landing.event_ideas.idea1_meta'), desc: trans('landing.event_ideas.idea1_desc') },
    { title: trans('landing.event_ideas.idea2_title'), meta: trans('landing.event_ideas.idea2_meta'), desc: trans('landing.event_ideas.idea2_desc') },
    { title: trans('landing.event_ideas.idea3_title'), meta: trans('landing.event_ideas.idea3_meta'), desc: trans('landing.event_ideas.idea3_desc') },
    { title: trans('landing.event_ideas.idea4_title'), meta: trans('landing.event_ideas.idea4_meta'), desc: trans('landing.event_ideas.idea4_desc') },
    { title: trans('landing.event_ideas.idea5_title'), meta: trans('landing.event_ideas.idea5_meta'), desc: trans('landing.event_ideas.idea5_desc') },
    { title: trans('landing.event_ideas.idea6_title'), meta: trans('landing.event_ideas.idea6_meta'), desc: trans('landing.event_ideas.idea6_desc') },
    { title: trans('landing.event_ideas.idea7_title'), meta: trans('landing.event_ideas.idea7_meta'), desc: trans('landing.event_ideas.idea7_desc') },
    { title: trans('landing.event_ideas.idea8_title'), meta: trans('landing.event_ideas.idea8_meta'), desc: trans('landing.event_ideas.idea8_desc') },
];
</script>
