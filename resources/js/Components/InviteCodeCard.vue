<template>
    <!-- The way in, on the page itself.

         A listed invite-only event opens for anyone since 2026-08-31, so it
         no longer renders Boards/AccessGate — and the code field lived there.
         Without this, the only route into such an event was an invite LINK,
         and anybody holding a bare code had nowhere to type it. -->
    <u-card :ui="{ root: 'ring-primary/30' }">
        <div class="flex items-start gap-3">
            <u-icon name="i-lucide-key-round" class="size-5 shrink-0 mt-0.5 text-primary" />

            <div class="min-w-0 flex-1">
                <p class="font-semibold text-highlighted">{{ $t('board.invite_only_title') }}</p>
                <p class="text-sm text-muted mt-1">{{ $t('board.invite_only_desc') }}</p>

                <!-- Signed out: a code is no use without an account to attach
                     it to, and the join endpoint is behind auth. Said plainly
                     rather than letting somebody type a code into a form that
                     would bounce them to a login and lose it. -->
                <u-button
                    v-if="!user"
                    href="/login"
                    color="primary"
                    variant="outline"
                    size="sm"
                    class="mt-3"
                    :label="$t('board.invite_only_sign_in')"
                />

                <form v-else class="flex gap-2 mt-3 max-w-sm" @submit.prevent="submit">
                    <u-input
                        v-model="form.token_or_code"
                        :placeholder="$t('board.enter_code')"
                        class="flex-1"
                        :disabled="form.processing"
                    />
                    <u-button
                        type="submit"
                        color="primary"
                        :label="$t('board.join_with_code')"
                        :loading="form.processing"
                        :disabled="!form.token_or_code"
                    />
                </form>

                <p v-if="form.errors.access" class="text-error text-sm mt-2">{{ form.errors.access }}</p>
            </div>
        </div>
    </u-card>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { useAuth } from '@/Composables/useAuth';

const props = defineProps({
    eventId: { type: String, required: true },
});

const { user } = useAuth();

// Same endpoint the gate posted to — one join path, not two.
const form = useForm({ token_or_code: '' });

function submit() {
    form.post(`/events/${props.eventId}/join`, {
        preserveScroll: true,
        // Cleared on success only: a wrong code should stay in the box to be
        // corrected, and the error above says what happened.
        onSuccess: () => form.reset(),
    });
}
</script>
