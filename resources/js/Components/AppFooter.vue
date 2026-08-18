<template>
    <u-footer>
        <template #left>
            <div class="flex flex-col gap-1">
                <p class="text-sm text-muted">OSRS Events &bull; &copy; {{ currentYear }}</p>
                <p class="text-xs text-muted italic">{{ $t('common.not_affiliated') }}</p>
            </div>
        </template>

        <template #right>
            <nav class="flex flex-wrap items-center gap-x-4 gap-y-1 justify-end">
                <a v-for="link in footerLinks" :key="link.to" :href="link.to" class="text-xs text-muted hover:text-primary transition-colors">
                    {{ link.label }}
                </a>
            </nav>
        </template>
    </u-footer>
</template>

<script setup>
import { trans } from 'laravel-vue-i18n';

// Read in UTC, not local time — the server renders in UTC while the browser
// renders in the visitor's zone, so around New Year a local-time read would
// disagree across the two and mismatch on hydration. Same UTC-day
// convention as boardEventStatus() in Support/board.js.
const currentYear = new Date().getUTCFullYear();

// Guide pages first — the footer is the only site-wide crawl path to them
// (no header nav entry, since they're marketing/SEO pages not app features).
const footerLinks = [
    { to: '/osrs-snakes-and-ladders', label: trans('nav.snakes') },
    { to: '/osrs-clan-events', label: trans('nav.clan_events') },
    { to: '/osrs-event-ideas', label: trans('nav.event_ideas') },
    { to: '/about', label: trans('nav.about') },
    { to: '/donate', label: trans('nav.donate') },
    { to: '/privacy', label: trans('nav.privacy') },
    { to: '/terms', label: trans('nav.terms') },
];
</script>
