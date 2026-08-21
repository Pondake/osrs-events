<template>
    <u-modal v-model:open="isOpen" :title="square?.label || $t('bingo.empty_square')" :dismissible="false">
        <template #body>
            <div class="space-y-4 py-2">
                <p class="text-sm text-muted">{{ $t('bingo.claim_intro') }}</p>

                <u-form-field :label="$t('bingo.proof_url')" :description="$t('bingo.proof_url_desc')" :error="form.errors.proof_url">
                    <u-input v-model="form.proof_url" class="w-full" placeholder="https://" />
                </u-form-field>

                <u-form-field :label="$t('bingo.claim_note')" :error="form.errors.note">
                    <u-input v-model="form.note" class="w-full" />
                </u-form-field>
            </div>
        </template>

        <template #footer>
            <div class="flex items-center justify-end gap-2 w-full">
                <u-button color="neutral" variant="ghost" :label="$t('common.cancel')" @click="isOpen = false" />
                <u-button color="primary" :loading="form.processing" :label="$t('bingo.submit_claim')" @click="submit" />
            </div>
        </template>
    </u-modal>
</template>

<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

/**
 * Claiming a square, with proof.
 *
 * A dialog rather than a bare click because a claim on a card that requires
 * approval is a submission to a queue, and the thing that makes it reviewable
 * is the screenshot. Asking for it at the moment of claiming is the only point
 * where the player still has it to hand.
 */
const props = defineProps({
    open: { type: Boolean, default: false },
    eventId: { type: String, required: true },
    square: { type: Object, default: null },
});

const emit = defineEmits(['update:open']);

const isOpen = computed({ get: () => props.open, set: (v) => emit('update:open', v) });

const form = useForm({ proof_url: '', note: '' });

watch(() => props.square, () => form.reset());

function submit() {
    form.post(`/events/${props.eventId}/bingo/squares/${props.square.id}/claim`, {
        preserveScroll: true,
        onSuccess: () => (isOpen.value = false),
    });
}
</script>
