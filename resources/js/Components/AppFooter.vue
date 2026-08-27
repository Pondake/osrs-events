<template>
    <u-footer>
        <template #left>
            <div class="flex flex-col gap-1">
                <p class="text-sm text-muted">OSRS Events &bull; &copy; {{ currentYear }}</p>
                <p class="text-xs text-muted italic">{{ $t('common.not_affiliated') }}</p>
            </div>
        </template>

        <template #right>
            <nav class="flex flex-wrap items-center gap-x-4 gap-y-1 justify-center lg:justify-end">
                <a
                    v-for="link in footerLinks"
                    :key="link.to"
                    :href="link.to"
                    :target="link.external ? '_blank' : undefined"
                    :rel="link.external ? 'noopener noreferrer' : undefined"
                    class="text-xs text-muted hover:text-primary transition-colors"
                >
                    {{ link.label }}
                </a>
            </nav>
        </template>
    </u-footer>
</template>

<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { GUIDE_LINKS } from '@/Support/guides';

// Shared globally (HandleInertiaRequests) rather than passed as a prop —
// the footer renders on every page, including ones with no controller of
// their own.
const inertiaPage = usePage();
const kofiUrl = computed(() => inertiaPage.props?.site?.kofiUrl ?? 'https://ko-fi.com');

// Read in UTC, not local time — the server renders in UTC while the browser
// renders in the visitor's zone, so around New Year a local-time read would
// disagree across the two and mismatch on hydration. Same UTC-day
// convention as boardEventStatus() in Support/board.js.
const currentYear = new Date().getUTCFullYear();

// Guide pages first — search engines still reach them fastest from a
// site-wide footer link, even though the header's Guides menu also carries
// them now. Ko-fi is linked straight from here rather than through a
// /donate page of our own. A middle page had nothing to add that Ko-fi's
// own doesn't say, and it put a click between the button and the thing it's
// for.
const footerLinks = computed(() => [
    ...GUIDE_LINKS.map((link) => ({ to: link.to, label: trans(link.labelKey) })),
    { to: '/about', label: trans('nav.about') },
    { to: kofiUrl.value, label: trans('nav.support'), external: true },
    { to: '/privacy', label: trans('nav.privacy') },
    { to: '/terms', label: trans('nav.terms') },
]);
</script>
