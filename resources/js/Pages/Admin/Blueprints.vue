<template>
    <Head :title="$t('admin.blueprints_title')" />

    <admin-layout current="blueprints" :title="$t('settings.nav_admin_blueprints')" :description="$t('admin.blueprints_subtitle')">
        <template #actions>
            <u-button color="primary" icon="i-lucide-plus" size="sm" :label="$t('admin.create_blueprint')" @click="openCreate" />
        </template>

        <u-input
            v-model="search"
            :placeholder="$t('admin.search_blueprints_placeholder')"
            icon="i-lucide-search"
            class="w-full sm:max-w-sm"
            @update:model-value="doSearch"
        />

        <div class="divide-y divide-default rounded-lg ring ring-default bg-default">
            <div v-for="blueprint in blueprints" :key="blueprint.id" class="flex items-center justify-between gap-4 px-4 py-3">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-medium truncate" :class="{ 'text-muted': !blueprint.is_active }">{{ blueprint.title }}</span>
                        <u-badge v-if="blueprint.type" color="primary" variant="subtle" size="sm" :label="$t(`events.type_${blueprint.type.toLowerCase()}`)" />
                        <u-badge v-if="blueprint.metric" color="neutral" variant="subtle" size="sm" :label="blueprintMetricLabel(blueprint)" />
                        <!-- Hidden, not deleted: the reason a retired format
                             still shows here at all. -->
                        <u-badge v-if="!blueprint.is_active" color="warning" variant="subtle" size="sm" :label="$t('admin.blueprint_hidden')" />
                    </div>
                    <p v-if="blueprint.description" class="text-xs text-muted truncate mt-0.5">{{ blueprint.description }}</p>
                </div>

                <div class="flex items-center gap-1 shrink-0">
                    <u-button icon="i-lucide-pencil" size="xs" color="neutral" variant="ghost" :aria-label="$t('common.edit')" @click="openEdit(blueprint)" />
                    <u-button icon="i-lucide-trash-2" size="xs" color="error" variant="ghost" :aria-label="$t('common.delete')" @click="destroyBlueprint(blueprint)" />
                </div>
            </div>

            <p v-if="!blueprints.length" class="px-4 py-8 text-center text-muted text-sm">{{ $t('admin.no_blueprints') }}</p>
        </div>

        <client-only>
            <event-blueprint-settings-modal v-model:open="showModal" :blueprint="editingBlueprint" />
        </client-only>
    </admin-layout>
</template>

<script setup>
import { defineAsyncComponent, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import ClientOnly from '@/Components/ClientOnly.vue';
import AdminLayout from '@/Components/AdminLayout.vue';
import { metricKindFor, metricLabel } from '@/Support/metrics';

const EventBlueprintSettingsModal = defineAsyncComponent(() => import('@/Components/EventBlueprintSettingsModal.vue'));

const props = defineProps({
    blueprints: { type: Array, required: true },
    search: { type: String, default: '' },
});

const search = ref(props.search);
const showModal = ref(false);
const editingBlueprint = ref(null);

// Via the shared helper so the namespace matches the kind — a boss slug
// looked up under `skills.` renders as the raw key.
const blueprintMetricLabel = (blueprint) => metricLabel(blueprint.metric, metricKindFor(blueprint.type));

function doSearch(value) {
    router.get('/admin/blueprints', { search: value }, { preserveState: true, replace: true });
}

function openCreate() {
    editingBlueprint.value = null;
    showModal.value = true;
}

function openEdit(blueprint) {
    editingBlueprint.value = blueprint;
    showModal.value = true;
}

function destroyBlueprint(blueprint) {
    if (!window.confirm(trans('admin.blueprint_delete_confirm', { title: blueprint.title }))) return;

    router.delete(`/admin/blueprints/${blueprint.id}`, { preserveScroll: true });
}
</script>
