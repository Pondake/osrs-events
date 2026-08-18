<template>
    <Head :title="resolved.title">
        <meta name="description" :content="resolved.description" />
        <link rel="canonical" :href="canonical" />
    </Head>

    <u-main>
        <u-page>
            <u-container class="max-w-3xl py-12">
                <div class="mb-10">
                    <h1 class="text-3xl font-bold text-highlighted">{{ $t('donate.title') }}</h1>
                    <p class="text-muted mt-2">{{ $t('donate.subtitle') }}</p>
                </div>

                <p class="text-lg text-muted leading-relaxed mb-10">
                    {{ $t('donate.intro') }}
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-10">
                    <u-card>
                        <template #header>
                            <span class="text-sm font-medium text-muted">{{ $t('donate.kofi_tag') }}</span>
                        </template>
                        <p class="text-muted leading-relaxed">
                            {{ $t('donate.kofi_desc') }}
                        </p>
                        <template #footer>
                            <u-button href="https://ko-fi.com" target="_blank" color="warning" icon="i-lucide-coffee" :label="$t('donate.kofi_button')" />
                        </template>
                    </u-card>

                    <u-card>
                        <template #header>
                            <span class="text-sm font-medium text-muted">{{ $t('donate.gp_tag') }}</span>
                        </template>
                        <p class="text-muted leading-relaxed mb-2">
                            {{ $t('donate.gp_desc') }}
                        </p>
                        <u-alert
                            color="neutral"
                            variant="subtle"
                            size="sm"
                            :title="$t('donate.gp_warning_title')"
                            :description="$t('donate.gp_warning_desc')"
                        />
                        <template #footer>
                            <u-button href="mailto:dev@absolit.nl?subject=GP Donation" color="neutral" variant="outline" icon="i-lucide-mail" :label="$t('donate.gp_how_cta')" />
                        </template>
                    </u-card>
                </div>

                <u-alert color="primary" variant="subtle" icon="i-lucide-sword" :title="$t('donate.thanks_title')">
                    <template #description>
                        {{ $t('donate.thanks_body') }}
                    </template>
                </u-alert>
            </u-container>
        </u-page>
    </u-main>
</template>

<script setup>
import { trans } from 'laravel-vue-i18n';
import { useSeoData } from '@/Composables/useSeo';

const { resolved, canonical, Head } = useSeoData({
    title: trans('seo.donate_title'),
    description: trans('seo.donate_desc'),
});
</script>
