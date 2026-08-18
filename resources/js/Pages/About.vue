<template>
    <Head :title="resolved.title">
        <meta name="description" :content="resolved.description" />
        <link rel="canonical" :href="canonical" />
    </Head>

    <u-main>
        <u-page>
            <u-container class="max-w-4xl py-12">
                <div class="mb-10">
                    <h1 class="text-3xl font-bold text-highlighted">{{ $t('about.title') }}</h1>
                    <p class="text-muted mt-2">{{ $t('about.subtitle') }}</p>
                </div>

                <section class="mb-12">
                    <h2 class="text-2xl font-bold mb-6">{{ $t('about.offer_title') }}</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <u-page-card
                            v-for="feature in features"
                            :key="feature.title"
                            :title="feature.title"
                            :description="feature.desc"
                            :icon="feature.icon"
                        />
                    </div>
                </section>

                <u-separator class="my-8" />

                <section class="mb-12 flex items-start gap-4">
                    <div class="shrink-0 w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center">
                        <u-icon name="i-lucide-shield-check" class="text-primary text-2xl" />
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold mb-3">{{ $t('about.privacy_title') }}</h2>
                        <p class="text-muted leading-relaxed mb-4">
                            {{ $t('about.privacy_body') }}
                        </p>
                        <u-button href="/privacy" variant="outline" color="primary" size="sm" icon="i-lucide-arrow-right" trailing :label="$t('about.privacy_cta')" />
                    </div>
                </section>

                <u-separator class="my-8" />

                <section class="mb-12 flex items-start gap-4">
                    <div class="shrink-0 w-12 h-12 rounded-xl bg-amber-500/10 flex items-center justify-center">
                        <u-icon name="i-lucide-heart" class="text-amber-500 text-2xl" />
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold mb-3">{{ $t('about.free_title') }}</h2>
                        <p class="text-muted leading-relaxed mb-4">
                            {{ $t('about.free_body') }}
                        </p>
                        <u-button href="/donate" variant="outline" color="warning" size="sm" icon="i-lucide-coffee" trailing :label="$t('about.donate_cta')" />
                    </div>
                </section>

                <u-separator class="my-8" />

                <section class="mb-12 flex items-start gap-4">
                    <div class="shrink-0 w-12 h-12 rounded-xl bg-sky-500/10 flex items-center justify-center">
                        <u-icon name="i-lucide-life-buoy" class="text-sky-500 text-2xl" />
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold mb-3">{{ $t('about.support_title') }}</h2>
                        <p class="text-muted leading-relaxed mb-4">
                            {{ $t('about.support_body') }}
                        </p>
                        <u-button href="mailto:dev@absolit.nl" variant="outline" color="primary" size="sm" icon="i-lucide-mail" :label="$t('about.support_email')" />
                    </div>
                </section>

                <u-separator class="my-8" />

                <u-alert
                    color="warning"
                    variant="subtle"
                    icon="i-lucide-alert-triangle"
                    :title="$t('about.disclaimer_title')"
                    :description="$t('about.disclaimer_body')"
                />
            </u-container>
        </u-page>
    </u-main>
</template>

<script setup>
import { trans } from 'laravel-vue-i18n';
import { useSeoData } from '@/Composables/useSeo';

const { resolved, canonical, Head } = useSeoData({
    title: trans('seo.about_title'),
    description: trans('seo.about_desc'),
});

const features = [
    { icon: 'i-lucide-grid-3x3', title: trans('about.feature_boards_title'), desc: trans('about.feature_boards_desc') },
    { icon: 'i-lucide-dice-6', title: trans('about.feature_dice_title'), desc: trans('about.feature_dice_desc') },
    { icon: 'i-lucide-check-square', title: trans('about.feature_tasks_title'), desc: trans('about.feature_tasks_desc') },
    { icon: 'i-lucide-message-circle', title: trans('about.feature_discord_title'), desc: trans('about.feature_discord_desc') },
    { icon: 'i-lucide-moon', title: trans('about.feature_dark_title'), desc: trans('about.feature_dark_desc') },
    { icon: 'i-lucide-heart', title: trans('about.feature_free_title'), desc: trans('about.feature_free_desc') },
];
</script>
