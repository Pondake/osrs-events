<template>
    <u-main>
        <u-page>
            <u-container class="max-w-4xl py-12">
                <h1 class="text-3xl font-bold text-highlighted mb-8">{{ $t('settings.title') }}</h1>

                <div class="flex flex-col md:flex-row gap-8 items-start">
                    <!-- Plain <a> links, not u-navigation-menu: this nav lives inside
                         an Inertia page (unlike AppHeader's), so it has no SSR
                         ordering problem to work around — see AppHeader.vue's own
                         comment for why that one needs ClientOnly and this doesn't. -->
                    <nav class="w-full md:w-48 shrink-0 flex md:flex-col gap-1">
                        <a
                            v-for="item in items"
                            :key="item.to"
                            :href="item.to"
                            class="flex items-center gap-2 px-3 py-2 rounded-md text-sm transition-colors"
                            :class="current === item.key ? 'bg-elevated text-highlighted font-medium' : 'text-muted hover:bg-elevated/50'"
                        >
                            <u-icon :name="item.icon" class="size-4 shrink-0" />
                            {{ item.label }}
                        </a>
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

const items = computed(() => [
    { key: 'profile', to: '/settings/profile', icon: 'i-lucide-user-circle', label: trans('settings.nav_profile') },
    { key: 'account', to: '/settings/account', icon: 'i-lucide-shield', label: trans('settings.nav_account') },
]);
</script>
