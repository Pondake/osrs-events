<template>
    <Head :title="$t('settings.nav_admin_content')" />

    <settings-layout current="admin-content">
        <div>
            <h2 class="text-xl font-semibold text-highlighted">{{ $t('settings.nav_admin_content') }}</h2>
            <p class="text-sm text-muted mt-0.5">{{ $t('admin.content_subtitle') }}</p>
        </div>

        <u-alert
            color="info"
            variant="subtle"
            icon="i-lucide-hammer"
            :title="$t('admin.content_wip_title')"
            :description="$t('admin.content_wip_desc')"
        />

        <div>
            <h3 class="font-semibold mb-3">{{ $t('admin.content_pages_title') }}</h3>
            <div class="divide-y divide-default rounded-lg ring ring-default bg-default">
                <div v-for="page in pages" :key="page.path" class="flex items-center justify-between gap-4 px-4 py-3">
                    <div class="min-w-0">
                        <div class="font-medium truncate">{{ page.label }}</div>
                        <div class="text-xs text-muted truncate">{{ page.path }}</div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <u-badge :label="$t('admin.content_source_static')" color="neutral" variant="subtle" size="sm" />
                        <u-button :href="page.path" target="_blank" icon="i-lucide-external-link" color="neutral" variant="ghost" size="xs" :aria-label="$t('admin.content_view')" />
                    </div>
                </div>
            </div>
        </div>
    </settings-layout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import SettingsLayout from '@/Components/SettingsLayout.vue';

defineProps({
    pages: { type: Array, required: true },
});
</script>
