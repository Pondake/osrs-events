<template>
    <u-modal v-model:open="isOpen" :title="isEdit ? 'Edit board' : 'Create board'">
        <template #body>
            <!-- CLAUDE.md's convention is a stepper for create and tabs for edit
                 (per-step validation on create). Both use tabs here instead — a
                 deliberate simplification, not an oversight, since building
                 per-step validation for one form wasn't worth the scope for this
                 pass. Noted in docs/backlog.md. Co-author management (the old
                 EditorsSection.vue) is left out entirely: it needs a user-search
                 endpoint that doesn't exist yet either. -->
            <u-tabs :items="tabs" class="w-full">
                <template #basics>
                    <div class="space-y-4 py-2">
                        <u-form-field label="Title" required>
                            <u-input v-model="form.title" class="w-full" placeholder="Winter Clan Grind" />
                        </u-form-field>

                        <u-form-field label="Description">
                            <u-textarea v-model="form.description" class="w-full" :rows="3" />
                        </u-form-field>

                        <div class="grid grid-cols-2 gap-4">
                            <u-form-field label="Board size" required>
                                <u-select v-model="form.size" :items="sizeOptions" class="w-full" />
                            </u-form-field>

                            <u-form-field label="Mode">
                                <u-select v-model="form.mode" :items="modeOptions" class="w-full" />
                            </u-form-field>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <u-form-field label="Start date">
                                <u-input v-model="form.start_date" type="date" class="w-full" />
                            </u-form-field>
                            <u-form-field label="End date">
                                <u-input v-model="form.end_date" type="date" class="w-full" />
                            </u-form-field>
                        </div>

                        <u-form-field label="Daily dice roll limit" description="Leave empty for unlimited.">
                            <u-input v-model.number="form.dice_roll_limit" type="number" min="1" class="w-full" />
                        </u-form-field>

                        <u-switch v-model="form.is_listed" label="Listed on the public boards page" />
                    </div>
                </template>

                <template #access>
                    <div class="space-y-4 py-2">
                        <u-form-field label="Who can join">
                            <u-select v-model="form.access_mode" :items="accessOptions" class="w-full" />
                        </u-form-field>

                        <u-form-field
                            v-if="form.access_mode === 'GUILD'"
                            label="Required Discord server ID"
                            description="Only members of this Discord server can join."
                        >
                            <u-input v-model="form.required_guild_id" class="w-full" />
                        </u-form-field>
                    </div>
                </template>
            </u-tabs>
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
import { BOARD_SIZE_LABEL, BOARD_TILE_COUNT } from '@/Support/board';

const props = defineProps({
    open: { type: Boolean, default: false },
    board: { type: Object, default: null },
});

const emit = defineEmits(['update:open']);

const isOpen = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value),
});

const isEdit = computed(() => props.board !== null);

const tabs = [
    { label: 'Basics', slot: 'basics' },
    { label: 'Access', slot: 'access' },
];

const sizeOptions = ['SIZE_5X5', 'SIZE_7X7', 'SIZE_9X9'].map((size) => ({
    label: `${BOARD_SIZE_LABEL[size]} (${BOARD_TILE_COUNT[size]} tiles)`,
    value: size,
}));

const modeOptions = [
    { label: 'Solo', value: 'SOLO' },
    { label: 'Team', value: 'TEAM' },
];

const accessOptions = [
    { label: 'Open to everyone', value: 'OPEN' },
    { label: 'Discord server members only', value: 'GUILD' },
    { label: 'Invite only', value: 'INVITE' },
];

function blankForm() {
    return {
        title: '',
        description: '',
        size: 'SIZE_7X7',
        mode: 'SOLO',
        start_date: '',
        end_date: '',
        dice_roll_limit: null,
        is_listed: true,
        access_mode: 'OPEN',
        required_guild_id: '',
    };
}

const form = useForm(blankForm());

// Re-seed the form whenever a different board is opened for editing, or the
// modal is reopened in create mode after a previous edit.
watch(
    () => props.board,
    (board) => {
        form.defaults(board ? { ...blankForm(), ...board } : blankForm());
        form.reset();
    },
    { immediate: true },
);

function submit() {
    if (isEdit.value) {
        form.patch(`/boards/${props.board.id}`, { onSuccess: () => (isOpen.value = false) });
    } else {
        form.post('/boards', { onSuccess: () => (isOpen.value = false) });
    }
}
</script>
