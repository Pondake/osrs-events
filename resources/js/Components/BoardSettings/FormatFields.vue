<template>
    <div class="space-y-5 py-2">
        <!-- Solo vs teams, for EVERY event type.
             This existed only inside the Snakes & Ladders block, so a bingo
             card or a race could never be a team event — and the Teams step
             it gates could therefore never appear. Reported as "there is
             nowhere to choose individual progress or teams", and it was the
             literal truth for three of the four types. Bingo and both race
             types have scored per-team on the server the whole time
             (BingoService::competitorFor branches on it); the form was
             simply never able to say so. -->
        <u-form-field :label="$t('admin.board_mode')" :description="$t('admin.board_mode_desc')">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <button
                    v-for="option in modeOptions"
                    :key="option.value"
                    type="button"
                    class="flex items-start gap-3 p-3 rounded-lg ring text-left transition-colors cursor-pointer"
                    :class="form.mode === option.value ? 'ring-primary bg-primary/5' : 'ring-default hover:ring-primary/50'"
                    @click="form.mode = option.value"
                >
                    <u-icon :name="option.icon" class="size-5 shrink-0 mt-0.5" :class="form.mode === option.value ? 'text-primary' : 'text-muted'" />
                    <span class="min-w-0">
                        <span class="block font-medium text-sm">{{ option.label }}</span>
                        <span class="block text-xs text-muted mt-0.5">{{ option.description }}</span>
                    </span>
                </button>
            </div>
        </u-form-field>

        <u-separator />

        <!-- Only for types that race on a metric. Snakes & Ladders has none,
             and the server rejects one. -->
        <u-form-field
            v-if="needsMetric"
            :label="$t(metricKind === 'boss' ? 'events.metric_label_boss' : 'events.metric_label')"
            :description="$t(metricKind === 'boss' ? 'events.metric_desc_boss' : 'events.metric_desc')"
            :error="form.errors.metric"
            required
        >
            <metric-picker v-model="form.metric" :kind="metricKind" :metrics="metrics" />
        </u-form-field>

        <!-- Bingo brings its own grid, unrelated to the Snakes & Ladders one:
             a side length rather than a size enum, plus what counts as
             winning and whether a host signs claims off. -->
        <template v-if="isBingo">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <u-form-field
                    :label="$t('bingo.card_size')"
                    :description="$t('bingo.card_size_desc')"
                    :error="form.errors.bingo_size"
                    required
                >
                    <u-select v-model="form.bingo_size" :items="bingoSizeOptions" class="w-full" />
                </u-form-field>

                <u-form-field :label="$t('bingo.win_condition')" :description="$t('bingo.win_condition_desc')">
                    <u-select v-model="form.win_condition" :items="winConditionOptions" class="w-full" />
                </u-form-field>
            </div>

            <!-- What counts as a line. "First line wins" was quietly doing
                 three jobs — rows, columns and both diagonals — with no way
                 to say a card is rows-only, or that the diagonals are the
                 whole point. On a 3x3 that choice decides how long the
                 event lasts. -->
            <u-form-field
                v-if="form.win_condition === 'LINE'"
                :label="$t('bingo.win_lines')"
                :description="$t('bingo.win_lines_desc')"
                :error="form.errors.win_lines"
            >
                <div class="flex flex-col gap-2">
                    <u-checkbox
                        v-for="kind in lineKindOptions"
                        :key="kind.value"
                        :model-value="(form.win_lines ?? []).includes(kind.value)"
                        :label="kind.label"
                        :description="kind.description"
                        @update:model-value="(on) => toggleLineKind(kind.value, on)"
                    />
                </div>
            </u-form-field>

            <u-form-field :label="$t('bingo.line_bonus')" :description="$t('bingo.line_bonus_desc')">
                <u-input v-model.number="form.line_bonus" type="number" min="0" max="1000" class="w-full sm:max-w-40" />
            </u-form-field>

            <u-form-field :description="$t('bingo.requires_approval_desc')">
                <u-switch v-model="form.requires_approval" :label="$t('bingo.requires_approval')" />
            </u-form-field>
        </template>

        <!-- Snakes & Ladders is the only type with a grid to size or dice to
             limit, so both are hidden for anything else rather than sitting
             there doing nothing. -->
        <template v-if="hasBoard">
            <u-form-field :label="$t('admin.board_size')" :description="$t('admin.board_size_desc')" required>
                <u-select v-model="form.size" :items="sizeOptions" class="w-full sm:max-w-xs" />
            </u-form-field>

            <!-- The description has always promised "set to Unlimited", and
                 null is exactly what the server stores for it — but a
                 `type=number min=1` spinner has no way to express null, so
                 the state was reachable only by never touching the field. -->
            <u-form-field :label="$t('admin.dice_roll_limit')" :description="$t('admin.dice_roll_limit_desc')">
                <div class="space-y-3">
                    <u-switch v-model="unlimitedRolls" :label="$t('admin.dice_roll_unlimited')" />
                    <u-input
                        v-if="!unlimitedRolls"
                        v-model.number="form.dice_roll_limit"
                        type="number"
                        min="1"
                        class="w-full sm:max-w-40"
                    />
                </div>
            </u-form-field>

            <!-- Same setting bingo cards carry, same reason: a plain
                 self-toggle on a task tile is the same trust problem a
                 bingo square was, unsolved on this board type until now. -->
            <u-form-field :description="$t('board.requires_approval_desc')">
                <u-switch v-model="form.requires_approval" :label="$t('board.requires_approval')" />
            </u-form-field>
        </template>

        <!-- Every type ends up here eventually; saying so is better than an
             empty panel that reads as a bug. -->
        <p v-if="!needsMetric && !isBingo && !hasBoard" class="text-sm text-muted">
            {{ $t('admin.format_no_extra_settings') }}
        </p>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import MetricPicker from '@/Components/MetricPicker.vue';
import { BOARD_SIZE_LABEL, BOARD_TILE_COUNT } from '@/Support/board';

const props = defineProps({
    form: { type: Object, required: true },
});

const site = computed(() => usePage().props?.site ?? {});

const selectedType = computed(() => (site.value.eventTypes ?? []).find((t) => t.value === props.form.type));

const needsMetric = computed(() => Boolean(selectedType.value?.needsMetric));
const metricKind = computed(() => selectedType.value?.metricKind ?? null);
const metrics = computed(() => site.value.metricsByKind?.[metricKind.value] ?? []);

const hasBoard = computed(() => props.form.type === 'SNAKES_LADDERS');
const isBingo = computed(() => props.form.type === 'BINGO');

const modeOptions = [
    {
        value: 'SOLO',
        label: trans('admin.board_mode_solo'),
        description: trans('admin.board_mode_solo_desc'),
        icon: 'i-lucide-user',
    },
    {
        value: 'TEAM',
        label: trans('admin.board_mode_team'),
        description: trans('admin.board_mode_team_desc'),
        icon: 'i-lucide-users',
    },
];

const sizeOptions = ['SIZE_5X5', 'SIZE_7X7', 'SIZE_9X9'].map((size) => ({
    label: trans('admin.board_size_option', { size: BOARD_SIZE_LABEL[size], tiles: BOARD_TILE_COUNT[size] }),
    value: size,
}));

const bingoSizeOptions = [3, 4, 5, 6, 7, 8, 9, 10].map((size) => ({
    value: size,
    label: trans('bingo.size_option', { size }),
}));

const lineKindOptions = [
    { value: 'ROW', label: trans('bingo.line_rows'), description: trans('bingo.line_rows_desc') },
    { value: 'COLUMN', label: trans('bingo.line_columns'), description: trans('bingo.line_columns_desc') },
    { value: 'DIAGONAL', label: trans('bingo.line_diagonals'), description: trans('bingo.line_diagonals_desc') },
];

/**
 * Toggling the last one back on rather than off.
 *
 * A card with no line kinds at all makes "first line wins" a condition
 * nothing can meet, so the server refuses it — and refusing a click after
 * the fact is worse than not letting it land. Unticking the last remaining
 * kind is ignored.
 */
function toggleLineKind(kind, on) {
    const current = props.form.win_lines ?? [];

    if (on) {
        props.form.win_lines = [...new Set([...current, kind])];

        return;
    }

    if (current.length <= 1) return;

    props.form.win_lines = current.filter((k) => k !== kind);
}

const winConditionOptions = [
    { value: 'LINE', label: trans('bingo.win_line') },
    { value: 'FULL_HOUSE', label: trans('bingo.win_full_house') },
];

/**
 * null is "unlimited" on the server side, so this is a view of the field
 * rather than a value of its own — nothing extra to keep in sync, and
 * nothing to forget to reset when the modal reopens on another board.
 */
const unlimitedRolls = computed({
    get: () => props.form.dice_roll_limit === null || props.form.dice_roll_limit === '',
    set: (on) => {
        props.form.dice_roll_limit = on ? null : (site.value.defaultDiceRollLimit ?? 1);
    },
});
</script>
