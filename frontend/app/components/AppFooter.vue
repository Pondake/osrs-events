<template>
  <u-footer ref="footerRef">
    <template #left>
      <div class="flex flex-col gap-1">
        <p class="text-sm text-muted">OSRS Snakes &amp; Ladders &bull; &copy; {{ currentYear }}</p>

        <p class="text-xs text-muted italic">
          {{ $t('common.not_affiliated') }}
        </p>
      </div>
    </template>

    <template #right>
      <nav class="flex flex-wrap items-center gap-x-4 gap-y-1 justify-end">
        <nuxt-link
          v-for="link in footerLinks"
          :key="link.to"
          :to="link.to"
          class="text-xs text-muted hover:text-primary transition-colors"
        >
          {{ link.label }}
        </nuxt-link>
      </nav>
    </template>
  </u-footer>
</template>

<script setup lang="ts">
const footerRef = ref<{ $el: HTMLElement } | null>(null);
const { t } = useI18n();

// Read in UTC, not local time: the server renders in UTC while the browser
// renders in the visitor's zone, so around New Year a local-time read would
// disagree across the two and mismatch on hydration. Same UTC-day convention
// as boardEventStatus in utils/board.ts.
const currentYear = new Date().getUTCFullYear();

const footerLinks = [
  { to: '/about', label: t('nav.about') },
  { to: '/donate', label: t('nav.donate') },
  { to: '/privacy', label: t('nav.privacy') },
  { to: '/terms', label: t('nav.terms') },
];

let observer: ResizeObserver | null = null;

// Set --ui-footer-height CSS variable based on actual rendered footer height
onMounted(() => {
  nextTick(() => {
    const el = footerRef.value?.$el;
    if (!el) return;

    const updateHeight = () => {
      document.documentElement.style.setProperty('--ui-footer-height', `${el.offsetHeight}px`);
    };

    updateHeight();

    // Keep it updated on resize (e.g. font scaling, mobile orientation)
    observer = new ResizeObserver(updateHeight);
    observer.observe(el);
  });
});

onUnmounted(() => {
  observer?.disconnect();
});
</script>
