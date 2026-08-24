<template>
    <u-main>
        <u-page>
            <u-container class="max-w-5xl py-12">
                <h1 class="text-3xl font-bold text-highlighted mb-8">{{ $t('settings.title') }}</h1>

                <div class="flex flex-col md:flex-row gap-8 items-start">
                    <!-- Plain <a> links, not u-navigation-menu: this nav lives inside
                         an Inertia page (unlike AppHeader's), so it has no SSR
                         ordering problem to work around — see AppHeader.vue's own
                         comment for why that one needs ClientOnly and this doesn't. -->
                    <!-- Vertical rail on desktop, horizontally scrollable
                         rows on mobile. The row needs overflow-x-auto with
                         shrink-0 + whitespace-nowrap children: without them
                         the items squeeze to fit, wrapping "Users & roles"
                         onto three lines and pushing the last entry off
                         screen entirely with no way to reach it.
                         -mx-4 px-4 lets the scrolled row bleed to the screen
                         edges so it reads as scrollable instead of clipped. -->
                    <nav class="w-full md:w-52 shrink-0 space-y-4 md:space-y-6">
                        <div v-for="group in groups" :key="group.key">
                            <p class="px-3 mb-1 text-xs font-semibold uppercase tracking-wide text-muted">{{ group.label }}</p>
                            <div class="flex md:flex-col gap-1 overflow-x-auto md:overflow-x-visible -mx-4 px-4 md:mx-0 md:px-0 pb-1 md:pb-0">
                                <a
                                    v-for="item in group.items"
                                    :key="item.to"
                                    :href="item.to"
                                    class="flex items-center gap-2 px-3 py-2 rounded-md text-sm transition-colors shrink-0 whitespace-nowrap"
                                    :class="current === item.key ? 'bg-elevated text-highlighted font-medium' : 'text-muted hover:bg-elevated/50'"
                                >
                                    <u-icon :name="item.icon" class="size-4 shrink-0" />
                                    {{ item.label }}
                                </a>
                            </div>
                        </div>
                    </nav>

                    <div class="flex-1 min-w-0 w-full space-y-8">
                        <slot />
                    </div>
                </div>
            </u-container>
        </u-page>
    </u-main>
</template>

<script setup>
import { computed } from 'vue';
import { trans } from 'laravel-vue-i18n';

defineProps({
    current: { type: String, required: true },
});

/** Personal account settings only — everything admin lives at /admin. */
const groups = computed(() => {
    const result = [
        {
            key: 'account',
            label: trans('settings.group_account'),
            items: [
                { key: 'profile', to: '/settings/profile', icon: 'i-lucide-user-circle', label: trans('settings.nav_profile') },
                { key: 'account', to: '/settings/account', icon: 'i-lucide-shield', label: trans('settings.nav_account') },
                { key: 'notifications', to: '/settings/notifications', icon: 'i-lucide-bell', label: trans('settings.nav_notifications') },
            ],
        },
    ];

    return result;
});
</script>
