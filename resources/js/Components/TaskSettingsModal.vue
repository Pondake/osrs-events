<template>
    <u-modal v-model:open="isOpen" :title="isEdit ? $t('tile_editor.action_edit_task') : $t('tile_editor.action_create_task')">
        <template #body>
            <div class="space-y-4 py-2">
                <u-form-field :label="$t('admin.task_title_label')" required>
                    <u-input v-model="form.title" class="w-full" :placeholder="$t('admin.task_title_placeholder')" />
                </u-form-field>
                <u-form-field :label="$t('admin.task_icon_label')">
                    <u-input v-model="form.icon_url" class="w-full" :placeholder="$t('admin.icon_url_placeholder')" />
                </u-form-field>
                <u-form-field :label="$t('admin.task_desc_label')">
                    <u-textarea v-model="form.description" class="w-full" :rows="3" />
                </u-form-field>
            </div>
        </template>

        <template #footer>
            <div class="flex justify-end gap-2 w-full">
                <u-button color="neutral" variant="outline" :label="$t('common.cancel')" @click="isOpen = false" />
                <u-button color="primary" :label="$t('common.save')" :loading="form.processing" @click="submit" />
            </div>
        </template>
    </u-modal>
</template>

<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    open: { type: Boolean, default: false },
    task: { type: Object, default: null },
});

const emit = defineEmits(['update:open']);

const isOpen = computed({ get: () => props.open, set: (v) => emit('update:open', v) });
const isEdit = computed(() => props.task !== null);

const form = useForm({ title: '', icon_url: '', description: '' });

watch(
    () => props.task,
    (task) => form.defaults(task ? { title: task.title, icon_url: task.icon_url ?? '', description: task.description ?? '' } : { title: '', icon_url: '', description: '' }).reset(),
    { immediate: true },
);

function submit() {
    if (isEdit.value) {
        form.patch(`/settings/admin/tasks/${props.task.id}`, { onSuccess: () => (isOpen.value = false) });
    } else {
        form.post('/settings/admin/tasks', { onSuccess: () => (isOpen.value = false) });
    }
}
</script>
