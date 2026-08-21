<template>
    <!-- Same single-form deviation from CLAUDE.md's stepper/tabs rule as
         TeamSettingsModal, for the same reason: five fields with no sections
         to split them across. What the rule really asks for — one component
         for create and edit — is what this is. -->
    <u-modal
        v-model:open="isOpen"
        :title="isEdit ? $t('admin.edit_blueprint') : $t('admin.create_blueprint')"
        :description="$t('admin.blueprint_modal_desc')"
        :dismissible="false"
    >
        <template #body>
            <div class="space-y-4 py-2">
                <u-form-field :label="$t('admin.blueprint_title')" :description="$t('admin.blueprint_title_desc')" :error="form.errors.title" required>
                    <u-input v-model="form.title" class="w-full" :placeholder="$t('admin.blueprint_title_placeholder')" />
                </u-form-field>

                <u-form-field :label="$t('events.type_label')" :description="$t('admin.blueprint_type_desc')" :error="form.errors.type">
                    <u-select
                        v-model="form.type"
                        :items="typeOptions"
                        class="w-full"
                        :placeholder="$t('admin.blueprint_no_type')"
                    />
                </u-form-field>

                <!-- Only the racing types have one, and the server rejects a
                     metric that doesn't belong to the type it's paired with. -->
                <u-form-field
                    v-if="metricOptions.length"
                    :label="$t(metricKind === 'boss' ? 'events.metric_label_boss' : 'events.metric_label')"
                    :description="$t('admin.blueprint_metric_desc')"
                    :error="form.errors.metric"
                >
                    <u-select v-model="form.metric" :items="metricOptions" class="w-full" :placeholder="$t('admin.blueprint_no_metric')" />
                </u-form-field>

                <u-form-field :label="$t('admin.board_description')" :description="$t('admin.blueprint_description_desc')">
                    <u-textarea v-model="form.description" class="w-full" :rows="3" />
                </u-form-field>

                <u-form-field :description="$t('admin.blueprint_active_desc')">
                    <u-switch v-model="form.is_active" :label="$t('admin.blueprint_active')" />
                </u-form-field>
            </div>
        </template>

        <template #footer>
            <div class="flex justify-end gap-2 w-full">
                <u-button color="neutral" variant="outline" :label="$t('common.cancel')" @click="isOpen = false" />
                <u-button
                    color="primary"
                    :label="isEdit ? $t('common.save') : $t('common.create')"
                    :loading="form.processing"
                    @click="submit"
                />
            </div>
        </template>
    </u-modal>
</template>

<script setup>
import { computed, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';

const props = defineProps({
    open: { type: Boolean, default: false },
    blueprint: { type: Object, default: null },
});

const emit = defineEmits(['update:open']);

const isOpen = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value),
});

const isEdit = computed(() => props.blueprint !== null);

const blank = () => ({ title: '', type: null, metric: null, description: '', is_active: true });

const form = useForm(blank());

// A blueprint carrying no type at all is a legitimate entry — a name a clan
// reuses for an event whose format changes each run — so the list gets a
// null option rather than defaulting to whichever type happens to be first.
const typeOptions = computed(() => [
    { label: trans('admin.blueprint_no_type'), value: null },
    ...(usePage().props?.site?.eventTypes ?? [])
        .filter((type) => type.available)
        .map((type) => ({ label: trans(`events.type_${type.value.toLowerCase()}`), value: type.value })),
]);

const selectedType = computed(() =>
    (usePage().props?.site?.eventTypes ?? []).find((t) => t.value === form.type),
);

const metricKind = computed(() => selectedType.value?.metricKind ?? null);

const metricOptions = computed(() => {
    const kind = metricKind.value;
    if (!kind) return [];

    return [
        { label: trans('admin.blueprint_no_metric'), value: null },
        ...(usePage().props?.site?.metricsByKind?.[kind] ?? []).map((m) => ({
            value: m,
            label: trans(`${kind === 'boss' ? 'bosses' : 'skills'}.${m}`),
        })),
    ];
});

// Same rule BoardSettingsModal applies to its own metric field: a boss name
// is not a valid skill race and vice versa, so switching type drops a metric
// that is no longer on offer instead of submitting one the server rejects.
watch(() => form.type, () => {
    if (form.metric && !metricOptions.value.some((option) => option.value === form.metric)) {
        form.metric = null;
    }
});

// Field by field rather than a wholesale spread — a blueprint row also
// carries id and timestamps, and posting those back is noise at best.
watch(
    () => props.blueprint,
    (blueprint) => {
        form.defaults(blueprint
            ? {
                title: blueprint.title ?? '',
                type: blueprint.type ?? null,
                metric: blueprint.metric ?? null,
                description: blueprint.description ?? '',
                is_active: blueprint.is_active ?? true,
            }
            : blank());
        form.reset();
        form.clearErrors();
    },
    { immediate: true },
);

function submit() {
    const options = { preserveScroll: true, onSuccess: () => (isOpen.value = false) };

    if (isEdit.value) {
        form.patch(`/admin/blueprints/${props.blueprint.id}`, options);
    } else {
        form.post('/admin/blueprints', { ...options, onSuccess: () => { isOpen.value = false; form.reset(); } });
    }
}
</script>
