<template>
    <div>
        <u-input
            :model-value="modelValue"
            maxlength="12"
            icon="i-lucide-user-round"
            :size="size"
            :placeholder="placeholder"
            class="w-full"
            @update:model-value="onInput"
            @blur="check"
        />

        <p v-if="status" class="text-xs mt-1.5 flex items-start gap-1.5" :class="statusClass">
            <u-icon :name="statusIcon" class="size-3.5 shrink-0 mt-px" />
            <span>{{ statusText }}</span>
        </p>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { trans } from 'laravel-vue-i18n';
import { xsrfHeader } from '@/Support/csrf';

/**
 * The one RSN input, so the three places that ask for a name answer the
 * same way.
 *
 * It checks on BLUR, not while typing: Wise Old Man is somebody else's API
 * and a lookup per keystroke would be both rude and pointless — half a name
 * is never a name. Nothing here blocks: a name the hiscores have never seen
 * is normal for a new account, and a name another account carries is a
 * situation to be told about (see OsrsIdentityService::takenByAnother).
 * The answer is advice; the server still decides on save.
 */
const props = defineProps({
    modelValue: { type: String, default: '' },
    size: { type: String, default: undefined },
    placeholder: { type: String, default: undefined },
});

const emit = defineEmits(['update:modelValue']);

// 'checking' | 'found' | 'missing' | 'taken' | 'failed' | null
const status = ref(null);
const foundName = ref(null);
const checked = ref('');

function onInput(value) {
    emit('update:modelValue', value);

    // The verdict belonged to the previous name. Leaving it on screen while
    // a different one is being typed is the one way this field can lie.
    if (value !== checked.value) status.value = null;
}

// The event's own value, not the prop: a blur in the same tick as the last
// keystroke arrives before the model has caught up, and reading the prop
// there checks the previous name or nothing at all.
async function check(event) {
    const name = (event?.target?.value ?? props.modelValue ?? '').trim();

    if (name === '' || name === checked.value) return;

    checked.value = name;
    status.value = 'checking';

    try {
        const response = await fetch('/welcome/osrs-username/check', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...xsrfHeader(),
            },
            body: JSON.stringify({ osrs_username: name }),
        });

        // 422 is the shape rule talking — the form field's own error already
        // says what is wrong with the name, so this stays quiet.
        if (response.status === 422) {
            status.value = null;

            return;
        }

        if (! response.ok) throw new Error(`check failed: ${response.status}`);

        const result = await response.json();

        foundName.value = result.displayName ?? name;

        // Taken outranks the hiscores answer: it is the one with a
        // consequence attached, and a race will refuse the second entrant.
        if (result.taken) status.value = 'taken';
        else if (result.found === true) status.value = 'found';
        else if (result.found === false) status.value = 'missing';
        else status.value = 'failed';
    } catch (error) {
        console.error(error);
        status.value = 'failed';
    }
}

// The name can be set from outside (a suggestion, a saved value arriving in
// props); a verdict about the old one must not survive it.
watch(() => props.modelValue, (value) => {
    if (value !== checked.value) status.value = null;
});

const statusText = computed(() => {
    if (status.value === 'checking') return trans('auth.osrs_check_checking');
    if (status.value === 'found') return trans('auth.osrs_check_found', { name: foundName.value });
    if (status.value === 'missing') return trans('auth.osrs_check_missing');
    if (status.value === 'taken') return trans('auth.osrs_check_taken');

    return trans('auth.osrs_check_failed');
});

const statusIcon = computed(() => {
    if (status.value === 'checking') return 'i-lucide-loader-circle';
    if (status.value === 'found') return 'i-lucide-check';

    return 'i-lucide-triangle-alert';
});

const statusClass = computed(() => {
    if (status.value === 'found') return 'text-success';
    if (status.value === 'checking') return 'text-muted';

    return 'text-warning';
});
</script>
