<template>
    <u-footer>
        <template #left>
            <div class="flex flex-col gap-1">
                <p class="text-sm text-muted">OSRS Events &bull; &copy; {{ currentYear }}</p>
                <p class="text-xs text-muted italic">{{ $t('common.not_affiliated') }}</p>
            </div>
        </template>

        <template #right>
            <nav class="flex flex-wrap items-center gap-x-4 lg:gap-y-1 justify-center lg:justify-end">
                <a
                    v-for="link in footerLinks"
                    :key="link.to"
                    :href="link.to"
                    :target="link.external ? '_blank' : undefined"
                    :rel="link.external ? 'noopener noreferrer' : undefined"
                    class="inline-flex min-h-11 items-center text-xs text-muted hover:text-primary transition-colors lg:min-h-0"
                >
                    {{ link.label }}
                </a>
            </nav>
        </template>
    </u-footer>
</template>

<script setup>
import { computed } from 'vue';
import { trans } from 'laravel-vue-i18n';
import { GUIDE_LINKS } from '@/Support/guides';

// Read in UTC, not local time — the server renders in UTC while the browser
// renders in the visitor's zone, so around New Year a local-time read would
// disagree across the two and mismatch on hydration. Same UTC-day
// convention as boardEventStatus() in Support/board.js.
const currentYear = new Date().getUTCFullYear();

// Guide pages first — search engines still reach them fastest from a
// site-wide footer link, even though the header's Guides menu also carries
// them now. Ko-fi lives on /supporters and /about, not here.
const footerLinks = computed(() => [
    ...GUIDE_LINKS.map((link) => ({ to: link.to, label: trans(link.labelKey) })),
    { to: '/about', label: trans('nav.about') },
    { to: '/supporters', label: trans('nav.supporters') },
    { to: '/privacy', label: trans('nav.privacy') },
    { to: '/terms', label: trans('nav.terms') },
]);
</script>
