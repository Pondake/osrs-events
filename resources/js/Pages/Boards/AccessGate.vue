<template>
    <Head :title="board.title" />

    <u-main>
        <u-page>
            <u-container class="max-w-lg py-20 text-center">
                <u-icon :name="icon" class="size-12 text-muted mx-auto mb-4" />
                <h1 class="text-2xl font-bold text-highlighted mb-2">{{ board.title }}</h1>
                <p class="text-muted mb-8">{{ reason ?? $t('errors.forbidden') }}</p>

                <form v-if="canRequestInvite" class="flex gap-2 max-w-xs mx-auto" @submit.prevent="submitInvite">
                    <u-input v-model="tokenOrCode" :placeholder="$t('board.enter_code')" class="flex-1" />
                    <u-button type="submit" color="primary" :label="$t('board.join_with_code')" :loading="form.processing" />
                </form>

                <u-button v-else-if="board.access_mode === 'OPEN'" href="/events" color="neutral" variant="outline" :label="$t('boards.back_to_boards')" class="mt-2" />

                <p v-if="form.errors.access" class="text-error text-sm mt-4">{{ form.errors.access }}</p>
            </u-container>
        </u-page>
    </u-main>
</template>

<script setup>
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    board: { type: Object, required: true },
    reason: { type: String, default: null },
    canRequestInvite: { type: Boolean, default: false },
});

const icon = computed(() => (props.board.access_mode === 'INVITE' ? 'i-lucide-key-round' : 'i-lucide-shield-off'));

const form = useForm({ token_or_code: '' });
const tokenOrCode = computed({
    get: () => form.token_or_code,
    set: (v) => (form.token_or_code = v),
});

function submitInvite() {
    form.post(`/events/${props.board.id}/join`);
}
</script>
