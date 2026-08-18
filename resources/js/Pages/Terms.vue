<template>
    <Head :title="resolved.title">
        <meta name="description" :content="resolved.description" />
        <link rel="canonical" :href="canonical" />
    </Head>

    <u-main>
        <u-page>
            <u-container class="max-w-3xl py-12 prose prose-sm dark:prose-invert max-w-none">
                <h1 class="text-3xl font-bold text-highlighted mb-1">{{ $t('terms.title') }}</h1>
                <p class="text-muted">{{ $t('terms.subtitle') }}</p>
                <p class="text-sm text-muted">{{ lastUpdatedText }}</p>

                <h2>{{ $t('terms.acceptance_title') }}</h2>
                <p>
                    {{ $t('terms.acceptance_body') }}
                </p>

                <h2>{{ $t('terms.service_title') }}</h2>
                <p>
                    {{ $t('terms.service_body') }}
                </p>

                <h2>{{ $t('terms.accounts_title') }}</h2>
                <p>{{ $t('terms.accounts_body') }}</p>
                <ul>
                    <li>{{ $t('terms.account_rule_discord') }}</li>
                    <li>{{ $t('terms.account_rule_one') }}</li>
                    <li>{{ $t('terms.account_rule_accurate') }}</li>
                </ul>

                <h2>{{ $t('terms.conduct_title') }}</h2>
                <p>{{ $t('terms.conduct_body') }}</p>
                <ul>
                    <li>{{ $t('terms.conduct_rule_abuse') }}</li>
                    <li>{{ $t('terms.conduct_rule_scrape') }}</li>
                    <li>{{ $t('terms.conduct_rule_exploit') }}</li>
                    <li>{{ $t('terms.conduct_rule_impersonate') }}</li>
                </ul>

                <h2>{{ $t('terms.ip_title') }}</h2>
                <p>
                    {{ $t('terms.ip_body') }}
                </p>

                <h2>{{ $t('terms.jagex_title') }}</h2>
                <p>
                    {{ $t('terms.jagex_body') }}
                </p>

                <h2>{{ $t('terms.disclaimer_title') }}</h2>
                <p>
                    {{ $t('terms.disclaimer_body') }}
                </p>

                <h2>{{ $t('terms.termination_title') }}</h2>
                <p>
                    {{ $t('terms.termination_body') }}
                </p>

                <h2>{{ $t('terms.changes_title') }}</h2>
                <p>
                    {{ $t('terms.changes_body') }}
                </p>
            </u-container>
        </u-page>
    </u-main>
</template>

<script setup>
import { computed } from 'vue';
import { trans } from 'laravel-vue-i18n';
import { useSeoData } from '@/Composables/useSeo';

const { resolved, canonical, Head } = useSeoData({
    title: trans('seo.terms_title'),
    description: trans('seo.terms_desc'),
});

const lastUpdated = 'August 2026';
const lastUpdatedText = computed(() => trans('terms.last_updated', { date: lastUpdated }));
</script>
