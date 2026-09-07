<template>
    <!-- :title as a Head PROP rather than a literal <title> child, for the
         same reason the guide pages use it — a child element double-applies
         against createInertiaApp's title callback during hydration. -->
    <Head :title="resolved.title">
        <meta name="description" :content="resolved.description" />
        <link rel="canonical" :href="canonical" />

        <!-- noindex, follow. This page describes a beta nobody can join
             without a password that is not written here, so a search result
             pointing at it costs a visitor a click and gives them nothing —
             the same reasoning the lock screen itself uses. `follow` because
             the guides it links to ARE meant to be indexed. It is also
             absent from SitemapController on purpose. -->
        <meta name="robots" :content="robots" />

        <meta property="og:title" :content="resolved.title" />
        <meta property="og:description" :content="resolved.description" />
        <meta property="og:type" content="website" />
        <meta property="og:url" :content="canonical" />
        <meta property="og:site_name" content="OSRS Events" />
        <meta property="og:image" :content="imageUrl" />
        <meta property="og:image:width" content="1200" />
        <meta property="og:image:height" content="630" />
        <meta property="og:image:alt" :content="resolved.title" />

        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="resolved.title" />
        <meta name="twitter:description" :content="resolved.description" />
        <meta name="twitter:image" :content="imageUrl" />
    </Head>

    <guide-layout
        current-path="/beta"
        :title="$t('beta.title')"
        :description="$t('beta.lead')"
        :sections="sections"
        :quick-facts="quickFacts"
    >
        <template #cta>
            <!-- The door first, and only while it is actually shut for this
                 visitor. Somebody already through it does not need to be
                 sent back to a password box, and an admin never did. -->
            <u-button v-if="locked" href="/locked" color="primary" icon="i-lucide-lock" :label="$t('beta.cta_door')" />

            <!-- Rendered only when an admin has set one. A "join our Discord"
                 button that goes nowhere is worse than no button: it reads as
                 broken rather than as not-yet. -->
            <u-button
                v-if="discordInviteUrl"
                :href="discordInviteUrl"
                target="_blank"
                rel="noopener"
                color="neutral"
                variant="outline"
                icon="i-simple-icons-discord"
                :label="$t('beta.cta_discord')"
            />

            <u-button href="/osrs-clan-events" color="neutral" variant="ghost" trailing-icon="i-lucide-arrow-right" :label="$t('beta.cta_guides')" />
        </template>

        <section id="what">
            <h2 :class="prose.h2">{{ whatTitle }}</h2>
            <p :class="prose.p">{{ $t('beta.what_body') }}</p>

            <ul class="space-y-3" :class="prose.list">
                <li v-for="point in expectations" :key="point" class="flex gap-3">
                    <u-icon name="i-lucide-info" class="size-5 text-primary shrink-0 mt-0.5" />
                    <span class="text-muted">{{ point }}</span>
                </li>
            </ul>
        </section>

        <section id="access">
            <h2 :class="prose.h2">{{ accessTitle }}</h2>
            <p :class="prose.p">{{ $t('beta.access_subtitle') }}</p>

            <ol class="list-decimal list-inside" :class="prose.list">
                <li v-for="step in accessSteps" :key="step.title">
                    <span class="font-medium text-highlighted">{{ step.title }}</span>
                    <span class="text-muted"> — {{ step.description }}</span>
                </li>
            </ol>
        </section>

        <!-- Two tracks with their own numbering, copied from the Snakes &
             Ladders guide rather than reinvented: hosting and playing are
             different jobs, and one flat list of nine steps hides the moment
             the audience changes. -->
        <section id="test">
            <h2 :class="prose.h2">{{ testTitle }}</h2>

            <div class="grid md:grid-cols-2 gap-x-8">
                <div>
                    <h3 :class="prose.h3">{{ $t('beta.host_title') }}</h3>
                    <p :class="prose.p">{{ $t('beta.host_subtitle') }}</p>

                    <ol class="list-decimal list-inside" :class="prose.list">
                        <li v-for="step in hostSteps" :key="step.title">
                            <span class="font-medium text-highlighted">{{ step.title }}</span>
                            <span class="text-muted"> — {{ step.description }}</span>
                        </li>
                    </ol>
                </div>

                <div>
                    <h3 :class="prose.h3">{{ $t('beta.player_title') }}</h3>
                    <p :class="prose.p">{{ $t('beta.player_subtitle') }}</p>

                    <ol class="list-decimal list-inside" :class="prose.list">
                        <li v-for="step in playerSteps" :key="step.title">
                            <span class="font-medium text-highlighted">{{ step.title }}</span>
                            <span class="text-muted"> — {{ step.description }}</span>
                        </li>
                    </ol>
                </div>
            </div>
        </section>

        <section id="report">
            <h2 :class="prose.h2">{{ reportTitle }}</h2>
            <p :class="prose.p">{{ $t('beta.report_body') }}</p>

            <h3 :class="prose.h3">{{ $t('beta.report_points_title') }}</h3>
            <ul class="space-y-2" :class="prose.list">
                <li v-for="point in reportPoints" :key="point" class="flex gap-3">
                    <u-icon name="i-lucide-check" class="size-5 text-primary shrink-0 mt-0.5" />
                    <span class="text-muted">{{ point }}</span>
                </li>
            </ul>

            <u-alert
                color="neutral"
                variant="subtle"
                icon="i-lucide-message-circle"
                class="mt-4"
                :description="$t('beta.report_note')"
            />
        </section>

        <section id="known">
            <h2 :class="prose.h2">{{ knownTitle }}</h2>
            <p :class="prose.p">{{ $t('beta.known_body') }}</p>

            <ul class="space-y-2" :class="prose.list">
                <li v-for="issue in knownIssues" :key="issue" class="flex gap-3">
                    <u-icon name="i-lucide-minus" class="size-5 text-dimmed shrink-0 mt-0.5" />
                    <span class="text-muted">{{ issue }}</span>
                </li>
            </ul>
        </section>

        <section id="rules">
            <h2 :class="prose.h2">{{ rulesTitle }}</h2>

            <ul class="space-y-3" :class="prose.list">
                <li v-for="rule in groundRules" :key="rule" class="flex gap-3">
                    <u-icon name="i-lucide-shield" class="size-5 text-warning shrink-0 mt-0.5" />
                    <span class="text-muted">{{ rule }}</span>
                </li>
            </ul>
        </section>
    </guide-layout>
</template>

<script setup>
import { computed } from 'vue';
import { trans } from 'laravel-vue-i18n';
import { useSeoData } from '@/Composables/useSeo';
import { useSiteLock } from '@/Composables/useSiteLock';
import { useCurrentPage } from '@/Support/pageState';
import GuideLayout from '@/Components/GuideLayout.vue';
import { GUIDE_PROSE } from '@/Support/guides';

defineProps({
    accessSteps: { type: Array, required: true },
    hostSteps: { type: Array, required: true },
    playerSteps: { type: Array, required: true },
    expectations: { type: Array, required: true },
    reportPoints: { type: Array, required: true },
    knownIssues: { type: Array, required: true },
    groundRules: { type: Array, required: true },
});

const { locked } = useSiteLock();

const page = useCurrentPage();

// Null on a fresh install and on any environment where nobody has set one —
// the template renders no button in that case rather than a dead link.
const discordInviteUrl = computed(() => page.value.props?.site?.discordInviteUrl ?? null);

const prose = GUIDE_PROSE;

const { resolved, canonical, imageUrl, robots, Head } = useSeoData({
    title: trans('beta.meta_title'),
    description: trans('beta.meta_desc'),
    noindex: true,
});

const whatTitle = trans('beta.what_title');
const accessTitle = trans('beta.access_title');
const testTitle = trans('beta.test_title');
const reportTitle = trans('beta.report_title');
const knownTitle = trans('beta.known_title');
const rulesTitle = trans('beta.rules_title');

const sections = [
    { id: 'what', label: whatTitle },
    { id: 'access', label: accessTitle },
    { id: 'test', label: testTitle },
    { id: 'report', label: reportTitle },
    { id: 'known', label: knownTitle },
    { id: 'rules', label: rulesTitle },
];

const quickFacts = [
    { label: trans('beta.fact_cost'), value: trans('beta.fact_cost_value') },
    { label: trans('beta.fact_login'), value: trans('beta.fact_login_value') },
    { label: trans('beta.fact_report'), value: trans('beta.fact_report_value') },
    { label: trans('beta.fact_wipes'), value: trans('beta.fact_wipes_value') },
];
</script>
