<template>
    <settings-layout>
        <Head :title="$t('animations.title')" />

        <u-card>
            <template #header>
                <p class="font-medium">{{ $t('animations.title') }}</p>
                <p class="text-sm text-muted">{{ $t('animations.desc') }}</p>
            </template>

            <!-- Saved per switch rather than behind a Save button: each one
                 takes effect on the next roll, and the thing they change is
                 on a board one click away — a Save step here would only add a
                 way to think you had changed something and not have. -->
            <div class="flex flex-col gap-5">
                <div v-for="key in keys" :key="key" class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="font-medium">{{ $t(`animations.${key}`) }}</p>
                        <p class="text-sm text-muted">{{ $t(`animations.${key}_desc`) }}</p>
                    </div>
                    <u-switch v-model="values[key]" class="shrink-0 mt-0.5" @update:model-value="save" />
                </div>
            </div>
        </u-card>
    </settings-layout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';

import SettingsLayout from '@/Components/SettingsLayout.vue';

const props = defineProps({
    // Already resolved against the catalogue's defaults by the controller.
    preferences: { type: Object, required: true },
    // The catalogue's order, so adding a setting server-side puts it on the
    // page without touching this file.
    keys: { type: Array, required: true },
});

const values = ref({ ...props.preferences });

function save() {
    router.put('/settings/animations', { preferences: values.value }, { preserveScroll: true });
}
</script>
