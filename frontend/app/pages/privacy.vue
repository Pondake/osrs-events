<template>
  <nuxt-layout :title="$t('privacy.title')" :description="$t('privacy.subtitle')">
    <u-page-body>
      <u-container class="max-w-3xl prose prose-stone dark:prose-invert py-8">
        <p class="text-sm text-muted mb-8">
          {{ $t('privacy.last_updated', { date: 'March 2026' }) }}
        </p>

        <!-- 1. Introduction -->
        <section class="mb-8">
          <h2 class="text-xl font-bold osrs-font mb-3">{{ $t('privacy.intro_title') }}</h2>

          <p class="text-muted leading-relaxed">{{ $t('privacy.intro_body') }}</p>
        </section>

        <u-separator class="my-6" />

        <!-- 2. Data we collect -->
        <section class="mb-8">
          <h2 class="text-xl font-bold osrs-font mb-3">{{ $t('privacy.data_title') }}</h2>

          <p class="text-muted leading-relaxed mb-4">{{ $t('privacy.data_intro') }}</p>

          <div class="space-y-3">
            <u-card v-for="item in dataItems" :key="item.key" class="osrs-border">
              <div class="flex items-start gap-3">
                <u-icon :name="item.icon" class="text-primary text-xl shrink-0 mt-0.5" />

                <div>
                  <p class="font-semibold text-sm">{{ item.label }}</p>

                  <p class="text-muted text-sm mt-0.5">{{ item.desc }}</p>
                </div>
              </div>
            </u-card>
          </div>
        </section>

        <u-separator class="my-6" />

        <!-- 3. What we do NOT collect -->
        <section class="mb-8">
          <h2 class="text-xl font-bold osrs-font mb-3">{{ $t('privacy.no_collect_title') }}</h2>

          <p class="text-muted leading-relaxed mb-4">{{ $t('privacy.no_collect_body') }}</p>

          <ul class="space-y-1 text-muted text-sm list-none pl-0">
            <li v-for="item in noCollectItems" :key="item" class="flex items-center gap-2">
              <u-icon name="i-lucide-x-circle" class="text-error shrink-0" />
              {{ item }}
            </li>
          </ul>
        </section>

        <u-separator class="my-6" />

        <!-- 4. How data is stored -->
        <section class="mb-8">
          <h2 class="text-xl font-bold osrs-font mb-3">{{ $t('privacy.storage_title') }}</h2>

          <p class="text-muted leading-relaxed">{{ $t('privacy.storage_body') }}</p>
        </section>

        <u-separator class="my-6" />

        <!-- 5. Third parties -->
        <section class="mb-8">
          <h2 class="text-xl font-bold osrs-font mb-3">{{ $t('privacy.third_party_title') }}</h2>

          <p class="text-muted leading-relaxed mb-3">{{ $t('privacy.third_party_body') }}</p>

          <u-card class="osrs-border">
            <div class="flex items-start gap-3">
              <u-icon
                name="i-lucide-message-circle"
                class="text-indigo-400 text-xl shrink-0 mt-0.5"
              />

              <div>
                <p class="font-semibold text-sm">Discord</p>

                <p class="text-muted text-sm mt-0.5">{{ $t('privacy.discord_note') }}</p>

                <u-button
                  to="https://discord.com/privacy"
                  target="_blank"
                  variant="link"
                  size="xs"
                  color="primary"
                  class="mt-1 px-0"
                >
                  {{ $t('privacy.discord_policy_link') }}
                  <u-icon name="i-lucide-external-link" class="ml-1" />
                </u-button>
              </div>
            </div>
          </u-card>
        </section>

        <u-separator class="my-6" />

        <!-- 6. Your rights -->
        <section class="mb-8">
          <h2 class="text-xl font-bold osrs-font mb-3">{{ $t('privacy.rights_title') }}</h2>

          <p class="text-muted leading-relaxed mb-4">{{ $t('privacy.rights_body') }}</p>

          <u-card class="osrs-border">
            <div class="flex items-start gap-3">
              <u-icon name="i-lucide-info" class="text-primary text-xl shrink-0 mt-0.5" />

              <div class="flex-1">
                <p class="font-semibold text-sm mb-1">{{ $t('privacy.rights_contact_title') }}</p>

                <p class="text-muted text-sm leading-relaxed mb-3">
                  {{ $t('privacy.rights_contact_body') }}
                </p>

                <u-button
                  to="mailto:dev@absolit.nl?subject=Account deletion request"
                  size="sm"
                  color="primary"
                  variant="outline"
                  icon="i-lucide-mail"
                  :label="$t('privacy.rights_contact_cta')"
                />
              </div>
            </div>
          </u-card>
        </section>

        <u-separator class="my-6" />

        <!-- 7. Changes -->
        <section class="mb-8">
          <h2 class="text-xl font-bold osrs-font mb-3">{{ $t('privacy.changes_title') }}</h2>

          <p class="text-muted leading-relaxed">{{ $t('privacy.changes_body') }}</p>
        </section>
      </u-container>
    </u-page-body>
  </nuxt-layout>
</template>

<script setup lang="ts">
const { t } = useI18n();

useSeo({ title: t('seo.privacy_title'), description: t('seo.privacy_desc') });

const dataItems = [
  {
    key: 'discord_id',
    icon: 'i-lucide-fingerprint',
    label: t('privacy.data_discord_id'),
    desc: t('privacy.data_discord_id_desc'),
  },
  {
    key: 'username',
    icon: 'i-lucide-user',
    label: t('privacy.data_username'),
    desc: t('privacy.data_username_desc'),
  },
  {
    key: 'avatar',
    icon: 'i-lucide-image',
    label: t('privacy.data_avatar'),
    desc: t('privacy.data_avatar_desc'),
  },
  {
    key: 'nickname',
    icon: 'i-lucide-pencil',
    label: t('privacy.data_nickname'),
    desc: t('privacy.data_nickname_desc'),
  },
  {
    key: 'progress',
    icon: 'i-lucide-bar-chart-2',
    label: t('privacy.data_progress'),
    desc: t('privacy.data_progress_desc'),
  },
];

const noCollectItems = [
  t('privacy.no_email'),
  t('privacy.no_passwords'),
  t('privacy.no_messages'),
  t('privacy.no_analytics'),
  t('privacy.no_tracking'),
  t('privacy.no_sell'),
];
</script>
