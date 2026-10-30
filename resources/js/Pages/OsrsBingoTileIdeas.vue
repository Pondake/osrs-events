<template>
    <seo-head :options="seo" />

    <guide-layout
        current-path="/osrs-bingo-tile-ideas"
        :title="$t('landing.tile_ideas.title')"
        :description="$t('landing.tile_ideas.lead')"
        :sections="sections"
        :quick-facts="quickFacts"
    >
        <template #cta>
            <u-button href="/osrs-bingo" color="primary" icon="i-lucide-grid-3x3" :label="$t('landing.tile_ideas.cta_bingo')" />
            <u-button to="/events" color="neutral" variant="outline" trailing-icon="i-lucide-arrow-right" :label="$t('landing.cta_browse')" />
        </template>

        <p :class="prose.p">{{ $t('landing.tile_ideas.intro') }}</p>

        <section id="balance">
            <h2 :class="prose.h2">{{ $t('landing.tile_ideas.balance_title') }}</h2>
            <p :class="prose.p">{{ $t('landing.tile_ideas.balance_intro') }}</p>

            <ol class="list-decimal list-inside space-y-2" :class="prose.list">
                <li v-for="tip in tips" :key="tip.title">
                    <span class="font-medium text-highlighted">{{ tip.title }}</span>
                    <span class="text-muted"> — {{ tip.description }}</span>
                </li>
            </ol>
        </section>

        <section v-for="tier in tiers" :id="tier.id" :key="tier.id">
            <h2 :class="prose.h2" class="flex items-center gap-2">
                <u-icon :name="tier.icon" class="size-6 text-primary shrink-0" />
                {{ tier.title }}
            </h2>
            <p :class="prose.p">{{ tier.intro }}</p>

            <ul class="divide-y divide-default">
                <li v-for="tile in tier.tiles" :key="tile.title" class="py-3 first:pt-0 last:pb-0">
                    <h3 class="font-semibold text-highlighted">{{ tile.title }}</h3>
                    <p class="text-muted leading-relaxed mt-0.5">{{ tile.description }}</p>
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
import { GUIDE_PROSE } from '@/Support/guides';

const prose = GUIDE_PROSE;

const props = defineProps({
    tips: { type: Array, required: true },
    tiers: { type: Array, required: true },
    faqs: { type: Array, required: true },
});

const seo = {
    title: trans('landing.tile_ideas.meta_title'),
    description: trans('landing.tile_ideas.meta_desc'),
    image: 'bingo',
};

const faqTitle = trans('landing.faq_title');

const sections = [
    { id: 'balance', label: trans('landing.tile_ideas.balance_title') },
    ...props.tiers.map((tier) => ({ id: tier.id, label: tier.title })),
    { id: 'faq', label: faqTitle },
];

const tileCount = props.tiers.reduce((sum, tier) => sum + tier.tiles.length, 0);

const quickFacts = [
    { label: trans('landing.tile_ideas.fact_tiles'), value: String(tileCount) },
    { label: trans('landing.tile_ideas.fact_tiers'), value: trans('landing.tile_ideas.fact_tiers_value') },
    { label: trans('landing.tile_ideas.fact_size'), value: '5×5' },
];
</script>
