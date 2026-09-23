<template>
    <!-- Only asked when there is a choice: one character, or an event that
         counts mains only, and the claim is simply the main's. -->
    <u-form-field v-if="options.length > 1" :label="$t('bingo.claim_character')" :description="$t('bingo.claim_character_desc')" :error="error">
        <u-select
            :model-value="modelValue || options[0].value"
            :items="options"
            class="w-full sm:max-w-xs"
            @update:model-value="(value) => emit('update:modelValue', value)"
        />
    </u-form-field>
</template>

<script setup>
import { computed } from 'vue';
import { trans } from 'laravel-vue-i18n';
import { useAuth } from '@/Composables/useAuth';

/** Which of the claimant's characters did it. Blank means the main. */
const props = defineProps({
    modelValue: { type: String, default: '' },
    // The event's own switch: off, only the main may claim.
    allowAlts: { type: Boolean, default: true },
    error: { type: String, default: null },
});

const emit = defineEmits(['update:modelValue']);

const { user } = useAuth();

const options = computed(() => {
    const names = user.value?.osrsCharacters ?? [];

    return (props.allowAlts ? names : names.slice(0, 1)).map((name, index) => ({
        value: name,
        label: index === 0 ? trans('bingo.claim_character_main', { name }) : name,
    }));
});
</script>
