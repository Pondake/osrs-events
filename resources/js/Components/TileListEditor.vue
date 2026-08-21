<template>
    <u-modal
        v-model:open="isOpen"
        :title="$t('tile_list.title')"
        :description="$t(isBingo ? 'tile_list.desc_bingo' : 'tile_list.desc_board')"
        :ui="{ content: 'max-w-2xl' }"
    >
        <template #body>
            <div class="space-y-4">
                <!-- How far along you are. A grid of 49 tiles gives no answer
                     to "am I done", and counting empty squares by eye is the
                     job this list exists to replace. -->
                <div>
                    <div class="flex items-center justify-between text-sm mb-1.5">
                        <span class="text-muted">{{ $t('tile_list.progress', { filled, total: rows.length }) }}</span>
                        <span class="tabular-nums text-highlighted">{{ pct }}%</span>
                    </div>
                    <u-progress :model-value="pct" size="sm" />
                </div>

                <!-- Which way the numbering runs. The two grids genuinely
                     disagree — a Snakes & Ladders board is a track that
                     starts bottom-left and snakes upward, a bingo card is
                     read like text — and a numbered list with no stated
                     direction is a list you cannot map onto the board. -->
                <u-alert
                    color="neutral"
                    variant="subtle"
                    :icon="isBingo ? 'i-lucide-corner-down-right' : 'i-lucide-move-up-right'"
                    :description="$t(isBingo ? 'tile_list.direction_bingo' : 'tile_list.direction_board')"
                />

                <div class="flex items-center gap-2">
                    <u-switch v-model="onlyEmpty" :label="$t('tile_list.only_empty')" />
                </div>

                <div class="divide-y divide-default rounded-lg ring ring-default max-h-[26rem] overflow-y-auto">
                    <div v-for="row in visibleRows" :key="row.position">
                        <button
                            type="button"
                            class="w-full flex items-center gap-3 px-3 py-2.5 text-left hover:bg-elevated transition-colors"
                            :class="expanded === row.position ? 'bg-elevated' : ''"
                            @click="toggle(row.position)"
                        >
                            <span class="w-8 shrink-0 text-xs font-semibold tabular-nums text-muted">
                                {{ row.label }}
                            </span>

                            <img v-if="row.iconUrl" :src="row.iconUrl" alt="" class="size-6 object-contain shrink-0" />
                            <u-icon v-else-if="row.isWildcard" name="i-lucide-star" class="size-5 text-warning shrink-0" />
                            <u-icon v-else name="i-lucide-square-dashed" class="size-5 text-dimmed shrink-0" />

                            <span class="flex-1 min-w-0 truncate text-sm" :class="row.title ? '' : 'text-dimmed italic'">
                                {{ row.title || $t(row.isWildcard ? 'bingo.wildcard' : 'tile_list.empty') }}
                            </span>

                            <!-- Snakes and ladders say where they go, since
                                 that is the whole of what makes them one. -->
                            <u-badge
                                v-if="row.type && row.type !== 'NORMAL'"
                                :label="row.type === 'SNAKE'
                                    ? $t('tile_list.snake_to', { n: (row.targetPosition ?? 0) + 1 })
                                    : $t('tile_list.ladder_to', { n: (row.targetPosition ?? 0) + 1 })"
                                :color="row.type === 'SNAKE' ? 'error' : 'success'"
                                variant="subtle"
                                size="sm"
                                class="shrink-0"
                            />

                            <u-icon
                                :name="expanded === row.position ? 'i-lucide-chevron-up' : 'i-lucide-chevron-down'"
                                class="size-4 text-muted shrink-0"
                            />
                        </button>

                        <!-- Inline rather than a nested dialog. A modal on top
                             of a modal traps focus twice and hides the list
                             you are working down, which is the only reason to
                             be in a list. -->
                        <div v-if="expanded === row.position" class="px-3 pb-3 pt-1 space-y-3 bg-elevated/50">
                            <u-form-field :label="$t('tile_editor.task')">
                                <task-picker
                                    :model-value="draft.task"
                                    :event-id="eventId"
                                    @update:model-value="(task) => (draft.task = task)"
                                />
                            </u-form-field>

                            <u-form-field :label="$t('tile_editor.title_override')">
                                <u-input v-model="draft.titleOverride" class="w-full" :placeholder="draft.task?.title ?? ''" />
                            </u-form-field>

                            <template v-if="isBingo">
                                <u-form-field :description="$t('bingo.wildcard_desc')">
                                    <u-switch v-model="draft.isWildcard" :label="$t('bingo.wildcard_field')" />
                                </u-form-field>

                                <u-form-field :label="$t('bingo.points_field')">
                                    <u-input v-model.number="draft.points" type="number" min="0" max="1000" class="w-full sm:max-w-32" />
                                </u-form-field>
                            </template>

                            <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <u-form-field :label="$t('tile_editor.tile_type')">
                                    <u-select v-model="draft.type" :items="tileTypeOptions" class="w-full" />
                                </u-form-field>

                                <!-- A picker, not a number box. "Target
                                     position" asked people to know the
                                     board's own zero-based indexing; this
                                     lists the tiles by the number printed on
                                     them. -->
                                <u-form-field v-if="draft.type !== 'NORMAL'" :label="$t('tile_editor.target_tile')">
                                    <u-select v-model="draft.targetPosition" :items="targetOptions(row.position)" class="w-full" />
                                </u-form-field>
                            </div>

                            <div class="flex items-center gap-2">
                                <u-button
                                    color="primary"
                                    size="sm"
                                    :loading="saving"
                                    :label="$t('common.save')"
                                    @click="save(row)"
                                />
                                <u-button
                                    color="neutral"
                                    variant="ghost"
                                    size="sm"
                                    :label="$t('common.cancel')"
                                    @click="expanded = null"
                                />
                                <u-button
                                    v-if="!isBingo && row.exists"
                                    color="error"
                                    variant="ghost"
                                    size="sm"
                                    icon="i-lucide-trash-2"
                                    class="ms-auto"
                                    :label="$t('tile_editor.clear_tile')"
                                    @click="clear(row)"
                                />
                            </div>
                        </div>
                    </div>

                    <p v-if="!visibleRows.length" class="px-3 py-8 text-center text-sm text-muted">
                        {{ $t('tile_list.all_filled') }}
                    </p>
                </div>
            </div>
        </template>

        <template #footer>
            <u-button color="neutral" variant="outline" class="ms-auto" :label="$t('tile_list.done')" @click="isOpen = false" />
        </template>
    </u-modal>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import TaskPicker from '@/Components/TaskPicker.vue';

/**
 * Fills in a whole board, one row at a time.
 *
 * The grid is the right shape for playing a board and the wrong one for
 * building it: clicking 49 tiles in turn with no sense of how many are still
 * empty is the part of setting up an event that actually takes an hour. This
 * is the same data as a list — numbered, countable, filterable down to what
 * is still blank.
 *
 * One component for both grids because the differences are two: which way
 * the numbering runs, and what a row carries besides a task (a bingo square
 * has points and a free-square flag; a Snakes & Ladders tile has a type and
 * somewhere to send you).
 */
const props = defineProps({
    open: { type: Boolean, default: false },
    eventId: { type: String, required: true },
    // 'BINGO' or 'SNAKES_LADDERS'.
    type: { type: String, required: true },
    // Bingo: the card's squares. Snakes & Ladders: the tiles that exist so
    // far — the rest of the grid is filled in from `total`, since an S&L
    // tile is only created on first edit.
    items: { type: Array, default: () => [] },
    total: { type: Number, required: true },
});

const emit = defineEmits(['update:open']);

const isOpen = computed({ get: () => props.open, set: (v) => emit('update:open', v) });

const isBingo = computed(() => props.type === 'BINGO');

/**
 * One row per position, whether or not a record exists for it yet.
 *
 * `label` is the number printed on the tile — position + 1 — because that is
 * what the board shows and what somebody reading this list will look for.
 */
const rows = computed(() => Array.from({ length: props.total }, (_, position) => {
    const item = props.items.find((i) => i.position === position) ?? null;

    return {
        position,
        label: String(position + 1),
        exists: item !== null,
        id: item?.id ?? null,
        title: item?.label ?? item?.title_override ?? item?.task?.title ?? null,
        iconUrl: item?.iconUrl ?? item?.task?.icon_url ?? null,
        task: item?.task ?? null,
        titleOverride: item?.titleOverride ?? item?.title_override ?? '',
        points: item?.points ?? 1,
        isWildcard: item?.isWildcard ?? false,
        type: item?.type ?? 'NORMAL',
        targetPosition: item?.target_position ?? null,
    };
}));

// A square counts as filled once it asks for something — a task, a wording
// of its own, or the free-square flag.
const filled = computed(() => rows.value.filter((r) => r.title || r.isWildcard).length);
const pct = computed(() => (rows.value.length ? Math.round((filled.value / rows.value.length) * 100) : 0));

const onlyEmpty = ref(false);

const visibleRows = computed(() => (
    onlyEmpty.value ? rows.value.filter((r) => !r.title && !r.isWildcard) : rows.value
));

const expanded = ref(null);
const saving = ref(false);

// The row being edited, held apart from the list so a half-typed change is
// not written into the grid behind the modal.
const draft = reactive({ task: null, titleOverride: '', points: 1, isWildcard: false, type: 'NORMAL', targetPosition: null });

function toggle(position) {
    if (expanded.value === position) {
        expanded.value = null;

        return;
    }

    const row = rows.value.find((r) => r.position === position);

    draft.task = row.task;
    draft.titleOverride = row.titleOverride ?? '';
    draft.points = row.points ?? 1;
    draft.isWildcard = row.isWildcard ?? false;
    draft.type = row.type ?? 'NORMAL';
    draft.targetPosition = row.targetPosition;

    expanded.value = position;
}

// Closing the dialog drops whatever was open, so reopening never lands on a
// half-filled form belonging to a row you have forgotten about.
watch(() => props.open, (open) => {
    if (!open) expanded.value = null;
});

const tileTypeOptions = [
    { label: trans('tile_editor.type_normal'), value: 'NORMAL' },
    { label: trans('tile_editor.type_snake_full'), value: 'SNAKE' },
    { label: trans('tile_editor.type_ladder_full'), value: 'LADDER' },
];

/** Every other tile, by its printed number — not its zero-based position. */
function targetOptions(from) {
    return rows.value
        .filter((r) => r.position !== from)
        .map((r) => ({ label: trans('tile_list.tile_n', { n: r.label }), value: r.position }));
}

function save(row) {
    saving.value = true;

    const done = {
        preserveScroll: true,
        onSuccess: () => (expanded.value = null),
        onError: (errors) => console.error(errors),
        onFinish: () => (saving.value = false),
    };

    if (isBingo.value) {
        router.patch(`/events/${props.eventId}/bingo/squares/${row.id}`, {
            task_id: draft.isWildcard ? null : (draft.task?.id ?? null),
            title_override: draft.titleOverride || null,
            points: draft.points,
            is_wildcard: draft.isWildcard,
        }, done);

        return;
    }

    router.post(`/events/${props.eventId}/tiles`, {
        position: row.position,
        task_id: draft.task?.id ?? null,
        title_override: draft.titleOverride || null,
        type: draft.type,
        target_position: draft.type === 'NORMAL' ? null : draft.targetPosition,
    }, done);
}

function clear(row) {
    router.delete(`/events/${props.eventId}/tiles/${row.id}`, {
        preserveScroll: true,
        onSuccess: () => (expanded.value = null),
        onError: (errors) => console.error(errors),
    });
}
</script>
