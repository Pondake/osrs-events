<template>
  <nuxt-layout :title="$t('donate.title')" :description="$t('donate.subtitle')">
    <u-page-body>
      <u-container class="max-w-3xl py-8">
        <p class="text-muted leading-relaxed mb-10">{{ $t('donate.intro') }}</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-12">
          <!-- Ko-fi card -->
          <u-card class="osrs-border flex flex-col">
            <div class="flex items-center gap-3 mb-4">
              <div
                class="w-12 h-12 rounded-xl bg-amber-500/10 flex items-center justify-center shrink-0"
              >
                <u-icon name="i-lucide-coffee" class="text-amber-500 text-2xl" />
              </div>

              <div>
                <h3 class="font-bold osrs-font text-lg">Ko-fi</h3>

                <p class="text-xs text-muted">{{ $t('donate.kofi_tag') }}</p>
              </div>
            </div>

            <p class="text-muted text-sm leading-relaxed flex-1 mb-6">
              {{ $t('donate.kofi_desc') }}
            </p>

            <u-button
              to="https://ko-fi.com/pondake"
              target="_blank"
              color="warning"
              variant="solid"
              icon="i-lucide-coffee"
              block
              :label="$t('donate.kofi_button')"
            />
          </u-card>

          <!-- OSRS GP card -->
          <u-card class="osrs-border flex flex-col">
            <div class="flex items-center gap-3 mb-4">
              <div
                class="w-12 h-12 rounded-xl bg-yellow-500/10 flex items-center justify-center shrink-0 text-2xl"
              >
                🪙
              </div>

              <div>
                <h3 class="font-bold osrs-font text-lg">{{ $t('donate.gp_title') }}</h3>

                <p class="text-xs text-muted">{{ $t('donate.gp_tag') }}</p>
              </div>
            </div>

            <p class="text-muted text-sm leading-relaxed flex-1 mb-4">{{ $t('donate.gp_desc') }}</p>

            <u-alert
              color="warning"
              variant="subtle"
              icon="i-lucide-alert-triangle"
              :description="$t('donate.gp_warning')"
              class="mb-4 text-xs"
            />

            <!-- How to donate GP: email to get RSN (kept private against spam) -->
            <div class="rounded-lg border border-dashed border-default p-3 text-sm">
              <p class="font-semibold text-sm mb-1 flex items-center gap-1.5">
                <u-icon name="i-lucide-mail" class="text-primary shrink-0" />
                {{ $t('donate.gp_how') }}
              </p>

              <p class="text-muted text-xs leading-relaxed mb-2">{{ $t('donate.gp_how_desc') }}</p>

              <u-button
                to="mailto:dev@absolit.nl?subject=GP Donation"
                variant="outline"
                color="primary"
                size="xs"
                icon="i-lucide-mail"
                block
                :label="$t('donate.gp_how_cta')"
              />
            </div>
          </u-card>
        </div>

        <u-separator class="my-8" />

        <!-- Thank you note -->
        <div class="text-center">
          <p class="text-5xl mb-4">🙏</p>

          <h2 class="text-2xl font-bold osrs-font mb-3">{{ $t('donate.thanks_title') }}</h2>

          <p class="text-muted leading-relaxed max-w-xl mx-auto">{{ $t('donate.thanks_body') }}</p>
        </div>
      </u-container>
    </u-page-body>
  </nuxt-layout>
</template>

<script setup lang="ts">
const { t } = useI18n();

useHead({ title: t('donate.title') });
</script>
