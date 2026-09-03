<template>
    <u-modal
        v-model:open="isOpen"
        :title="isEdit ? $t('admin.edit_board') : $t('admin.create_board')"
        :description="isEdit ? undefined : $t('admin.create_board_desc')"
        :dismissible="false"
        :ui="{ content: 'max-w-2xl' }"
    >
        <template #body>
            <!-- Every problem with the form, in one place, before the tabs.
                 Relying on the error appearing under its own field means
                 relying on the reader being on that tab — and they are not,
                 because the tab they are on is the one they were working in.
                 Reported as "I hit save, it jumped back to Basics and saved
                 nothing": it was a 422 on the Access tab, invisible from
                 Teams. Clicking a row here goes to the tab that owns it. -->
            <div v-if="errorList.length" class="mb-4 rounded-lg ring ring-error/40 bg-error/5 px-3 py-2.5">
                <p class="text-sm font-medium text-error mb-1">{{ $t('validation.form_has_errors') }}</p>
                <ul class="space-y-0.5">
                    <li v-for="entry in errorList" :key="entry.field">
                        <button
                            type="button"
                            class="text-xs text-error/90 hover:text-error hover:underline text-left"
                            @click="showTabFor({ [entry.field]: entry.message })"
                        >{{ entry.message }}</button>
                    </li>
                </ul>
            </div>

            <!-- CLAUDE.md's rule, now actually followed: a stepper for
                 create, tabs for edit. It was tabs for both, which put five
                 sections of a thing that does not exist yet in front of
                 someone who has not named it — and let them reach a Teams
                 tab before choosing whether the event has teams at all.
                 Editing is free navigation because every section is already
                 filled in; creating is linear because the later steps depend
                 on the earlier ones (the type decides which settings exist,
                 the mode decides whether teams do). -->
            <u-tabs v-if="isEdit" v-model="activeTab" :items="tabs" class="w-full">
                <template #basics>
                    <basics-fields :form="form" is-edit />
                </template>
                <template #format>
                    <!-- The size can still be changed after a template is
                         applied, and a layout is a snapshot of one grid. The
                         server drops what does not fit rather than stacking
                         it on the last square — this is so that is a choice
                         rather than a surprise. -->
                    <u-alert
                        v-if="layoutWillBeTrimmed"
                        color="warning"
                        variant="subtle"
                        icon="i-lucide-scissors"
                        class="mb-4"
                        :description="$t('blueprints.layout_resized')"
                    />

                    <format-fields :form="form" />
                </template>
                <template #access>
                    <access-fields
                        :form="form"
                        is-edit
                        :discord-webhooks-enabled="discordWebhooksEnabled"
                        :guilds="guilds"
                        :loading-guilds="loadingGuilds"
                        :has-discord="hasDiscord"
                        :authors="selectedAuthors"
                        :author-search="authorSearch"
                        :author-results="authorResults"
                        :current-user-id="currentUser?.id"
                        @update:author-search="onAuthorSearch"
                        @add-author="addAuthor"
                        @remove-author="removeAuthor"
                    />
                </template>
                <template #invites>
                    <invite-fields
                        :event-id="board?.id ?? null"
                        :invites="invites"
                        :open-count="openInvites"
                        :max-open="maxOpenInvites"
                        :creating="creatingInvite"
                        @create="createInvite"
                        @revoke="revokeInvite"
                    />
                </template>
                <template #danger>
                    <danger-fields
                        :title="form.title"
                        :can-delete="canDelete"
                        :event="board"
                        :finishes="finishes"
                        :paused-at="board?.paused_at ?? null"
                        :closed-at="board?.closed_at ?? null"
                        :pausing="pausing"
                        :closing="closing"
                        :deleting="deleting"
                        @pause="setPaused"
                        @close-event="setClosed"
                        @destroy="destroyEvent"
                    />
                </template>
                <template #teams>
                    <team-fields
                        v-model="teamToAdd"
                        :assigned="assignedTeams"
                        :available="availableTeams"
                        :loading="loadingTeams"
                        :adding="addingTeam"
                        is-edit
                        @add="addTeam"
                        @remove="removeTeam"
                    />
                </template>
            </u-tabs>

            <u-stepper v-else ref="stepper" v-model="currentStep" :items="steps" size="sm" class="w-full">
                <template #template>
                    <template-fields
                        :blueprints="blueprintResults"
                        :loading="loadingBlueprints"
                        :search="blueprintSearch"
                        :selected-id="appliedBlueprintId"
                        @search="onBlueprintSearch"
                        @apply="applyBlueprint"
                        @skip="skipBlueprint"
                    />
                </template>

                <template #type>
                    <div class="space-y-3 py-2">
                        <p class="text-sm text-muted">{{ $t('events.type_desc') }}</p>

                        <!-- A grid of cards, not a dropdown. This is the
                             first decision and it changes every screen after
                             it; a collapsed <select> shows one option at a
                             time and hides that there are four kinds of event
                             to choose between. -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <button
                                v-for="option in typeOptions"
                                :key="option.value"
                                type="button"
                                class="flex items-start gap-3 p-3 rounded-lg ring text-left transition-colors"
                                :class="typeCardClass(option)"
                                :disabled="option.disabled"
                                @click="form.type = option.value"
                            >
                                <u-icon :name="option.icon" class="size-5 shrink-0 mt-0.5" :class="form.type === option.value ? 'text-primary' : 'text-muted'" />
                                <span class="min-w-0">
                                    <span class="flex items-center gap-2 flex-wrap">
                                        <span class="font-medium text-sm">{{ option.label }}</span>
                                        <u-badge v-if="option.disabled" :label="$t('events.type_unavailable')" color="neutral" variant="subtle" size="sm" />
                                    </span>
                                    <span class="block text-xs text-muted mt-0.5">{{ option.description }}</span>
                                </span>
                            </button>
                        </div>
                    </div>
                </template>

                <template #basics>
                    <basics-fields :form="form" />
                </template>

                <template #format>
                    <format-fields :form="form" />
                </template>

                <template #access>
                    <access-fields
                        :form="form"
                        :guilds="guilds"
                        :loading-guilds="loadingGuilds"
                        :has-discord="hasDiscord"
                        :authors="selectedAuthors"
                        :author-search="authorSearch"
                        :author-results="authorResults"
                        :current-user-id="currentUser?.id"
                        @update:author-search="onAuthorSearch"
                        @add-author="addAuthor"
                        @remove-author="removeAuthor"
                    />
                </template>

                <template #teams>
                    <team-fields
                        v-model="teamToAdd"
                        :assigned="assignedTeams"
                        :available="availableTeams"
                        :loading="loadingTeams"
                        :adding="addingTeam"
                        @add="addTeam"
                        @remove="removeTeam"
                    />
                </template>
            </u-stepper>
        </template>

        <template #footer>
            <div class="flex items-center justify-between gap-2 w-full">
                <!-- Says which step of how many even when the stepper's own
                     rail has scrolled out of view on a narrow screen. -->
                <span v-if="!isEdit" class="text-xs text-muted tabular-nums">
                    {{ $t('admin.step_counter', { current: stepIndex + 1, total: steps.length }) }}
                </span>

                <!-- Left of the save button and visually separate from it,
                     because it is not part of saving: it writes a second
                     thing that outlives this event. Offered while editing
                     because that is when a host is thinking about the
                     settings; the event page offers it again once the event
                     has finished, which is when they know whether the format
                     was worth keeping. -->
                <blueprint-save-modal
                    v-else-if="board"
                    :event-id="board.id"
                    :event-title="board.title"
                    variant="ghost"
                />

                <div class="flex items-center gap-2">
                    <u-button color="neutral" variant="ghost" :label="$t('common.cancel')" @click="isOpen = false" />

                    <u-button
                        v-if="!isEdit && stepIndex > 0"
                        color="neutral"
                        variant="outline"
                        icon="i-lucide-arrow-left"
                        :label="$t('common.back')"
                        @click="goBack"
                    />

                    <u-button
                        v-if="!isEdit && stepIndex < steps.length - 1"
                        color="primary"
                        trailing-icon="i-lucide-arrow-right"
                        :label="$t('common.next')"
                        @click="goNext"
                    />

                    <u-button
                        v-else
                        color="primary"
                        :label="isEdit ? $t('common.save') : $t('admin.create_board')"
                        :loading="form.processing"
                        @click="submit"
                    />
                </div>
            </div>
        </template>
    </u-modal>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { useAuth } from '@/Composables/useAuth';
import AccessFields from '@/Components/BoardSettings/AccessFields.vue';
import BasicsFields from '@/Components/BoardSettings/BasicsFields.vue';
import FormatFields from '@/Components/BoardSettings/FormatFields.vue';
import InviteFields from '@/Components/BoardSettings/InviteFields.vue';
import DangerFields from '@/Components/BoardSettings/DangerFields.vue';
import TeamFields from '@/Components/BoardSettings/TeamFields.vue';
import TemplateFields from '@/Components/BoardSettings/TemplateFields.vue';
import BlueprintSaveModal from '@/Components/BlueprintSaveModal.vue';
import { blueprintPatch, decidesType, layoutFits } from '@/Support/blueprint';
import { DEFAULT_DURATION, addDuration } from '@/Support/duration';

const { user: currentUser } = useAuth();

const props = defineProps({
    open: { type: Boolean, default: false },
    board: { type: Object, default: null },
    // Kept out of the `board` payload on purpose: that object is what every
    // viewer of a public event receives and what the live channel pushes, and
    // a webhook URL is a capability rather than a fact about the event. The
    // server only fills this in for somebody who may edit.
    webhookUrl: { type: String, default: null },
    // Where this modal's writes go. The admin section passes '/admin/events'
    // because an admin editing somebody else's event is a different route
    // with a different check behind it — on the public side an admin is an
    // ordinary user and these endpoints answer on authorship alone.
    // Creating is not part of that split: a new event has an author by
    // definition, so it always posts to /events.
    basePath: { type: String, default: '/events' },
    /**
     * Which tab to open on. The Manage menu offers "Event status" as its own
     * entry — the state of an event is the thing a host most often opens
     * this dialog to check — and without this it would land on Basics and
     * make them hunt for the tab they asked for.
     */
    initialTab: { type: String, default: 'basics' },
    /**
     * The podium as it stands, for the Status tab's end-now confirmation to
     * name. A page prop rather than part of the event card — it arrives on
     * the live stream and changes without the event itself changing.
     */
    finishes: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:open']);

const isOpen = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value),
});

const isEdit = computed(() => props.board !== null);

/** This event's endpoints, on whichever side of the app the modal is open. */
const eventPath = computed(() => `${props.basePath}/${props.board?.id}`);

const site = () => usePage().props?.site ?? {};

// Site-wide switch for the Discord announcements field — see Setting::DEFAULTS.
const discordWebhooksEnabled = computed(() => usePage().props?.site?.discordWebhooksEnabled ?? false);

// ---------------------------------------------------------------- the form

/**
 * Today, and a fortnight from today, as `<input type="date">` wants them.
 *
 * Built from the local date parts rather than toISOString(): that converts
 * to UTC first, so anyone east of UTC gets tomorrow and anyone far enough
 * west gets yesterday — the same timezone trap dateFields() below avoids
 * when reading a saved date back.
 */
function isoDate(date) {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}

function defaultDates() {
    const start = new Date();

    // How long a new event is pre-filled to run for, from the admin site
    // settings — a clan that always runs weeklies should say so once rather
    // than correct the same field on every event.
    //
    // A duration, not a day count: "1m" from 31 January has to land on
    // 28 February, and only the unit can tell you that. See
    // Support/duration.js, and app/Support/EventDuration.php for the copy
    // the server computes from.
    const end = addDuration(start, site().defaultEventDuration ?? DEFAULT_DURATION);

    return { start_date: isoDate(start), end_date: isoDate(end) };
}

function blankForm() {
    // Size and roll limit come from the admin site settings so a clan can set
    // the shape its events usually take once, instead of every creator
    // changing the same two fields each time.
    const settings = site();

    return {
        title: '',
        type: 'SNAKES_LADDERS',
        metric: null,
        description: '',
        size: settings.defaultBoardSize ?? 'SIZE_7X7',
        // Bingo's own grid. 5x5 is the conventional card, and the win
        // condition defaults to first-line because that is the shorter, more
        // common event.
        bingo_size: 5,
        win_condition: 'LINE',
        // All three, which is what every card behaved as before the setting
        // existed.
        win_lines: ['ROW', 'COLUMN', 'DIAGONAL'],
        line_bonus: 0,
        requires_approval: true,
        mode: 'SOLO',
        // What happens when the first competitor gets home. CONTINUE is the
        // forgiving default — see the finish_rule migration.
        finish_rule: 'CONTINUE',
        ...defaultDates(),
        dice_roll_limit: settings.defaultDiceRollLimit ?? null,
        is_listed: true,
        discord_webhook_url: '',
        access_mode: 'OPEN',
        required_guild_id: '',
        author_ids: [],
        // Only read on create — an existing event's teams are added and
        // removed one at a time against their own endpoints, because those
        // writes have to land immediately rather than wait for a save.
        team_ids: [],
        // Which template this started from. The settings it carries are
        // applied here in the browser and arrive as ordinary fields; the
        // BOARD cannot work that way — it is up to 81 rows written after the
        // event exists — so the server is told which template to read it
        // from.
        blueprint_id: null,
    };
}

const form = useForm(blankForm());

// ------------------------------------------------------------ type & steps

const eventTypes = computed(() => site().eventTypes ?? []);

/**
 * Planned types are listed but disabled rather than hidden — an empty gap
 * where Bingo will be tells nobody anything, and the server rejects them
 * anyway (Event::availableTypes()).
 */
const typeOptions = computed(() => eventTypes.value.map((type) => ({
    value: type.value,
    label: trans(`events.type_${type.value.toLowerCase()}`),
    description: trans(`events.type_${type.value.toLowerCase()}_desc`),
    icon: type.icon,
    disabled: !type.available,
})));

function typeCardClass(option) {
    if (option.disabled) return 'ring-default opacity-50 cursor-not-allowed';

    return form.type === option.value
        ? 'ring-primary bg-primary/5'
        : 'ring-default hover:ring-primary/50 cursor-pointer';
}

const selectedType = computed(() => eventTypes.value.find((t) => t.value === form.type));
const needsMetric = computed(() => Boolean(selectedType.value?.needsMetric));
const metricKind = computed(() => selectedType.value?.metricKind ?? null);
const metricsForKind = computed(() => site().metricsByKind?.[metricKind.value] ?? []);

/**
 * The create flow, in the order the decisions actually depend on each other.
 *
 * Teams is conditional because the question it answers — which teams play —
 * only exists once you have said the event has teams at all.
 */
const steps = computed(() => [
    // First, because it is the step that can answer several of the others.
    // Skippable in one click — see TemplateFields' "start from scratch".
    { value: 'template', slot: 'template', title: trans('blueprints.step_title'), icon: 'i-lucide-layout-template' },
    { value: 'type', slot: 'type', title: trans('admin.step_type'), icon: 'i-lucide-shapes' },
    { value: 'basics', slot: 'basics', title: trans('admin.step_basics'), icon: 'i-lucide-text' },
    { value: 'format', slot: 'format', title: trans('admin.step_format'), icon: 'i-lucide-settings-2' },
    { value: 'access', slot: 'access', title: trans('admin.step_access'), icon: 'i-lucide-lock' },
    ...(form.mode === 'TEAM'
        ? [{ value: 'teams', slot: 'teams', title: trans('admin.team_assignment'), icon: 'i-lucide-users' }]
        : []),
]);

const currentStep = ref('template');
const stepper = ref(null);
const stepIndex = computed(() => Math.max(0, steps.value.findIndex((s) => s.value === currentStep.value)));

/**
 * The edit tabs. Two of these are conditional, and both used to be
 * unconditional — which is the reported confusion exactly: an "Invite links"
 * tab sat there on an OPEN event, explaining that invites appear once the
 * event exists, on an event that already existed. It is not a placeholder for
 * the Teams tab; it is a section that applies to one access mode.
 */
const tabs = computed(() => [
    { value: 'basics', slot: 'basics', label: trans('admin.step_basics') },
    { value: 'format', slot: 'format', label: trans('admin.step_format') },
    { value: 'access', slot: 'access', label: trans('admin.step_access') },
    ...(form.access_mode === 'INVITE' ? [{ value: 'invites', slot: 'invites', label: trans('admin.invite_links') }] : []),
    ...(form.mode === 'TEAM' ? [{ value: 'teams', slot: 'teams', label: trans('admin.team_assignment') }] : []),
    // Last, because it is where you go on purpose. The tabs only render
    // while editing, and only somebody who may edit the event gets this far
    // — so pausing and ending are available to everyone who can see this
    // tab, while deleting is gated inside it (the owner's alone, per
    // BoardController::destroy).
    //
    // Called "Status", not "Stop", since it gained the one thing a list of
    // stop buttons could not tell you: where the event actually is right
    // now. Upcoming, live, paused since Tuesday, ended on the 14th, or
    // closed because somebody won — read it first, then change it.
    { value: 'danger', slot: 'danger', label: trans('admin.step_status') },
]);

const activeTab = ref('basics');

// Back to the first tab whenever the dialog is opened, rather than whenever
// the board prop changes — see the note in the watch on `props.board`.
watch(() => props.open, (open) => {
    if (! open) return;

    // The re-seed the board watch no longer does while the dialog is open.
    // Without it an edit that was abandoned — typed, then cancelled — would
    // still be sitting there the next time the dialog opened.
    seedFromBoard(props.board);

    activeTab.value = props.initialTab;

    // Loaded when the modal opens rather than when the step is reached: the
    // template step IS the first thing on screen, so waiting for a keystroke
    // would show an empty gallery to everyone who never types in the search
    // box — which is most people.
    if (! isEdit.value) {
        blueprintSearch.value = '';
        loadBlueprints();
    }
});

// Flattened for the summary above. useForm keeps errors as
// { field: 'message' }, and the template needs both halves — the message to
// show and the field to navigate by.
const errorList = computed(() => Object.entries(form.errors ?? {})
    .filter(([, message]) => Boolean(message))
    .map(([field, message]) => ({ field, message })));

// ------------------------------------------------------- step validation

/**
 * Checked before advancing, and written into form.errors so the message
 * lands under the field it belongs to rather than in a toast.
 *
 * Only the rules that would make the NEXT step nonsense, plus the ones the
 * server will refuse anyway. The point is not to duplicate validation — it is
 * that reaching step four and being sent back to step two is a worse way to
 * learn the title was empty.
 */
function validateStep(step) {
    form.clearErrors();

    // The template step has nothing to get wrong: picking one is optional and
    // "from scratch" is a valid answer, so it never holds anybody up.
    if (step === 'template') return true;

    if (step === 'type' && !selectedType.value?.available) {
        form.setError('type', trans('validation.event_type_required'));
    }

    if (step === 'basics') {
        if (!form.title.trim()) form.setError('title', trans('validation.title_required'));
        if (!form.start_date) form.setError('start_date', trans('validation.start_date_required'));
        if (!form.end_date) form.setError('end_date', trans('validation.end_date_required'));
        if (form.start_date && form.end_date && form.end_date < form.start_date) {
            form.setError('end_date', trans('validation.end_before_start'));
        }
    }

    if (step === 'format' && needsMetric.value && !form.metric) {
        form.setError('metric', trans('validation.metric_required'));
    }

    return !form.hasErrors;
}

function goNext() {
    if (!validateStep(currentStep.value)) return;

    stepper.value?.next();
}

function goBack() {
    form.clearErrors();
    stepper.value?.prev();
}

// A metric belongs to one kind of race. Switching type drops one that no
// longer belongs to the list on offer — left alone it would submit and be
// rejected by a rule the user cannot see.
watch(() => form.type, () => {
    if (form.metric && !metricsForKind.value.includes(form.metric)) {
        form.metric = null;
    }
});

// ----------------------------------------------------------------- guilds

const guilds = ref([]);
const loadingGuilds = ref(false);

// Whether the account has Discord at all decides which of the two empty
// states applies — "link it" and "relink it" are different problems.
const hasDiscord = computed(() => !!currentUser.value?.discordUsername);

async function loadGuilds() {
    if (guilds.value.length || loadingGuilds.value) return;

    loadingGuilds.value = true;

    try {
        const response = await fetch('/my-guilds', { headers: { Accept: 'application/json' } });
        const data = await response.json();
        guilds.value = data.guilds ?? [];
    } catch (error) {
        console.error(error);
    } finally {
        loadingGuilds.value = false;
    }
}

watch(() => form.access_mode, (mode) => {
    if (mode === 'GUILD') loadGuilds();
}, { immediate: true });

// ------------------------------------------------------------------ state
//
// Declared above the watch on `props.board`, which runs immediately (during
// setup) and reaches straight into them — a const declared further down is
// still in its temporal dead zone at that moment.

const assignedTeams = ref([]);
const availableTeams = ref([]);
const teamToAdd = ref(null);
const addingTeam = ref(false);
const loadingTeams = ref(false);

const blueprintResults = ref([]);
const blueprintSearch = ref('');
const loadingBlueprints = ref(false);
// Which template is applied, so the gallery can show the choice back. null is
// "from scratch", which is a choice too.
const appliedBlueprintId = ref(null);
let blueprintSearchTimeout = null;

const selectedAuthors = ref([]);
const authorSearch = ref('');
const authorResults = ref([]);
let authorSearchTimeout = null;

const invites = ref([]);
const openInvites = ref(0);
const maxOpenInvites = ref(null);
const creatingInvite = ref(false);

// useToast statically imports the virtual '#imports' specifier, and pulling
// it into the SSR module graph crashes the SSR process at startup for every
// page — see AppRoot.vue. Optional-called below, since a toast raised before
// hydration finishes is not worth a crash.
let toast;
onMounted(async () => {
    const { useToast } = await import('@nuxt/ui/composables/useToast');
    toast = useToast();
});

/**
 * `<input type="date">` accepts exactly one format, YYYY-MM-DD, and silently
 * renders empty for anything else. The API sends what the datetime cast
 * serialises — 2026-08-21T00:00:00.000000Z — so every saved date came back
 * looking unset, and re-saving the form then cleared it for real.
 *
 * Sliced rather than parsed through Date: the value is already the calendar
 * day we want, and round-tripping it through a timezone is how it becomes the
 * day before for anyone west of UTC.
 */
function dateFields(board) {
    const toInput = (value) => (value ? String(value).slice(0, 10) : '');

    return { start_date: toInput(board.start_date), end_date: toInput(board.end_date) };
}

/**
 * The bingo card's settings, flattened onto the one form.
 *
 * A bingo event's win condition is as much "the event's settings" as its
 * title is, and sending people to a second place for it is what made bingo
 * feel half-finished. BoardController::update routes these back to
 * BingoService::applyCardSettings, which is the same path the card's own
 * endpoint uses — including the guard that refuses to shrink a card whose
 * squares carry completions.
 */
function cardFields(board) {
    if (!board.card) return {};

    return {
        bingo_size: board.card.size,
        win_condition: board.card.winCondition,
        win_lines: board.card.winLines ?? ['ROW', 'COLUMN', 'DIAGONAL'],
        line_bonus: board.card.lineBonus ?? 0,
        requires_approval: board.card.requiresApproval ?? true,
    };
}

/**
 * Fill the form from the event being edited.
 *
 * Called when a different event is handed in and when the dialog opens —
 * deliberately NOT on every change of the `board` prop, which is a different
 * thing entirely. That prop is rebuilt on every page render: the live stream
 * pushes a fresh event card every few seconds, and a failed save comes back
 * as a re-render of the same page carrying the errors. Re-seeding on those
 * wiped whatever was half-typed.
 *
 * That is the bug behind "it saved nothing and said it did": a save that
 * failed validation reset the form to the stored values, and the second save
 * — after fixing the one field the error named — sent those stored values
 * back. The server saved them faithfully, answered success, and the dates
 * the person had picked before the error had never left the browser.
 */
function seedFromBoard(board) {
        form.defaults(board
            ? { ...blankForm(), ...board, ...dateFields(board), ...cardFields(board), discord_webhook_url: props.webhookUrl ?? '' }
            : blankForm());
        form.reset();
        form.clearErrors();

        currentStep.value = 'template';
        appliedBlueprintId.value = null;

        // NOT reset while the modal is open. A successful save updates the
        // `board` prop, which re-runs this watch — and resetting the tab
        // there made the dialog visibly snap back to Basics for a frame
        // before it closed. The tab only needs resetting for the NEXT
        // opening, which is what the watch on `open` below does.
        if (! props.open) activeTab.value = props.initialTab;

        if (board && board.access_mode === 'INVITE') fetchInvites();

        // Both modes need the pickable list; only an existing event has an
        // assigned one to read back.
        assignedTeams.value = [];
        availableTeams.value = [];
        teamToAdd.value = null;
        if ((board?.mode ?? 'SOLO') === 'TEAM') loadTeams();

        // The backend always keeps the true owner(s) as an editor regardless
        // of what is submitted here, so a new board did not technically need
        // the creator in this list — and started empty. But the field's own
        // description says "You are always included as an editor", and an
        // empty list directly under that reads as though it is not true.
        selectedAuthors.value = board
            ? board.authors.map((a) => ({ ...a.user, is_owner: a.is_owner }))
            : (currentUser.value ? [{ ...currentUser.value, is_owner: true }] : []);
        form.author_ids = selectedAuthors.value.map((a) => a.id);
        authorSearch.value = '';
        authorResults.value = [];
        blueprintResults.value = [];
}

watch(
    () => props.board,
    (board, previous) => {
        // `previous` is undefined on the immediate run, which is the one that
        // has to seed. After that, a new object for the same event while the
        // dialog is open is a re-render, not a new thing to edit.
        if (props.open && previous !== undefined && board?.id === previous?.id) return;

        seedFromBoard(board);
    },
    { immediate: true },
);

// ------------------------------------------------------------- blueprints

/**
 * Loads the gallery. Debounced the same way the author search is, and for the
 * same reason: it fires on every keystroke.
 *
 * No minimum length, unlike the author search — an empty box is exactly when
 * the list is worth the most, so opening the step with nothing typed shows
 * the formats rather than waiting for two characters nobody knows to type.
 */
async function loadBlueprints(value = '') {
    loadingBlueprints.value = true;

    try {
        const response = await fetch(`/event-blueprints?search=${encodeURIComponent(value)}`, {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) throw new Error(`blueprint lookup failed: ${response.status}`);

        blueprintResults.value = (await response.json()).blueprints ?? [];
    } catch (error) {
        // Silent: creating an event works without templates, and a toast over
        // a form someone is part-way through would be worse than an empty
        // gallery that says so.
        console.error(error);
        blueprintResults.value = [];
    } finally {
        loadingBlueprints.value = false;
    }
}

function onBlueprintSearch(value) {
    blueprintSearch.value = value ?? '';

    if (blueprintSearchTimeout) clearTimeout(blueprintSearchTimeout);
    blueprintSearchTimeout = setTimeout(() => loadBlueprints(blueprintSearch.value), 250);
}

/**
 * Fills in what the blueprint carries and nothing else.
 *
 * A title-only template sets a title and leaves a half-configured form alone,
 * which is what makes it safe to click one out of curiosity. Applied through
 * a patch object rather than field by field so the rule lives in
 * Support/blueprint.js, where it is testable.
 */
function applyBlueprint(blueprint) {
    const patch = blueprintPatch(blueprint);

    // Type first. The watch on form.type drops a metric that is not on the
    // new type's list, and it runs after both have landed — so in this order
    // the new metric is checked against the new type and survives. The other
    // way round it would be checked against the old one and thrown away.
    if (patch.type) form.type = patch.type;

    for (const [key, value] of Object.entries(patch)) {
        if (key !== 'type' && key in form) form[key] = value;
    }

    appliedBlueprintId.value = blueprint.id;
    form.blueprint_id = blueprint.id;

    // Straight on to whichever question the template did not answer.
    currentStep.value = decidesType(blueprint) ? 'basics' : 'type';
}

/**
 * Whether the applied template's board no longer matches the chosen grid.
 *
 * Only while creating: an edit never applies a layout, and the warning would
 * be about something that already happened.
 */
const layoutWillBeTrimmed = computed(() => {
    if (isEdit.value || !appliedBlueprintId.value) return false;

    const applied = blueprintResults.value.find((b) => b.id === appliedBlueprintId.value);

    return !layoutFits(applied, form.size, form.bingo_size);
});

/** "From scratch" is a choice, so it clears one rather than doing nothing. */
function skipBlueprint() {
    appliedBlueprintId.value = null;
    form.blueprint_id = null;
    currentStep.value = 'type';
}

// ---------------------------------------------------------------- authors

function onAuthorSearch(value) {
    authorSearch.value = value;

    if (authorSearchTimeout) clearTimeout(authorSearchTimeout);

    if (value.length < 2) {
        authorResults.value = [];

        return;
    }

    authorSearchTimeout = setTimeout(async () => {
        const response = await fetch(`/users/search?search=${encodeURIComponent(value)}`, {
            headers: { Accept: 'application/json' },
        });
        const results = response.ok ? await response.json() : [];
        authorResults.value = (Array.isArray(results) ? results : []).filter(
            (u) => !selectedAuthors.value.some((a) => a.id === u.id),
        );
    }, 300);
}

function addAuthor(candidate) {
    if (!selectedAuthors.value.some((a) => a.id === candidate.id)) {
        selectedAuthors.value = [...selectedAuthors.value, candidate];
        form.author_ids = selectedAuthors.value.map((a) => a.id);
    }
    authorResults.value = [];
    authorSearch.value = '';
}

function removeAuthor(userId) {
    selectedAuthors.value = selectedAuthors.value.filter((a) => a.id !== userId);
    form.author_ids = selectedAuthors.value.map((a) => a.id);
}

// ------------------------------------------------------------ danger zone

const pausing = ref(false);
const closing = ref(false);
const deleting = ref(false);

/**
 * Deleting is the owner's, pausing is any host's.
 *
 * The admin path is its own answer: /admin/events exists precisely so a site
 * admin can act on an event they did not author, and the routes behind it
 * assert that themselves.
 */
const canDelete = computed(() => props.basePath.startsWith('/admin')
    || (props.board?.authors ?? []).some((author) => author.is_owner && author.user_id === currentUser.value?.id));

function setPaused({ paused, notify, reason }) {
    pausing.value = true;

    router.patch(`${eventPath.value}/pause`, { paused, notify, reason }, {
        preserveScroll: true,
        // Stays open. A host pausing to sort something out is usually about
        // to change something else in here, and the tab reflects the new
        // state on its own — the page's props come back with the visit.
        onError: (errors) => console.error(errors),
        onFinish: () => { pausing.value = false; },
    });
}

/**
 * Calling it, or taking that back.
 *
 * Named `setClosed`, and the event it answers is `close-event` rather than
 * `close`: a Vue component that emits `close` is saying "shut me", and this
 * one means the opposite kind of closing — a modal that dismissed itself
 * every time a host ended an event would be a genuinely confusing bug to
 * chase.
 */
function setClosed({ closed, notify }) {
    closing.value = true;

    router.patch(`${eventPath.value}/close`, { closed, notify }, {
        preserveScroll: true,
        // Stays open, same as pausing: the panel re-reads the new state from
        // the props that come back with the visit, and a host who has just
        // reopened an event usually wants to move its end date next.
        onError: (errors) => console.error(errors),
        onFinish: () => { closing.value = false; },
    });
}

/**
 * The modal does not close itself: the server redirects to the events list,
 * so the page this is mounted on stops existing.
 */
function destroyEvent({ notify }) {
    deleting.value = true;

    router.delete(eventPath.value, {
        data: { notify },
        onError: (errors) => console.error(errors),
        onFinish: () => { deleting.value = false; },
    });
}

// ----------------------------------------------------------------- submit

function submit() {
    if (!isEdit.value) {
        // The step on screen is not necessarily the one with the problem, so
        // every step is re-checked before the request — and the first that
        // fails is the one we jump back to.
        for (const step of steps.value) {
            if (!validateStep(step.value)) {
                currentStep.value = step.value;

                return;
            }
        }

        // Same type-scoping as the edit path below — a bingo event has no
        // board size and a Snakes & Ladders event has no card.
        form.transform((data) => {
            const payload = { ...data };

            if (data.type !== 'BINGO') {
                delete payload.bingo_size;
                delete payload.win_condition;
                delete payload.win_lines;
                delete payload.line_bonus;
            }

            if (data.type !== 'SNAKES_LADDERS') {
                delete payload.size;
                delete payload.dice_roll_limit;
            }

            // Claim approval is the one card/board setting both a bingo
            // card and an S&L board actually have — only a race type has
            // nothing for it to gate.
            if (data.type !== 'BINGO' && data.type !== 'SNAKES_LADDERS') {
                delete payload.requires_approval;
            }

            if (data.mode !== 'TEAM') delete payload.team_ids;

            return payload;
        }).post('/events', { onSuccess: () => (isOpen.value = false) });

        return;
    }

    // Only the fields this event type actually has.
    //
    // The form carries every type's settings at once (one useForm, five
    // sections), so an edit on a Snakes & Ladders event was posting
    // bingo_size: null — present, therefore validated, therefore
    // "The card size field must be an integer" on an event with no card.
    // That failed EVERY non-bingo save, and the message named a field the
    // form does not even show for that type.
    form.transform((data) => {
        const payload = { ...data };

        // team_ids is a create-only staging field; on edit the teams are
        // written one at a time against their own endpoints.
        delete payload.team_ids;

        if (data.type !== 'BINGO') {
            delete payload.bingo_size;
            delete payload.win_condition;
            delete payload.win_lines;
            delete payload.line_bonus;
        }

        if (data.type !== 'SNAKES_LADDERS') {
            delete payload.size;
            delete payload.dice_roll_limit;
        }

        // Same rule as the create path: claim approval is the one setting
        // both a bingo card and an S&L board have, so only a race type has
        // nothing for it to gate. Dropping it for everything non-bingo meant
        // an S&L host could flip the toggle off, save, and have the field
        // never leave the browser — the board stayed on approval and the
        // reopened modal read the unchanged value back as "on".
        if (data.type !== 'BINGO' && data.type !== 'SNAKES_LADDERS') {
            delete payload.requires_approval;
        }

        return payload;
    }).patch(eventPath.value, {
        preserveScroll: true,
        onSuccess: () => (isOpen.value = false),
        // Reported as "I hit save, it jumped back to Basics and saved
        // nothing". It was a 422 the user could not see: the offending field
        // (a GUILD event with no server picked — legal before that rule
        // existed) lives on the Access tab, and they were on Teams. A form
        // that fails silently on a tab you are not looking at is
        // indistinguishable from one that is broken.
        onError: (errors) => showTabFor(errors),
    });
}

/**
 * The tab holding a given field, so a validation error can bring its own
 * section forward instead of waiting to be found.
 */
const FIELD_TABS = {
    title: 'basics',
    description: 'basics',
    start_date: 'basics',
    end_date: 'basics',
    type: 'format',
    metric: 'format',
    mode: 'format',
    size: 'format',
    bingo_size: 'format',
    win_condition: 'format',
    line_bonus: 'format',
    requires_approval: 'format',
    dice_roll_limit: 'format',
    access_mode: 'access',
    required_guild_id: 'access',
    is_listed: 'access',
    author_ids: 'access',
    team_ids: 'teams',
};

function showTabFor(errors) {
    const field = Object.keys(errors ?? {})[0];
    const tab = FIELD_TABS[field];

    // Only move if the target tab is actually on offer — the Teams tab does
    // not exist on a solo event, and jumping to a tab that is not rendered
    // would leave the modal on a blank panel.
    if (tab && tabs.value.some((t) => t.value === tab)) {
        activeTab.value = tab;
    }
}

// ----------------------------------------------------------------- invites
//
// Fetched/created/revoked via plain fetch() rather than Inertia's router — an
// Inertia visit would re-render the whole underlying board page and, since
// this modal isn't itself a page component, has no natural way to just
// refresh its own invites list without closing.
//
// No <meta name="csrf-token"> exists in app.blade.php, so the XSRF-TOKEN
// cookie is read instead — the same encrypted-cookie mechanism
// VerifyCsrfToken accepts, and what Inertia's own client uses under the hood.

function xsrfHeader() {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);

    return match ? { 'X-XSRF-TOKEN': decodeURIComponent(match[1]) } : {};
}

/**
 * All three endpoints answer with the same shape — the full list plus the
 * open count — so creating and revoking need no separate refetch and the list
 * can never drift from what the server just did.
 */
function applyInvites(data) {
    invites.value = Array.isArray(data?.invites) ? data.invites : [];
    openInvites.value = data?.openCount ?? invites.value.length;
    maxOpenInvites.value = data?.maxOpen ?? null;
}

async function inviteRequest(url, options = {}) {
    let response;

    try {
        response = await fetch(url, {
            headers: { Accept: 'application/json', ...xsrfHeader(), ...(options.headers ?? {}) },
            ...options,
        });
    } catch (error) {
        // The network never answered — a dropped connection or a server that
        // is not there. Worth saying, because it is the one case where
        // trying again really is the advice.
        console.error('invite request could not be sent', url, error);
        toast?.add({ id: 'invite-error', title: trans('errors.network'), color: 'error' });

        return null;
    }

    const body = await response.text();
    let data = null;

    try {
        data = JSON.parse(body);
    } catch {
        data = null;
    }

    if (!response.ok) {
        // Everything the next person needs to work out what happened. This
        // used to be one generic "something went wrong" for every failure —
        // reported as "invite links do not work", with nothing to go on and
        // no way to tell a stale session from a permission problem.
        console.error('invite request failed', { url, status: response.status, body: body.slice(0, 500) });

        toast?.add({ id: 'invite-error', title: inviteError(response, data), color: 'error' });

        return null;
    }

    applyInvites(data);

    return data;
}

/**
 * What actually went wrong, said out loud.
 *
 * `data.message` is empty on an `abort_unless`, and absent entirely when the
 * server answered with HTML — a 419 page, a redirect to the lock screen, a
 * 500. All three used to arrive as the same shrug.
 */
function inviteError(response, data) {
    if (data?.message) return data.message;

    if (response.status === 419) return trans('errors.session_expired');
    if (response.status === 403) return trans('errors.forbidden');

    return trans('errors.generic_with_status', { status: response.status });
}

async function fetchInvites() {
    await inviteRequest(`${eventPath.value}/invites`);
}

async function createInvite() {
    creatingInvite.value = true;

    try {
        const data = await inviteRequest(`${eventPath.value}/invites`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({}),
        });

        if (data) toast?.add({ id: 'invite-created', title: trans('admin.invite_created'), color: 'success' });
    } finally {
        // In a finally so a failure cannot leave the button spinning forever,
        // which is what a spam-click session turns into otherwise.
        creatingInvite.value = false;
    }
}

async function revokeInvite(invite) {
    const data = await inviteRequest(`${eventPath.value}/invites/${invite.id}`, { method: 'DELETE' });

    if (data) toast?.add({ id: 'invite-revoked', title: trans('admin.invite_revoked'), color: 'success' });
}

// The invites tab only exists on an INVITE event, so switching an existing
// event to that mode mid-edit is what has to trigger the first fetch.
watch(() => form.access_mode, (mode) => {
    if (isEdit.value && mode === 'INVITE' && !invites.value.length) fetchInvites();
});

// ------------------------------------------------------------------- teams
//
// Two sources, one shape. Editing reads the event's own split of
// assigned/available; creating has no event to ask, so it reads the teams
// this account may use and stages the picks locally until save.

async function loadTeams() {
    loadingTeams.value = true;

    try {
        const url = isEdit.value ? `${eventPath.value}/teams` : '/teams/options';
        const response = await fetch(url, { headers: { Accept: 'application/json' } });

        if (!response.ok) throw new Error(`teams request failed: ${response.status}`);

        const data = await response.json();

        if (isEdit.value) {
            assignedTeams.value = data.assigned ?? [];
            availableTeams.value = data.available ?? [];
        } else {
            // A staged pick survives a step change but not a reopen, which is
            // the same lifetime the rest of the form has.
            const staged = new Set(form.team_ids);
            const teams = data.teams ?? [];
            assignedTeams.value = teams.filter((t) => staged.has(t.id));
            availableTeams.value = teams.filter((t) => !staged.has(t.id));
        }
    } catch (error) {
        // Not toasted: the step shows its own empty state, and this fails
        // while someone is filling in a form they can still submit.
        console.error(error);
    } finally {
        loadingTeams.value = false;
    }
}

// Covers switching to TEAM mid-form, in both modes — the watch on `board`
// above only fires when the modal is opened on one that already was.
watch(() => form.mode, (mode) => {
    if (mode === 'TEAM' && !assignedTeams.value.length && !availableTeams.value.length) loadTeams();
});

async function addTeam() {
    if (!teamToAdd.value) return;

    const team = availableTeams.value.find((t) => t.id === teamToAdd.value);
    if (!team) return;

    // Create: staged on the form, written by BoardController::store in the
    // same transaction as the event itself.
    if (!isEdit.value) {
        assignedTeams.value = [...assignedTeams.value, team];
        availableTeams.value = availableTeams.value.filter((t) => t.id !== team.id);
        form.team_ids = assignedTeams.value.map((t) => t.id);
        teamToAdd.value = null;

        return;
    }

    addingTeam.value = true;

    try {
        const response = await fetch(`${eventPath.value}/teams`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', ...xsrfHeader() },
            body: JSON.stringify({ team_id: teamToAdd.value }),
        });

        if (!response.ok) throw new Error(`add team failed: ${response.status}`);

        teamToAdd.value = null;
        await loadTeams();
    } catch (error) {
        console.error(error);
        toast?.add({ id: 'board-team-error', title: trans('errors.generic'), color: 'error' });
    } finally {
        addingTeam.value = false;
    }
}

async function removeTeam(team) {
    if (!isEdit.value) {
        assignedTeams.value = assignedTeams.value.filter((t) => t.id !== team.id);
        availableTeams.value = [...availableTeams.value, team].sort((a, b) => a.name.localeCompare(b.name));
        form.team_ids = assignedTeams.value.map((t) => t.id);

        return;
    }

    try {
        const response = await fetch(`${eventPath.value}/teams/${team.id}`, {
            method: 'DELETE',
            headers: { Accept: 'application/json', ...xsrfHeader() },
        });

        if (!response.ok) throw new Error(`remove team failed: ${response.status}`);

        await loadTeams();
    } catch (error) {
        console.error(error);
        toast?.add({ id: 'board-team-error', title: trans('errors.generic'), color: 'error' });
    }
}
</script>
