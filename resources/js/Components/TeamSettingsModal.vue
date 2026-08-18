<template>
    <u-modal v-model:open="isOpen" title="Create team">
        <template #body>
            <div class="space-y-4 py-2">
                <u-form-field label="Name" required>
                    <u-input v-model="form.name" class="w-full" placeholder="Iron Titans" />
                </u-form-field>

                <u-form-field label="Icon URL">
                    <u-input v-model="form.icon_url" class="w-full" placeholder="https://..." />
                </u-form-field>

                <u-form-field label="Discord server ID" description="Restricts board visibility to members of this server.">
                    <u-input v-model="form.guild_id" class="w-full" />
                </u-form-field>

                <u-form-field label="Discord server name" description="Display-only label, cached for showing without a live Discord call.">
                    <u-input v-model="form.guild_name" class="w-full" />
                </u-form-field>
            </div>
        </template>

        <template #footer>
            <div class="flex justify-end gap-2 w-full">
                <u-button color="neutral" variant="outline" label="Cancel" @click="isOpen = false" />
                <u-button color="primary" label="Create" :loading="form.processing" @click="submit" />
            </div>
        </template>
    </u-modal>
</template>

<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    open: { type: Boolean, default: false },
});

const emit = defineEmits(['update:open']);

const isOpen = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value),
});

const form = useForm({ name: '', icon_url: '', guild_id: '', guild_name: '' });

function submit() {
    form.post('/teams', {
        onSuccess: () => {
            isOpen.value = false;
            form.reset();
        },
    });
}
</script>
