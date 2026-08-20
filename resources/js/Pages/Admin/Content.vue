<template>
    <Head :title="$t('settings.nav_admin_content')" />

    <admin-layout current="content" :title="$t('settings.nav_admin_content')" :description="$t('admin.content_subtitle')">
        <div>
            <h3 class="font-semibold mb-3">{{ $t('cms.editable_pages') }}</h3>
            <div class="divide-y divide-default rounded-lg ring ring-default bg-default">
                <a
                    v-for="page in pages"
                    :key="page.id"
                    :href="`/admin/content/${page.slug}`"
                    class="flex items-center justify-between gap-4 px-4 py-3 hover:bg-elevated/50 transition-colors"
                >
                    <div class="min-w-0">
                        <div class="font-medium truncate">{{ page.title }}</div>
                        <div class="text-xs text-muted truncate">/{{ page.slug }}</div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="text-xs text-muted">{{ $t('cms.block_count', { count: page.blockCount }) }}</span>
                        <u-badge
                            :label="page.isPublished ? $t('cms.published') : $t('cms.draft')"
                            :color="page.isPublished ? 'success' : 'neutral'"
                            variant="subtle"
                            size="sm"
                        />
                        <u-icon name="i-lucide-chevron-right" class="size-4 text-dimmed" />
                    </div>
                </a>
            </div>
        </div>

        <!-- Listed rather than hidden: the inventory should say what the CMS
             does NOT cover yet, or it implies every public page is editable. -->
        <div>
            <h3 class="font-semibold mb-1">{{ $t('cms.static_pages') }}</h3>
            <p class="text-sm text-muted mb-3">{{ $t('cms.static_pages_desc') }}</p>
            <div class="divide-y divide-default rounded-lg ring ring-default bg-default">
                <div v-for="page in staticPages" :key="page.path" class="flex items-center justify-between gap-4 px-4 py-3">
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
    </admin-layout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Components/AdminLayout.vue';

defineProps({
    pages: { type: Array, required: true },
    staticPages: { type: Array, required: true },
});
</script>
