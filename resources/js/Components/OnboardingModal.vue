<template>
    <u-modal v-model:open="isOpen" :title="$t('onboarding.title')" :ui="{ content: 'max-w-4xl' }">
        <template #body>
            <u-stepper :items="steps" :model-value="stepIndex" class="mb-6" disabled />

            <!-- Two columns: the step's own content on the left, a live
                 preview on the right that reacts to whatever the form holds
                 right now. Not a canned animation — it renders the same
                 board layout the real page does, so what's previewed is
                 what gets built (see BoardPreview.vue). -->
            <div class="grid md:grid-cols-2 gap-6 items-start min-h-[22rem]">
                <div class="space-y-4">
                    <template v-if="step === 'welcome'">
                        <h3 class="text-lg font-semibold text-highlighted">{{ $t('onboarding.welcome_heading', { name: displayName }) }}</h3>
                        <p class="text-sm text-muted leading-relaxed">{{ $t('onboarding.welcome_body') }}</p>

                        <div class="space-y-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted">{{ $t('onboarding.your_access') }}</p>
                            <div class="flex flex-wrap gap-2">
                                <u-badge v-for="role in roles" :key="role" :label="role" :color="roleColor(role)" variant="subtle" />
                                <u-badge v-if="!roles.length" :label="'PLAYER'" color="primary" variant="subtle" />
                            </div>
                            <ul class="text-sm text-muted space-y-1 mt-2">
                                <li class="flex items-start gap-2">
                                    <u-icon name="i-lucide-check" class="size-4 text-success mt-0.5 shrink-0" />
                                    {{ $t('onboarding.perm_play') }}
                                </li>
                                <li class="flex items-start gap-2">
                                    <u-icon :name="canCreateBoards ? 'i-lucide-check' : 'i-lucide-lock'" class="size-4 mt-0.5 shrink-0" :class="canCreateBoards ? 'text-success' : 'text-muted'" />
                                    {{ canCreateBoards ? $t('onboarding.perm_create_yes') : $t('onboarding.perm_create_no') }}
                                </li>
                            </ul>
                        </div>
                    </template>

                    <template v-else-if="step === 'board'">
                        <h3 class="text-lg font-semibold text-highlighted">{{ $t('onboarding.board_heading') }}</h3>

                        <template v-if="canCreateBoards">
                            <p class="text-sm text-muted">{{ $t('onboarding.board_body') }}</p>

                            <u-form-field :label="$t('admin.board_title')" :error="form.errors.title" required>
                                <u-input v-model="form.title" :placeholder="$t('admin.board_title_placeholder')" class="w-full" />
                            </u-form-field>

                            <u-form-field :label="$t('admin.board_size')">
                                <u-select v-model="form.size" :items="sizeOptions" class="w-full" />
                            </u-form-field>

                            <u-form-field :label="$t('admin.board_mode')" :description="$t('admin.board_mode_desc')">
                                <u-select v-model="form.mode" :items="modeOptions" class="w-full" />
                            </u-form-field>
                        </template>

                        <!-- Without canCreateBoards this step can't do its one
                             real action, so it says so instead of showing a
                             form that would 403 on submit. -->
                        <u-alert
                            v-else
                            color="info"
                            variant="subtle"
                            icon="i-lucide-info"
                            :title="$t('onboarding.board_locked_title')"
                            :description="$t('onboarding.board_locked_desc')"
                        />
                    </template>

                    <template v-else-if="step === 'runelite'">
                        <div class="flex items-center gap-2">
                            <h3 class="text-lg font-semibold text-highlighted">{{ $t('onboarding.runelite_heading') }}</h3>
                            <u-badge :label="$t('onboarding.coming_soon')" color="warning" variant="subtle" size="sm" />
                        </div>
                        <p class="text-sm text-muted leading-relaxed">{{ $t('onboarding.runelite_body') }}</p>
                        <ul class="text-sm text-muted space-y-2">
                            <li v-for="(line, i) in runeliteSteps" :key="i" class="flex items-start gap-2">
                                <span class="size-5 rounded-full bg-elevated text-xs flex items-center justify-center shrink-0 mt-0.5">{{ i + 1 }}</span>
                                {{ line }}
                            </li>
                        </ul>
                        <p class="text-xs text-muted italic">{{ $t('onboarding.runelite_disclaimer') }}</p>
                    </template>
                </div>

                <div class="hidden md:block">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted mb-2">{{ previewLabel }}</p>

                    <board-preview v-if="step !== 'runelite'" :size="form.size" :mode="form.mode" />

                    <!-- Mock, not a screenshot of anything real — the plugin
                         doesn't exist yet (docs/runelite-plugin.md). Drawn
                         rather than shipped as an image so it can't be
                         mistaken for a real product shot. -->
                    <div v-else class="rounded-xl border border-default bg-elevated/50 p-4 space-y-3">
                        <div class="flex items-center gap-2 pb-2 border-b border-default">
                            <div class="size-6 rounded bg-primary/20 flex items-center justify-center">
                                <u-icon name="i-lucide-puzzle" class="size-4 text-primary" />
                            </div>
                            <span class="text-sm font-semibold">RuneLite · Plugin Hub</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="size-8 rounded bg-primary/10 shrink-0 flex items-center justify-center">
                                <u-icon name="i-lucide-swords" class="size-4 text-primary" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium">OSRS Events</p>
                                <p class="text-xs text-muted leading-snug">{{ $t('onboarding.runelite_mock_desc') }}</p>
                            </div>
                            <div class="text-[10px] px-2 py-1 rounded bg-primary/20 text-primary font-medium shrink-0">Install</div>
                        </div>
                        <div class="pt-2 border-t border-default">
                            <p class="text-[10px] uppercase tracking-wide text-muted mb-1">{{ $t('onboarding.runelite_mock_token') }}</p>
                            <div class="h-7 rounded bg-default border border-default flex items-center px-2">
                                <span class="text-[10px] font-mono text-muted">••••-••••-••••</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <div class="flex items-center justify-between w-full gap-2">
                <u-button color="neutral" variant="ghost" :label="$t('onboarding.skip')" @click="finish" />

                <div class="flex gap-2">
                    <u-button v-if="stepIndex > 0" color="neutral" variant="outline" :label="$t('common.back')" @click="stepIndex--" />
                    <u-button v-if="!isLastStep" color="primary" :loading="form.processing" :label="nextLabel" @click="next" />
                    <u-button v-else color="primary" :label="$t('onboarding.finish')" @click="finish" />
                </div>
            </div>
        </template>
    </u-modal>
</template>

<script setup>
import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { useAuth } from '@/Composables/useAuth';
import BoardPreview from '@/Components/BoardPreview.vue';
import { BOARD_SIZE_LABEL, BOARD_TILE_COUNT } from '@/Support/board';

const props = defineProps({
    open: { type: Boolean, default: false },
});

const emit = defineEmits(['update:open']);

const isOpen = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value),
});

const { user, canCreateBoards } = useAuth();

const displayName = computed(() => user.value?.nickname ?? user.value?.discordUsername ?? '');
const roles = computed(() => user.value?.roles ?? []);

const ROLE_COLORS = { ADMIN: 'error', EDITOR: 'warning', TEAM_MANAGER: 'info', PLAYER: 'primary' };
const roleColor = (name) => ROLE_COLORS[name] ?? 'neutral';

const steps = computed(() => [
    { title: trans('onboarding.step_welcome'), icon: 'i-lucide-hand' },
    { title: trans('onboarding.step_board'), icon: 'i-lucide-layout-grid' },
    { title: trans('onboarding.step_runelite'), icon: 'i-lucide-puzzle' },
]);

const stepIndex = ref(0);
const STEP_KEYS = ['welcome', 'board', 'runelite'];
const step = computed(() => STEP_KEYS[stepIndex.value]);
const isLastStep = computed(() => stepIndex.value === STEP_KEYS.length - 1);

const previewLabel = computed(() =>
    step.value === 'runelite'
        ? trans('onboarding.preview_plugin')
        : trans('onboarding.preview_board', { size: BOARD_SIZE_LABEL[form.size], tiles: BOARD_TILE_COUNT[form.size] }),
);

const runeliteSteps = computed(() => [
    trans('onboarding.runelite_step_1'),
    trans('onboarding.runelite_step_2'),
    trans('onboarding.runelite_step_3'),
]);

const sizeOptions = ['SIZE_5X5', 'SIZE_7X7', 'SIZE_9X9'].map((size) => ({
    label: trans('admin.board_size_option', { size: BOARD_SIZE_LABEL[size], tiles: BOARD_TILE_COUNT[size] }),
    value: size,
}));

const modeOptions = [
    { label: trans('admin.board_mode_solo'), value: 'SOLO' },
    { label: trans('admin.board_mode_team'), value: 'TEAM' },
];

// Same endpoint and payload shape the real create flow posts
// (BoardSettingsModal) — this step is a shortcut into it, not a parallel
// implementation, so anything the controller enforces applies here too.
const form = useForm({
    title: '',
    description: '',
    size: 'SIZE_7X7',
    mode: 'SOLO',
    access_mode: 'OPEN',
    is_listed: true,
    author_ids: [],
});

const nextLabel = computed(() =>
    step.value === 'board' && canCreateBoards.value && form.title.trim()
        ? trans('onboarding.create_and_continue')
        : trans('common.next'),
);

function next() {
    // The board step optionally creates a board on its way past. An empty
    // title just moves on — this whole flow is skippable, so requiring one
    // would turn a tour into a wall.
    if (step.value === 'board' && canCreateBoards.value && form.title.trim()) {
        form.post('/boards', {
            preserveScroll: true,
            onSuccess: () => stepIndex.value++,
        });

        return;
    }

    stepIndex.value++;
}

function finish() {
    router.post('/onboarding/complete', {}, {
        preserveScroll: true,
        onFinish: () => (isOpen.value = false),
    });
}
</script>
