<template>
    <settings-layout>
        <Head :title="$t('animations.title')" />

        <div class="flex flex-col gap-6">
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
                <!-- Only ever rendered in a browser, and only on a machine
                     that actually asks for reduced motion. It says what is
                     happening rather than reporting a fault: the OS setting
                     is a deliberate choice for most of the people who have
                     it, and the only thing wrong here was that the switches
                     below claimed to be in charge while it quietly won. -->
                <u-alert
                    v-if="reducedMotion && !values[overrideKey]"
                    color="warning"
                    variant="subtle"
                    icon="i-lucide-pause-circle"
                    :title="$t('animations.reduced_motion_title')"
                    :description="$t('animations.reduced_motion_desc')"
                />

                <div v-for="key in keys" :key="key" class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="font-medium" :class="{ 'text-muted': overridden }">{{ $t(`animations.${key}`) }}</p>
                        <p class="text-sm text-muted">{{ $t(`animations.${key}_desc`) }}</p>
                    </div>
                    <u-switch v-model="values[key]" class="shrink-0 mt-0.5" @update:model-value="save" />
                </div>

                <!-- Below the switches it overrules, and only where the
                     question exists at all: on a machine with no reduced-motion
                     preference this would be a switch about nothing. -->
                <template v-if="reducedMotion">
                    <u-separator />

                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="font-medium">{{ $t(`animations.${overrideKey}`) }}</p>
                            <p class="text-sm text-muted">{{ $t(`animations.${overrideKey}_desc`) }}</p>
                        </div>
                        <u-switch v-model="values[overrideKey]" class="shrink-0 mt-0.5" @update:model-value="save" />
                    </div>
                </template>
            </div>
        </u-card>

        <!-- Its own card rather than a fourth switch above, because the card
             above is titled "Board animation" and this is not about a board.
             It is also the only switch here that changes something a
             signed-out visitor sees — for them the catalogue's default is the
             whole answer. -->
        <u-card>
            <template #header>
                <p class="font-medium">{{ $t('animations.ambient_title') }}</p>
                <p class="text-sm text-muted">{{ $t('animations.ambient_desc') }}</p>
            </template>

            <div class="flex flex-col gap-5">
                <div v-for="key in ambientKeys" :key="key" class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="font-medium">{{ $t(`animations.${key}`) }}</p>
                        <p class="text-sm text-muted">{{ $t(`animations.${key}_desc`) }}</p>
                    </div>
                    <u-switch v-model="values[key]" class="shrink-0 mt-0.5" @update:model-value="save" />
                </div>
            </div>
        </u-card>
        </div>
    </settings-layout>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';

import SettingsLayout from '@/Components/SettingsLayout.vue';

const props = defineProps({
    // Already resolved against the catalogue's defaults by the controller.
    preferences: { type: Object, required: true },
    // The catalogue's order, so adding a setting server-side puts it on the
    // page without touching this file.
    keys: { type: Array, required: true },
    // Site-wide rather than board-wide, so its own card below.
    ambientKeys: { type: Array, required: true },
    // Rendered apart from the list above, because it is only a question on a
    // machine that asks for reduced motion.
    overrideKey: { type: String, required: true },
});

const values = ref({ ...props.preferences });

// Set after mount, never during render: `matchMedia` does not exist on the
// server, and a value that differs between the SSR pass and the first client
// render is a hydration mismatch. False until mounted means the page renders
// identically on both sides and then gains the notice — see
// docs/ssr-gotchas.md.
const reducedMotion = ref(false);

onMounted(() => {
    reducedMotion.value = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ?? false;
});

/** What the board will actually do, which is what the switches should look like. */
const overridden = computed(() => reducedMotion.value && !values.value[props.overrideKey]);

function save() {
    router.put('/settings/animations', { preferences: values.value }, { preserveScroll: true });
}
</script>
