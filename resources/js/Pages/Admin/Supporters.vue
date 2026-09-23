<template>
    <Head :title="$t('admin.supporters_title')" />

    <admin-layout current="supporters" :title="$t('settings.nav_admin_supporters')" :description="$t('admin.supporters_subtitle')">
        <template #actions>
            <u-button to="/supporters" size="sm" class="max-sm:hidden" color="neutral" variant="outline" icon="i-lucide-external-link" :label="$t('admin.supporters_view_page')" />
            <u-button color="primary" icon="i-lucide-plus" size="sm" :label="$t('admin.create_supporter')" @click="openCreate" />
        </template>

        <div class="divide-y divide-default rounded-lg ring ring-default bg-default">
            <div v-for="supporter in supporters" :key="supporter.id" class="flex items-center justify-between gap-4 px-4 py-3">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-xs text-muted tabular-nums">{{ supporter.sort_order }}</span>
                        <span class="font-medium truncate" :class="{ 'text-muted': !isPublic(supporter) }">{{ supporter.name }}</span>
                        <u-badge v-for="role in supporter.roles" :key="role" color="primary" variant="subtle" size="sm" :label="$t(`supporters.role_${role}`)" />
                        <u-badge v-if="!supporter.consented" color="warning" variant="subtle" size="sm" icon="i-lucide-shield-alert" :label="$t('admin.supporter_no_consent')" />
                        <u-badge v-if="!supporter.is_visible" color="neutral" variant="subtle" size="sm" icon="i-lucide-eye-off" :label="$t('admin.supporter_hidden')" />
                    </div>
                    <p v-if="supporter.link" class="text-xs text-muted truncate mt-0.5">{{ supporter.link }}</p>
                </div>

                <div class="flex items-center gap-1 shrink-0">
                    <u-button icon="i-lucide-pencil" size="xs" color="neutral" variant="ghost" class="max-sm:min-h-11 max-sm:min-w-11 justify-center" :aria-label="$t('common.edit')" @click="openEdit(supporter)" />
                    <u-button icon="i-lucide-trash-2" size="xs" color="error" variant="ghost" class="max-sm:min-h-11 max-sm:min-w-11 justify-center" :aria-label="$t('common.delete')" @click="destroySupporter(supporter)" />
                </div>
            </div>

            <p v-if="!supporters.length" class="px-4 py-8 text-center text-muted text-sm">{{ $t('admin.no_supporters') }}</p>
        </div>

        <client-only>
            <supporter-settings-modal v-model:open="showModal" :supporter="editingSupporter" :roles="roles" />
        </client-only>
    </admin-layout>
</template>

<script setup>
import { defineAsyncComponent, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import ClientOnly from '@/Components/ClientOnly.vue';
import AdminLayout from '@/Components/AdminLayout.vue';

const SupporterSettingsModal = defineAsyncComponent(() => import('@/Components/SupporterSettingsModal.vue'));

defineProps({
    supporters: { type: Array, required: true },
    roles: { type: Array, required: true },
});

const showModal = ref(false);
const editingSupporter = ref(null);

const isPublic = (supporter) => supporter.consented && supporter.is_visible;

function openCreate() {
    editingSupporter.value = null;
    showModal.value = true;
}

function openEdit(supporter) {
    editingSupporter.value = supporter;
    showModal.value = true;
}

function destroySupporter(supporter) {
    if (!window.confirm(trans('admin.supporter_delete_confirm', { name: supporter.name }))) return;

    router.delete(`/admin/supporters/${supporter.id}`, { preserveScroll: true });
}
</script>
