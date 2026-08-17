<template>
  <u-main>
    <u-page>
      <u-page-hero
        :title="$t('landing.event_ideas.title')"
        :description="$t('landing.event_ideas.lead')"
      />

      <u-page-section>
        <u-container class="max-w-3xl">
          <p class="text-lg text-muted leading-relaxed">{{ $t('landing.event_ideas.intro') }}</p>
        </u-container>
      </u-page-section>

      <u-page-section>
        <u-container class="max-w-3xl">
          <article
            v-for="idea in ideas"
            :key="idea.title"
            class="py-8 border-t border-default first:border-t-0 first:pt-0"
          >
            <h2 class="text-2xl font-bold osrs-font">{{ idea.title }}</h2>

            <p class="mt-2 text-sm text-muted">
              <span class="font-medium text-default"
                >{{ $t('landing.event_ideas.meta_label') }}:</span
              >
              {{ idea.meta }}
            </p>

            <p class="mt-4 text-muted leading-relaxed">{{ idea.description }}</p>

            <u-button
              v-if="idea.to"
              :to="idea.to"
              class="mt-4"
              size="sm"
              color="primary"
              variant="outline"
              trailing-icon="i-lucide-arrow-right"
              :label="$t('landing.event_ideas.supported_cta')"
            />
          </article>
        </u-container>
      </u-page-section>

      <u-page-section :title="$t('landing.event_ideas.pick_title')">
        <u-container class="max-w-3xl">
          <p class="text-lg text-muted leading-relaxed">
            {{ $t('landing.event_ideas.pick_body') }}
          </p>
        </u-container>
      </u-page-section>

      <u-page-section :title="$t('landing.event_ideas.supported_title')">
        <u-container class="max-w-3xl">
          <p class="text-lg text-muted leading-relaxed">
            {{ $t('landing.event_ideas.supported_body') }}
          </p>

          <div class="flex flex-wrap gap-3 mt-6">
            <u-button
              to="/osrs-snakes-and-ladders"
              color="primary"
              trailing-icon="i-lucide-arrow-right"
              :label="$t('landing.event_ideas.supported_cta')"
            />

            <u-button
              to="/boards"
              color="neutral"
              variant="outline"
              :label="$t('landing.cta_browse')"
            />
          </div>
        </u-container>
      </u-page-section>
    </u-page>
  </u-main>
</template>

<script setup lang="ts">
const { t } = useI18n();

// Only the first entry links onward — it is the format the platform actually
// runs today. The rest are described honestly without implying support.
const ideas = Array.from({ length: 8 }, (_, i) => ({
  title: t(`landing.event_ideas.idea${i + 1}_title`),
  meta: t(`landing.event_ideas.idea${i + 1}_meta`),
  description: t(`landing.event_ideas.idea${i + 1}_desc`),
  to: i === 0 ? '/osrs-snakes-and-ladders' : undefined,
}));

useSeo({
  title: t('landing.event_ideas.meta_title'),
  description: t('landing.event_ideas.meta_desc'),
  ogType: 'article',
  jsonLd: {
    '@type': 'ItemList',
    name: t('landing.event_ideas.title'),
    description: t('landing.event_ideas.lead'),
    itemListElement: ideas.map((idea, i) => ({
      '@type': 'ListItem',
      position: i + 1,
      name: idea.title,
      description: idea.description,
    })),
  },
});
</script>
