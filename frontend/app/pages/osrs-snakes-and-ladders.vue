<template>
  <u-main>
    <u-page>
      <u-page-hero :title="$t('landing.snakes.title')" :description="$t('landing.snakes.lead')">
        <template #links>
          <u-button
            size="xl"
            color="primary"
            icon="i-lucide-plus"
            :label="$t('landing.cta_create')"
            @click="startBoard"
          />

          <u-button
            to="/boards"
            size="xl"
            color="neutral"
            variant="outline"
            trailing-icon="i-lucide-arrow-right"
            :label="$t('landing.cta_browse')"
          />
        </template>
      </u-page-hero>

      <u-page-section
        :title="$t('landing.snakes.how_title')"
        :description="$t('landing.snakes.how_subtitle')"
        :features="steps"
      />

      <u-page-section :title="$t('landing.snakes.why_title')">
        <u-container class="max-w-3xl">
          <p class="text-lg text-muted leading-relaxed">{{ $t('landing.snakes.why_body') }}</p>
        </u-container>
      </u-page-section>

      <u-page-section
        :title="$t('landing.snakes.sizes_title')"
        :description="$t('landing.snakes.sizes_subtitle')"
        :features="sizes"
      />

      <u-page-section :title="$t('landing.snakes.modes_title')">
        <u-container class="max-w-3xl">
          <p class="text-lg text-muted leading-relaxed">{{ $t('landing.snakes.modes_body') }}</p>
        </u-container>
      </u-page-section>

      <!-- Rendered as plain markup rather than an accordion so the answers are
           present in the server-rendered HTML for crawlers and AI answer
           engines, and match the FAQPage JSON-LD exactly. -->
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

const steps = [
  { icon: 'i-lucide-layout-grid', key: 'step1' },
  { icon: 'i-lucide-list-checks', key: 'step2' },
  { icon: 'i-lucide-arrow-up-from-line', key: 'step3' },
  { icon: 'i-simple-icons-discord', key: 'step4' },
  { icon: 'i-lucide-dice-6', key: 'step5' },
].map(({ icon, key }) => ({
  icon,
  title: t(`landing.snakes.${key}_title`),
  description: t(`landing.snakes.${key}_desc`),
}));

const sizes = ['5', '7', '9'].map(n => ({
  icon: 'i-lucide-grid-3x3',
  title: t(`landing.snakes.size_${n}_title`),
  description: t(`landing.snakes.size_${n}_desc`),
}));

const faqs = Array.from({ length: 7 }, (_, i) => ({
  question: t(`landing.snakes.faq_q${i + 1}`),
  answer: t(`landing.snakes.faq_a${i + 1}`),
}));

// Board creation lives behind a modal on /boards, so send unauthenticated
// visitors through Discord login rather than to a page they cannot act on.
function startBoard() {
  if (authStore.isAuthenticated) {
    navigateTo('/boards');
    return;
  }
  authStore.loginWithDiscord();
}

useSeo({
  title: t('landing.snakes.meta_title'),
  description: t('landing.snakes.meta_desc'),
  jsonLd: [
    {
      '@type': 'FAQPage',
      mainEntity: faqs.map(faq => ({
        '@type': 'Question',
        name: faq.question,
        acceptedAnswer: { '@type': 'Answer', text: faq.answer },
      })),
    },
    {
      '@type': 'HowTo',
      name: t('landing.snakes.how_title'),
      description: t('landing.snakes.how_subtitle'),
      step: steps.map((step, i) => ({
        '@type': 'HowToStep',
        position: i + 1,
        name: step.title,
        text: step.description,
      })),
    },
  ],
});
</script>
