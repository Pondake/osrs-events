<template>
    <Head :title="resolved.title">
        <meta name="description" :content="resolved.description" />
        <link rel="canonical" :href="canonical" />
    </Head>

    <u-main>
        <u-page>
            <u-container class="max-w-3xl py-12 prose prose-sm dark:prose-invert max-w-none">
                <h1 class="text-3xl font-bold text-highlighted mb-1">{{ $t('privacy.title') }}</h1>
                <p class="text-muted">{{ $t('privacy.subtitle') }}</p>
                <p class="text-sm text-muted">{{ lastUpdatedText }}</p>

                <h2>{{ $t('privacy.intro_title') }}</h2>
                <p>
                    {{ $t('privacy.intro_body') }}
                </p>

                <h2>{{ $t('privacy.data_title') }}</h2>
                <p>{{ $t('privacy.data_intro') }}</p>
                <ul>
                    <li><strong>{{ $t('privacy.data_discord_id') }}</strong> — {{ $t('privacy.data_discord_id_desc') }}</li>
                    <li><strong>{{ $t('privacy.data_username') }}</strong> — {{ $t('privacy.data_username_desc') }}</li>
                    <li><strong>{{ $t('privacy.data_avatar') }}</strong> — {{ $t('privacy.data_avatar_desc') }}</li>
                    <li><strong>{{ $t('privacy.data_nickname') }}</strong> — {{ $t('privacy.data_nickname_desc') }}</li>
                    <li><strong>{{ $t('privacy.data_progress') }}</strong> — {{ $t('privacy.data_progress_desc') }}</li>
                </ul>

                <h2>{{ $t('privacy.no_collect_title') }}</h2>
                <p>{{ $t('privacy.no_collect_body') }}</p>
                <ul>
                    <li>{{ $t('privacy.no_email') }}</li>
                    <li>{{ $t('privacy.no_passwords') }}</li>
                    <li>{{ $t('privacy.no_messages') }}</li>
                    <li>{{ $t('privacy.no_analytics') }}</li>
                    <li>{{ $t('privacy.no_tracking') }}</li>
                    <li>{{ $t('privacy.no_sell') }}</li>
                </ul>

                <h2>{{ $t('privacy.storage_title') }}</h2>
                <p>
                    {{ $t('privacy.storage_body') }}
                </p>

                <h2>{{ $t('privacy.third_party_title') }}</h2>
                <p>{{ $t('privacy.third_party_body') }}</p>
                <p>
                    {{ $t('privacy.discord_note') }}
                    <a href="https://discord.com/privacy" target="_blank" rel="noopener">{{ $t('privacy.discord_policy_link') }}</a>.
                </p>

                <h2>{{ $t('privacy.rights_title') }}</h2>
                <p>
                    {{ $t('privacy.rights_body') }}
                </p>
                <p>
                    {{ $t('privacy.rights_contact_body') }}
                    <u-button href="mailto:dev@absolit.nl?subject=Account deletion request" size="sm" color="primary" variant="outline" :label="$t('privacy.rights_contact_cta')" class="not-prose mt-2" />
                </p>

                <h2>{{ $t('privacy.changes_title') }}</h2>
                <p>
                    {{ $t('privacy.changes_body') }}
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
    title: trans('seo.privacy_title'),
    description: trans('seo.privacy_desc'),
    noindex: false,
});

// Static rather than dynamic — a legal document's "last updated" date should
// change only when its content actually changes, not on every deploy.
const lastUpdated = 'August 2026';
const lastUpdatedText = computed(() => trans('privacy.last_updated', { date: lastUpdated }));
</script>
