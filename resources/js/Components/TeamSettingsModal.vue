<template>
    <u-modal v-model:open="isOpen" :title="$t('teams.create_team')">
        <template #body>
            <div class="space-y-4 py-2">
                <u-form-field :label="$t('teams.team_name')" required>
                    <u-input v-model="form.name" class="w-full" :placeholder="$t('teams.team_name_placeholder')" />
                </u-form-field>

                <u-form-field :label="$t('teams.team_icon')">
                    <u-input v-model="form.icon_url" class="w-full" placeholder="https://..." />
                </u-form-field>

                <u-form-field :label="$t('teams.discord_server_id')" :description="$t('teams.discord_server_id_desc')">
                    <u-input v-model="form.guild_id" class="w-full" />
                </u-form-field>

                <u-form-field :label="$t('teams.discord_server_name')" :description="$t('teams.discord_server_name_desc')">
                    <u-input v-model="form.guild_name" class="w-full" />
                </u-form-field>
            </div>
        </template>

        <template #footer>
            <div class="flex justify-end gap-2 w-full">
                <u-button color="neutral" variant="outline" :label="$t('common.cancel')" @click="isOpen = false" />
                <u-button color="primary" :label="$t('common.create')" :loading="form.processing" @click="submit" />
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
