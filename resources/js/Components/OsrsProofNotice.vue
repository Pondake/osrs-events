<template>
    <u-alert
        v-if="needsProof"
        color="warning"
        variant="subtle"
        icon="i-lucide-shield-alert"
        :title="pending ? undefined : $t('board.proof_needed_title')"
        :description="pending
            ? $t('board.proof_needed_pending', { name: osrsName })
            : $t('board.proof_needed_desc', { name: osrsName })"
    >
        <template v-if="! pending" #actions>
            <u-button
                :href="route('settings.runelite')"
                color="warning"
                variant="outline"
                size="sm"
                icon="i-lucide-plug"
                :label="$t('board.proof_needed_cta')"
            />
        </template>
    </u-alert>
</template>

<script setup>
import { computed } from 'vue';
import { useAuth } from '@/Composables/useAuth';

/**
 * Why this claim is going to a host, said where the claim is made.
 *
 * The state itself lives on two settings pages, which is where somebody goes
 * to fix it — and exactly where somebody who has never been told there is
 * anything to fix will never look. A claim that quietly lands PENDING on a
 * board whose host switched review off reads as a bug, so the dialog says it
 * before the button is pressed (`pending` false) and again on the claim that
 * is already waiting (`pending` true).
 *
 * `needsOsrsProof` is the server's own rule, collapsed to one boolean by
 * HandleInertiaRequests. Recomputing it here from the plugin mode would let
 * the notice drift away from the status ReviewsClaims actually stamps.
 */
defineProps({
    // The claim already exists and is waiting: explain, do not instruct.
    pending: { type: Boolean, default: false },
});

const { user } = useAuth();

const needsProof = computed(() => user.value?.needsOsrsProof ?? false);
const osrsName = computed(() => user.value?.osrsUsername ?? '');
</script>
