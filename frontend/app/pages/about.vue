<template>
  <nuxt-layout :title="$t('about.title')" :description="$t('about.subtitle')">
    <u-page-body>
      <u-container class="max-w-4xl py-8">
        <!-- What we offer -->
        <section class="mb-12">
          <h2 class="text-2xl font-bold osrs-font mb-6">{{ $t('about.offer_title') }}</h2>

          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <u-page-card
              v-for="feature in features"
              :key="feature.key"
              :title="feature.title"
              :description="feature.desc"
              :icon="feature.icon"
              class="osrs-border"
            />
          </div>
        </section>

        <u-separator class="my-8" />

        <!-- Privacy first -->
        <section class="mb-12">
          <div class="flex items-start gap-4">
            <div
              class="shrink-0 w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center"
            >
              <u-icon name="i-lucide-shield-check" class="text-primary text-2xl" />
            </div>

            <div>
              <h2 class="text-2xl font-bold osrs-font mb-3">{{ $t('about.privacy_title') }}</h2>

              <p class="text-muted leading-relaxed mb-4">{{ $t('about.privacy_body') }}</p>

              <u-button
                to="/privacy"
                variant="outline"
                color="primary"
                size="sm"
                icon="i-lucide-arrow-right"
                trailing
                :label="$t('about.privacy_cta')"
              />
            </div>
          </div>
        </section>

        <u-separator class="my-8" />

        <!-- Open & free -->
        <section class="mb-12">
          <div class="flex items-start gap-4">
            <div
              class="shrink-0 w-12 h-12 rounded-xl bg-amber-500/10 flex items-center justify-center"
            >
              <u-icon name="i-lucide-heart" class="text-amber-500 text-2xl" />
            </div>

            <div>
              <h2 class="text-2xl font-bold osrs-font mb-3">{{ $t('about.free_title') }}</h2>

              <p class="text-muted leading-relaxed mb-4">{{ $t('about.free_body') }}</p>

              <u-button
                to="/donate"
                variant="outline"
                color="warning"
                size="sm"
                icon="i-lucide-coffee"
                trailing
                :label="$t('about.donate_cta')"
              />
            </div>
          </div>
        </section>

        <u-separator class="my-8" />

        <!-- Support -->
        <section class="mb-12">
          <div class="flex items-start gap-4">
            <div
              class="shrink-0 w-12 h-12 rounded-xl bg-sky-500/10 flex items-center justify-center"
            >
              <u-icon name="i-lucide-life-buoy" class="text-sky-500 text-2xl" />
            </div>

            <div>
              <h2 class="text-2xl font-bold osrs-font mb-3">{{ $t('about.support_title') }}</h2>

              <p class="text-muted leading-relaxed mb-4">{{ $t('about.support_body') }}</p>

              <div class="flex flex-wrap gap-3">
                <u-button
                  to="mailto:dev@absolit.nl"
                  variant="outline"
                  color="primary"
                  size="sm"
                  icon="i-lucide-mail"
                  :label="$t('about.support_email')"
                />
              </div>
            </div>
          </div>
        </section>

        <u-separator class="my-8" />

        <!-- Disclaimer -->
        <u-alert
          color="warning"
          variant="subtle"
          icon="i-lucide-alert-triangle"
          :title="$t('about.disclaimer_title')"
          :description="$t('about.disclaimer_body')"
        />
      </u-container>
    </u-page-body>
  </nuxt-layout>
</template>

<script setup lang="ts">
const { t } = useI18n();

useSeo({ title: t('seo.about_title'), description: t('seo.about_desc') });

const features = [
  {
    key: 'boards',
    icon: 'i-lucide-grid-3x3',
    title: t('about.feature_boards_title'),
    desc: t('about.feature_boards_desc'),
  },
  {
    key: 'dice',
    icon: 'i-lucide-dice-6',
    title: t('about.feature_dice_title'),
    desc: t('about.feature_dice_desc'),
  },
  {
    key: 'tasks',
    icon: 'i-lucide-check-square',
    title: t('about.feature_tasks_title'),
    desc: t('about.feature_tasks_desc'),
  },
  {
    key: 'discord',
    icon: 'i-lucide-message-circle',
    title: t('about.feature_discord_title'),
    desc: t('about.feature_discord_desc'),
  },
  {
    key: 'dark_mode',
    icon: 'i-lucide-moon',
    title: t('about.feature_dark_title'),
    desc: t('about.feature_dark_desc'),
  },
  {
    key: 'free',
    icon: 'i-lucide-heart',
    title: t('about.feature_free_title'),
    desc: t('about.feature_free_desc'),
  },
];
</script>
