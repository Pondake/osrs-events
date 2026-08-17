<template>
  <u-main>
    <u-page>
      <u-page-hero
        :title="$t('landing.clan_events.title')"
        :description="$t('landing.clan_events.lead')"
      >
        <template #links>
          <u-button
            size="xl"
            color="primary"
            icon="i-simple-icons-discord"
            :label="ctaLabel"
            @click="startBoard"
          />

          <u-button
            to="/osrs-snakes-and-ladders"
            size="xl"
            color="neutral"
            variant="outline"
            trailing-icon="i-lucide-arrow-right"
            :label="$t('landing.event_ideas.supported_cta')"
          />
        </template>
      </u-page-hero>

      <u-page-section :title="$t('landing.clan_events.why_title')">
        <u-container class="max-w-3xl">
          <p class="text-lg text-muted leading-relaxed">{{ $t('landing.clan_events.why_body') }}</p>
        </u-container>
      </u-page-section>

      <u-page-section
        :title="$t('landing.clan_events.what_title')"
        :description="$t('landing.clan_events.what_subtitle')"
        :features="features"
      />

      <u-page-section
        :title="$t('landing.clan_events.access_title')"
        :description="$t('landing.clan_events.access_subtitle')"
        :features="accessModes"
      />

      <u-page-section :title="$t('landing.clan_events.setup_title')">
        <u-container class="max-w-3xl">
          <p class="text-lg text-muted leading-relaxed">
            {{ $t('landing.clan_events.setup_body') }}
          </p>

          <u-button
            to="/osrs-event-ideas"
            class="mt-6"
            color="primary"
            variant="outline"
            trailing-icon="i-lucide-arrow-right"
            :label="$t('landing.event_ideas.title')"
          />
        </u-container>
      </u-page-section>

      <u-page-section :title="$t('landing.faq_title')">
        <u-container class="max-w-3xl">
          <dl class="divide-y divide-default">
            <div v-for="faq in faqs" :key="faq.question" class="py-6 first:pt-0 last:pb-0">
              <dt class="text-lg font-semibold osrs-font">{{ faq.question }}</dt>

              <dd class="mt-2 text-muted leading-relaxed">{{ faq.answer }}</dd>
            </div>
          </dl>
        </u-container>
      </u-page-section>
    </u-page>
  </u-main>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth';

const { t } = useI18n();
const authStore = useAuthStore();

const ctaLabel = computed(() =>
  authStore.isAuthenticated ? t('landing.cta_create') : t('landing.cta_login'),
);

const features = [
  { key: 'boards', icon: 'i-lucide-layout-grid' },
  { key: 'discord', icon: 'i-simple-icons-discord' },
  { key: 'teams', icon: 'i-lucide-users' },
  { key: 'invites', icon: 'i-lucide-link' },
  { key: 'leaderboard', icon: 'i-lucide-trophy' },
  { key: 'free', icon: 'i-lucide-heart' },
].map(({ key, icon }) => ({
  icon,
  title: t(`landing.clan_events.feature_${key}_title`),
  description: t(`landing.clan_events.feature_${key}_desc`),
}));

const accessModes = [
  { key: 'open', icon: 'i-lucide-globe' },
  { key: 'guild', icon: 'i-lucide-shield-check' },
  { key: 'invite', icon: 'i-lucide-key-round' },
].map(({ key, icon }) => ({
  icon,
  title: t(`landing.clan_events.access_${key}_title`),
  description: t(`landing.clan_events.access_${key}_desc`),
}));

const faqs = Array.from({ length: 5 }, (_, i) => ({
  question: t(`landing.clan_events.faq_q${i + 1}`),
  answer: t(`landing.clan_events.faq_a${i + 1}`),
}));

function startBoard() {
  if (authStore.isAuthenticated) {
    navigateTo('/boards');
    return;
  }
  authStore.loginWithDiscord();
}

useSeo({
  title: t('landing.clan_events.meta_title'),
  description: t('landing.clan_events.meta_desc'),
  jsonLd: {
    '@type': 'FAQPage',
    mainEntity: faqs.map(faq => ({
      '@type': 'Question',
      name: faq.question,
      acceptedAnswer: { '@type': 'Answer', text: faq.answer },
    })),
  },
});
</script>
