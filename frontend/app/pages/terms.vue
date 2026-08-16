<template>
  <nuxt-layout :title="$t('terms.title')" :description="$t('terms.subtitle')">
    <u-page-body>
      <u-container class="max-w-3xl py-8">
        <p class="text-sm text-muted mb-8">
          {{ $t('terms.last_updated', { date: 'March 2026' }) }}
        </p>

        <!-- Acceptance -->
        <section class="mb-8">
          <h2 class="text-xl font-bold osrs-font mb-3">{{ $t('terms.acceptance_title') }}</h2>

          <p class="text-muted leading-relaxed">{{ $t('terms.acceptance_body') }}</p>
        </section>

        <u-separator class="my-6" />

        <!-- What the service is -->
        <section class="mb-8">
          <h2 class="text-xl font-bold osrs-font mb-3">{{ $t('terms.service_title') }}</h2>

          <p class="text-muted leading-relaxed">{{ $t('terms.service_body') }}</p>
        </section>

        <u-separator class="my-6" />

        <!-- User accounts -->
        <section class="mb-8">
          <h2 class="text-xl font-bold osrs-font mb-3">{{ $t('terms.accounts_title') }}</h2>

          <p class="text-muted leading-relaxed mb-4">{{ $t('terms.accounts_body') }}</p>

          <div class="space-y-2">
            <div
              v-for="rule in accountRules"
              :key="rule"
              class="flex items-start gap-2 text-muted text-sm"
            >
              <u-icon name="i-lucide-check-circle" class="text-success shrink-0 mt-0.5" />
              {{ rule }}
            </div>
          </div>
        </section>

        <u-separator class="my-6" />

        <!-- Acceptable use -->
        <section class="mb-8">
          <h2 class="text-xl font-bold osrs-font mb-3">{{ $t('terms.conduct_title') }}</h2>

          <p class="text-muted leading-relaxed mb-4">{{ $t('terms.conduct_body') }}</p>

          <div class="space-y-2">
            <div
              v-for="rule in conductRules"
              :key="rule"
              class="flex items-start gap-2 text-muted text-sm"
            >
              <u-icon name="i-lucide-x-circle" class="text-error shrink-0 mt-0.5" />
              {{ rule }}
            </div>
          </div>
        </section>

        <u-separator class="my-6" />

        <!-- Intellectual property -->
        <section class="mb-8">
          <h2 class="text-xl font-bold osrs-font mb-3">{{ $t('terms.ip_title') }}</h2>

          <p class="text-muted leading-relaxed">{{ $t('terms.ip_body') }}</p>

          <u-alert
            class="mt-4"
            color="warning"
            variant="subtle"
            icon="i-lucide-alert-triangle"
            :title="$t('terms.jagex_title')"
            :description="$t('terms.jagex_body')"
          />
        </section>

        <u-separator class="my-6" />

        <!-- Disclaimers -->
        <section class="mb-8">
          <h2 class="text-xl font-bold osrs-font mb-3">{{ $t('terms.disclaimer_title') }}</h2>

          <p class="text-muted leading-relaxed">{{ $t('terms.disclaimer_body') }}</p>
        </section>

        <u-separator class="my-6" />

        <!-- Termination -->
        <section class="mb-8">
          <h2 class="text-xl font-bold osrs-font mb-3">{{ $t('terms.termination_title') }}</h2>

          <p class="text-muted leading-relaxed">{{ $t('terms.termination_body') }}</p>
        </section>

        <u-separator class="my-6" />

        <!-- Changes to terms -->
        <section class="mb-8">
          <h2 class="text-xl font-bold osrs-font mb-3">{{ $t('terms.changes_title') }}</h2>

          <p class="text-muted leading-relaxed">{{ $t('terms.changes_body') }}</p>
        </section>

        <!-- Footer links -->
        <div class="flex gap-4 flex-wrap mt-8 pt-6 border-t border-default">
          <u-button
            to="/privacy"
            variant="link"
            color="neutral"
            size="sm"
            class="px-0"
            :label="$t('nav.privacy')"
          />

          <u-button
            to="/about"
            variant="link"
            color="neutral"
            size="sm"
            class="px-0"
            :label="$t('nav.about')"
          />
        </div>
      </u-container>
    </u-page-body>
  </nuxt-layout>
</template>

<script setup lang="ts">
const { t } = useI18n();

useHead({ title: t('terms.title') });

const accountRules = [
  t('terms.account_rule_discord'),
  t('terms.account_rule_one'),
  t('terms.account_rule_accurate'),
];

const conductRules = [
  t('terms.conduct_rule_abuse'),
  t('terms.conduct_rule_scrape'),
  t('terms.conduct_rule_exploit'),
  t('terms.conduct_rule_impersonate'),
];
</script>
