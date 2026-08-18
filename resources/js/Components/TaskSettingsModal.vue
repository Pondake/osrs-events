<template>
    <u-modal v-model:open="isOpen" :title="isEdit ? 'Edit task' : 'New task'">
        <template #body>
            <div class="space-y-4 py-2">
                <u-form-field label="Title" required>
                    <u-input v-model="form.title" class="w-full" placeholder="Kill Zulrah" />
                </u-form-field>
                <u-form-field label="Icon URL">
                    <u-input v-model="form.icon_url" class="w-full" placeholder="https://oldschool.runescape.wiki/..." />
                </u-form-field>
                <u-form-field label="Description">
                    <u-textarea v-model="form.description" class="w-full" :rows="3" />
                </u-form-field>
            </div>
        </template>

        <template #footer>
            <div class="flex justify-end gap-2 w-full">
                <u-button color="neutral" variant="outline" label="Cancel" @click="isOpen = false" />
                <u-button color="primary" label="Save" :loading="form.processing" @click="submit" />
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
        form.patch(`/admin/tasks/${props.task.id}`, { onSuccess: () => (isOpen.value = false) });
    } else {
        form.post('/admin/tasks', { onSuccess: () => (isOpen.value = false) });
    }
}
</script>
