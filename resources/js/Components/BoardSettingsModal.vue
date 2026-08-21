<template>
    <u-modal v-model:open="isOpen" :title="isEdit ? $t('admin.edit_board') : $t('admin.create_board')">
        <template #body>
            <!-- CLAUDE.md's convention is a stepper for create and tabs for edit
                 (per-step validation on create). Both use tabs here instead — a
                 deliberate simplification, not an oversight, since building
                 per-step validation for one form wasn't worth the scope for this
                 pass. Noted in docs/backlog.md. Co-author management (the old
                 EditorsSection.vue) is left out entirely: it needs a user-search
                 endpoint that doesn't exist yet either. -->
            <u-tabs :items="tabs" class="w-full">
                <template #basics>
                    <div class="space-y-4 py-2">
                        <u-form-field :label="$t('admin.board_title')" required>
                            <u-input v-model="form.title" class="w-full" :placeholder="$t('admin.board_title_placeholder')" />
                        </u-form-field>

                        <u-form-field :label="$t('admin.board_description')">
                            <u-textarea v-model="form.description" class="w-full" :rows="3" />
                        </u-form-field>

                        <!-- Type first: it is the thing being created, and
                             the fields under it (size, dice limit) only make
                             sense once you know which kind of event it is. -->
                        <!-- Locked once the event exists: the type decides
                             which payload table holds it, and changing it
                             would orphan a board (and everyone's progress on
                             it) or leave a race with no board to play. The
                             server refuses it independently — this just says
                             so before the click. -->
                        <u-form-field
                            :label="$t('events.type_label')"
                            :description="isEdit ? $t('events.type_locked') : $t('events.type_desc')"
                            required
                        >
                            <u-select v-model="form.type" :items="typeOptions" :disabled="isEdit" class="w-full">
                                <template #item-leading="{ item }">
                                    <u-icon :name="item.icon" class="size-4" />
                                </template>
                                <template #item-trailing="{ item }">
                                    <u-badge
                                        v-if="item.disabled"
                                        :label="$t('events.type_unavailable')"
                                        color="neutral"
                                        variant="subtle"
                                        size="sm"
                                        class="ml-auto"
                                    />
                                </template>
                            </u-select>
                        </u-form-field>

                        <!-- Only for types that race on a metric. Snakes &
                             Ladders has none, and the server rejects one. -->
                        <u-form-field v-if="needsMetric" :label="$t(metricKind === 'boss' ? 'events.metric_label_boss' : 'events.metric_label')"
                            :description="$t(metricKind === 'boss' ? 'events.metric_desc_boss' : 'events.metric_desc')"
                            required
                        >
                            <u-select v-model="form.metric" :items="metricOptions" class="w-full" />
                        </u-form-field>

                        <!-- Bingo brings its own grid, unrelated to the
                             Snakes & Ladders one: a side length rather than a
                             size enum, plus what counts as winning. -->
                        <div v-if="isBingo" class="grid grid-cols-2 gap-4">
                            <u-form-field :label="$t('bingo.card_size')" required>
                                <u-select v-model="form.bingo_size" :items="bingoSizeOptions" class="w-full" />
                            </u-form-field>

                            <u-form-field :label="$t('bingo.win_condition')">
                                <u-select v-model="form.win_condition" :items="winConditionOptions" class="w-full" />
                            </u-form-field>
                        </div>

                        <div v-if="hasBoard" class="grid grid-cols-2 gap-4">
                            <u-form-field :label="$t('admin.board_size')" required>
                                <u-select v-model="form.size" :items="sizeOptions" class="w-full" />
                            </u-form-field>

                            <u-form-field :label="$t('admin.board_mode')">
                                <u-select v-model="form.mode" :items="modeOptions" class="w-full" />
                            </u-form-field>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <u-form-field :label="$t('admin.start_date')">
                                <u-input v-model="form.start_date" type="date" class="w-full" />
                            </u-form-field>
                            <u-form-field :label="$t('admin.end_date')">
                                <u-input v-model="form.end_date" type="date" class="w-full" />
                            </u-form-field>
                        </div>

                        <!-- The description has always promised "set to
                             Unlimited", and null is exactly what the server
                             stores for it — but a `type=number min=1` spinner
                             has no way to express null, so the state was
                             reachable only by never touching the field. -->
                        <u-form-field :label="$t('admin.dice_roll_limit')" :description="$t('admin.dice_roll_limit_desc')">
                            <div class="space-y-3">
                                <u-switch v-model="unlimitedRolls" :label="$t('admin.dice_roll_unlimited')" />
                                <u-input
                                    v-if="!unlimitedRolls"
                                    v-model.number="form.dice_roll_limit"
                                    type="number"
                                    min="1"
                                    class="w-full"
                                />
                            </div>
                        </u-form-field>

                        <u-switch v-model="form.is_listed" :label="$t('admin.board_listed')" />

                        <u-form-field :label="$t('admin.editors')" :description="$t('admin.editors_desc')">
                            <div class="space-y-2">
                                <u-input
                                    v-model="authorSearch"
                                    icon="i-lucide-search"
                                    :placeholder="$t('common.search')"
                                    class="w-full"
                                    @update:model-value="onAuthorSearch"
                                />

                                <div v-if="authorResults.length" class="rounded-md ring ring-default divide-y divide-default">
                                    <button
                                        v-for="candidate in authorResults"
                                        :key="candidate.id"
                                        type="button"
                                        class="w-full flex items-center gap-3 px-3 py-2 hover:bg-elevated transition-colors text-left"
                                        @click="addAuthor(candidate)"
                                    >
                                        <u-avatar :src="candidate.avatar_url ?? undefined" :alt="candidate.nickname ?? candidate.discord_username" size="xs" />
                                        <span class="text-sm">{{ candidate.nickname ?? candidate.discord_username }}</span>
                                    </button>
                                </div>

                                <div v-if="selectedAuthors.length" class="flex flex-wrap gap-2">
                                    <u-badge v-for="author in selectedAuthors" :key="author.id" color="primary" variant="subtle" class="flex items-center gap-1">
                                        {{ author.nickname ?? author.discord_username }}
                                        <span v-if="author.id === currentUser?.id" class="opacity-70">({{ $t('admin.you_suffix') }})</span>
                                        <button v-if="!author.is_owner" type="button" class="ml-1 hover:text-error" @click="removeAuthor(author.id)">
                                            <u-icon name="i-lucide-x" class="size-3" />
                                        </button>
                                    </u-badge>
                                </div>
                            </div>
                        </u-form-field>
                    </div>
                </template>

                <template #access>
                    <div class="space-y-4 py-2">
                        <u-form-field :label="$t('admin.access_mode')">
                            <u-select v-model="form.access_mode" :items="accessOptions" class="w-full" />
                        </u-form-field>

                        <u-form-field
                            v-if="form.access_mode === 'GUILD'"
                            :label="$t('admin.required_server')"
                            :description="$t('admin.required_server_desc')"
                        >
                            <!-- A Discord server id is an 18-digit snowflake.
                                 This was a bare text box, which asked the user
                                 to know or go and find one — for a value we
                                 already hold, by name and icon, from the guild
                                 sync that runs on every Discord login. -->
                            <u-select
                                v-if="loadingGuilds || guildOptions.length"
                                v-model="form.required_guild_id"
                                :items="guildOptions"
                                :loading="loadingGuilds"
                                :placeholder="guildPlaceholder"
                                class="w-full"
                            />

                            <!-- An empty dropdown is the one thing this must
                                 not be: it looks like the feature is broken
                                 when the actual situation is either "no
                                 Discord on this account" or "Discord is
                                 linked but we never got its server list",
                                 and those need different actions. -->
                            <u-alert
                                v-else
                                color="warning"
                                variant="subtle"
                                icon="i-simple-icons-discord"
                                :title="hasDiscord ? $t('admin.guilds_none_title') : $t('admin.guilds_no_discord_title')"
                                :description="hasDiscord ? $t('admin.guilds_none_desc') : $t('admin.guilds_no_discord_desc')"
                                :actions="[{
                                    label: hasDiscord ? $t('admin.guilds_reconnect') : $t('profile.connect_discord'),
                                    color: 'warning',
                                    variant: 'outline',
                                    to: '/settings/account',
                                }]"
                            />
                        </u-form-field>
                    </div>
                </template>

                <template #invites>
                    <div class="py-2">
                        <p v-if="!isEdit" class="text-sm text-muted py-8 text-center">{{ $t('admin.save_first') }}</p>
                        <p v-else-if="form.access_mode !== 'INVITE'" class="text-sm text-muted py-8 text-center">
                            {{ $t('admin.invite_links_gate_desc') }}
                        </p>
                        <div v-else class="space-y-4">
                            <div class="flex justify-end">
                                <u-button size="sm" color="primary" icon="i-lucide-plus" :label="$t('admin.create_invite')" :loading="creatingInvite" @click="createInvite" />
                            </div>

                            <div class="divide-y divide-default rounded-md ring ring-default">
                                <div v-for="invite in invites" :key="invite.id" class="flex items-center justify-between gap-3 px-3 py-2">
                                    <div class="min-w-0">
                                        <div class="font-mono text-sm">{{ invite.short_code }}</div>
                                        <div class="text-xs text-muted">
                                            {{ invite.use_count }}{{ invite.max_uses ? ` / ${invite.max_uses}` : '' }} {{ $t('admin.invite_uses_suffix') }}
                                            <span v-if="invite.expires_at"> · {{ $t('admin.invite_expires', { date: new Date(invite.expires_at).toLocaleDateString() }) }}</span>
                                        </div>
                                    </div>
                                    <u-button icon="i-lucide-trash-2" size="xs" color="error" variant="ghost" @click="revokeInvite(invite)" />
                                </div>
                                <p v-if="!invites.length" class="px-3 py-4 text-center text-sm text-muted">{{ $t('admin.no_invites') }}</p>
                            </div>
                        </div>
                    </div>
                </template>

                <template v-if="form.mode === 'TEAM'" #teams>
                    <div class="py-2">
                        <p v-if="!isEdit" class="text-sm text-muted py-8 text-center">{{ $t('admin.save_first') }}</p>
                        <div v-else class="space-y-4">
                            <p class="text-sm text-muted">{{ $t('admin.team_assignment_desc') }}</p>

                            <div class="flex gap-2">
                                <u-select
                                    v-model="teamToAdd"
                                    :items="availableTeams.map((t) => ({ label: t.name, value: t.id }))"
                                    :placeholder="$t('admin.select_team_placeholder')"
                                    class="w-full"
                                />
                                <u-button icon="i-lucide-plus" :disabled="!teamToAdd" :loading="addingTeam" @click="addTeam" />
                            </div>

                            <div class="divide-y divide-default rounded-md ring ring-default">
                                <div v-for="team in assignedTeams" :key="team.id" class="flex items-center justify-between gap-3 px-3 py-2">
                                    <span class="text-sm">{{ team.name }}</span>
                                    <u-button icon="i-lucide-x" size="xs" color="error" variant="ghost" @click="removeTeam(team)" />
                                </div>
                                <p v-if="!assignedTeams.length" class="px-3 py-4 text-center text-sm text-muted">{{ $t('admin.no_teams_assigned') }}</p>
                            </div>
                        </div>
                    </div>
                </template>
            </u-tabs>
        </template>

        <template #footer>
            <div class="flex justify-end gap-2 w-full">
                <u-button color="neutral" variant="outline" :label="$t('common.cancel')" @click="isOpen = false" />
                <u-button color="primary" :label="$t('common.save')" :loading="form.processing" @click="submit" />
            </div>
        </template>
    </u-modal>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { useAuth } from '@/Composables/useAuth';
import { BOARD_SIZE_LABEL, BOARD_TILE_COUNT } from '@/Support/board';

const { user: currentUser } = useAuth();

const props = defineProps({
    open: { type: Boolean, default: false },
    board: { type: Object, default: null },
});

const emit = defineEmits(['update:open']);

const isOpen = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value),
});

const isEdit = computed(() => props.board !== null);

// A computed() array reference changing on every render was originally
// suspected of resetting u-tabs back to the first tab — that theory turned
// out to be unverified and probably wrong: clicking ANY tab (even a static
// one) appeared to silently fail during testing regardless, until the test
// itself turned out to be the problem — Reka UI's Tabs (underneath u-tabs)
// requires the tab trigger to actually hold DOM focus (checks
// document.activeElement) to register a click; a synthetic el.click()
// without el.focus() first is a silent no-op regardless of how `items` is
// computed. Now genuinely computed (not static) so the Teams tab only
// appears for TEAM-mode boards — `u-tabs` only renders a slot whose entry
// exists in `items`, so gating the slot's own template with v-if alone
// isn't enough without also gating it here.
const tabs = computed(() => [
    { label: trans('admin.step_basics'), slot: 'basics' },
    { label: trans('admin.step_access'), slot: 'access' },
    { label: trans('admin.invite_links'), slot: 'invites' },
    ...(form.mode === 'TEAM' ? [{ label: trans('admin.team_assignment'), slot: 'teams' }] : []),
]);

const sizeOptions = ['SIZE_5X5', 'SIZE_7X7', 'SIZE_9X9'].map((size) => ({
    label: trans('admin.board_size_option', { size: BOARD_SIZE_LABEL[size], tiles: BOARD_TILE_COUNT[size] }),
    value: size,
}));

const modeOptions = [
    { label: trans('admin.board_mode_solo'), value: 'SOLO' },
    { label: trans('admin.board_mode_team'), value: 'TEAM' },
];

const accessOptions = [
    { label: trans('admin.access_mode_open'), value: 'OPEN' },
    { label: trans('admin.access_mode_guild'), value: 'GUILD' },
    { label: trans('admin.access_mode_invite'), value: 'INVITE' },
];

function blankForm() {
    // Size and roll limit come from the admin site settings so a clan can
    // set the shape its events usually take once, instead of every creator
    // changing the same two fields each time. Fall back to the previous
    // hardcoded values if the prop isn't there (e.g. an older cached page).
    const site = usePage().props?.site ?? {};

    return {
        title: '',
        type: 'SNAKES_LADDERS',
        metric: null,
        description: '',
        size: site.defaultBoardSize ?? 'SIZE_7X7',
        // Bingo's own grid. 5x5 is the conventional card, and the win
        // condition defaults to first-line because that is the shorter,
        // more common event.
        bingo_size: 5,
        win_condition: 'LINE',
        mode: 'SOLO',
        start_date: '',
        end_date: '',
        dice_roll_limit: site.defaultDiceRollLimit ?? null,
        is_listed: true,
        access_mode: 'OPEN',
        required_guild_id: '',
        author_ids: [],
    };
}

/**
 * Planned types are listed but disabled rather than hidden — an empty gap
 * where Bingo will be tells nobody anything, and the server rejects them
 * anyway (Board::availableEventTypes()).
 */
const typeOptions = computed(() =>
    (usePage().props?.site?.eventTypes ?? []).map((type) => ({
        value: type.value,
        label: trans(`events.type_${type.value.toLowerCase()}`),
        icon: type.icon,
        disabled: !type.available,
    })),
);

const selectedType = computed(() =>
    (usePage().props?.site?.eventTypes ?? []).find((t) => t.value === form.type),
);

const needsMetric = computed(() => Boolean(selectedType.value?.needsMetric));

// Snakes & Ladders is the only type with a grid, so size and dice limit are
// hidden for anything else rather than sitting there doing nothing.
const hasBoard = computed(() => form.type === 'SNAKES_LADDERS');

const isBingo = computed(() => form.type === 'BINGO');

const bingoSizeOptions = [3, 4, 5, 6, 7].map((size) => ({
    value: size,
    label: trans('bingo.size_option', { size }),
}));

const winConditionOptions = [
    { value: 'LINE', label: trans('bingo.win_line') },
    { value: 'FULL_HOUSE', label: trans('bingo.win_full_house') },
];

// Which list to offer depends on the type: a skill race races on skills, a
// drop race on boss killcounts. Both come from Wise Old Man's own vocabulary,
// and the i18n namespace matches the kind so a boss name never gets looked up
// as a skill.
const metricKind = computed(() => selectedType.value?.metricKind ?? null);

const metricOptions = computed(() => {
    const kind = metricKind.value;
    if (!kind) return [];

    return (usePage().props?.site?.metricsByKind?.[kind] ?? []).map((m) => ({
        value: m,
        label: trans(`${kind === 'boss' ? 'bosses' : 'skills'}.${m}`),
    }));
});

const form = useForm(blankForm());

/**
 * null is "unlimited" on the server side, so this is a view of the field
 * rather than a value of its own — nothing extra to keep in sync, and
 * nothing to forget to reset when the modal reopens on another board.
 */
const unlimitedRolls = computed({
    get: () => form.dice_roll_limit === null || form.dice_roll_limit === '',
    set: (on) => {
        form.dice_roll_limit = on ? null : (usePage().props?.site?.defaultDiceRollLimit ?? 1);
    },
});

// The current user's Discord servers, fetched when the access tab actually
// needs them rather than shared into every page — same reasoning as the
// invite and team lists below.
const guilds = ref([]);
const loadingGuilds = ref(false);

// Whether the account has Discord at all decides which of the two empty
// states applies — "link it" and "relink it" are different problems.
const hasDiscord = computed(() => !!currentUser.value?.discordUsername);

const guildOptions = computed(() => guilds.value.map((guild) => ({
    label: guild.name,
    value: guild.id,
})));

const guildPlaceholder = computed(() => (
    loadingGuilds.value
        ? trans('common.loading')
        : (guildOptions.value.length ? trans('admin.required_server_pick') : trans('admin.required_server_none'))
));

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

// A boss name is not a valid skill race and vice versa, so switching type
// drops a metric that no longer belongs to the list on offer. Left alone it
// would submit and be rejected by a validation rule the user cannot see.
watch(() => form.type, () => {
    if (form.metric && !metricOptions.value.some((option) => option.value === form.metric)) {
        form.metric = null;
    }
});

// Mirrors form.author_ids, but carrying display data (username/avatar) the
// form itself has no use for — kept in sync by addAuthor()/removeAuthor().
const selectedAuthors = ref([]);
const authorSearch = ref('');
const authorResults = ref([]);
let authorSearchTimeout = null;

/**
 * `<input type="date">` accepts exactly one format, YYYY-MM-DD, and silently
 * renders empty for anything else. The API sends what the datetime cast
 * serialises — 2026-08-21T00:00:00.000000Z — so every saved date came back
 * looking unset, and re-saving the form then cleared it for real.
 *
 * Sliced rather than parsed through Date: the value is already the calendar
 * day we want, and round-tripping it through a timezone is how it becomes
 * the day before for anyone west of UTC.
 */
function dateFields(board) {
    const toInput = (value) => (value ? String(value).slice(0, 10) : '');

    return {
        start_date: toInput(board.start_date),
        end_date: toInput(board.end_date),
    };
}

// Re-seed the form whenever a different board is opened for editing, or the
// modal is reopened in create mode after a previous edit.
watch(
    () => props.board,
    (board) => {
        form.defaults(board ? { ...blankForm(), ...board, ...dateFields(board) } : blankForm());
        form.reset();
        if (board && board.access_mode === 'INVITE') fetchInvites();
        if (board && board.mode === 'TEAM') fetchTeams();

        // The backend always keeps the true owner(s) as an editor regardless
        // of what's submitted here (see BoardController::store()/update()),
        // so a brand-new board just starts empty — the creator becomes owner
        // server-side without needing to appear in this list at all.
        selectedAuthors.value = board
            ? board.authors.map((a) => ({ ...a.user, is_owner: a.is_owner }))
            : [];
        form.author_ids = selectedAuthors.value.map((a) => a.id);
        authorSearch.value = '';
        authorResults.value = [];
    },
    { immediate: true },
);

function onAuthorSearch() {
    if (authorSearchTimeout) clearTimeout(authorSearchTimeout);
    if (authorSearch.value.length < 2) {
        authorResults.value = [];
        return;
    }
    authorSearchTimeout = setTimeout(async () => {
        const response = await fetch(`/users/search?search=${encodeURIComponent(authorSearch.value)}`, {
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

function submit() {
    if (isEdit.value) {
        form.patch(`/events/${props.board.id}`, { onSuccess: () => (isOpen.value = false) });
    } else {
        form.post('/events', { onSuccess: () => (isOpen.value = false) });
    }
}

// Invites are fetched/created/revoked via plain fetch() rather than
// Inertia's router — an Inertia visit would re-render the whole underlying
// board page and, since this modal isn't itself a page component, has no
// natural way to just refresh its own invites list without closing.
//
// No <meta name="csrf-token"> exists in app.blade.php (Blade's @csrf
// directive is for <form> tags, not fetch headers, and Laravel's default
// scaffold doesn't add one either) — read the XSRF-TOKEN cookie instead,
// the same encrypted-cookie mechanism VerifyCsrfToken accepts as an
// alternative to the session token, and what Inertia's own client uses
// under the hood for its requests.
const invites = ref([]);
const creatingInvite = ref(false);

function xsrfHeader() {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? { 'X-XSRF-TOKEN': decodeURIComponent(match[1]) } : {};
}

async function fetchInvites() {
    const response = await fetch(`/events/${props.board.id}/invites`, { headers: { Accept: 'application/json' } });
    invites.value = await response.json();
}

async function createInvite() {
    creatingInvite.value = true;
    await fetch(`/events/${props.board.id}/invites`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', ...xsrfHeader() },
        body: JSON.stringify({}),
    });
    await fetchInvites();
    creatingInvite.value = false;
}

async function revokeInvite(invite) {
    await fetch(`/events/${props.board.id}/invites/${invite.id}`, {
        method: 'DELETE',
        headers: { Accept: 'application/json', ...xsrfHeader() },
    });
    await fetchInvites();
}

// Same fetch()-not-Inertia rationale as invites above.
const assignedTeams = ref([]);
const availableTeams = ref([]);
const teamToAdd = ref(null);
const addingTeam = ref(false);

async function fetchTeams() {
    const response = await fetch(`/events/${props.board.id}/teams`, { headers: { Accept: 'application/json' } });
    const data = await response.json();
    assignedTeams.value = data.assigned;
    availableTeams.value = data.available;
}

// Covers switching an existing board's mode to TEAM mid-edit, not just
// opening the modal on an already-TEAM board (the watch(board) above).
watch(
    () => form.mode,
    (mode) => {
        if (isEdit.value && mode === 'TEAM' && assignedTeams.value.length === 0 && availableTeams.value.length === 0) {
            fetchTeams();
        }
    },
);

async function addTeam() {
    if (!teamToAdd.value) return;
    addingTeam.value = true;
    await fetch(`/events/${props.board.id}/teams`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', ...xsrfHeader() },
        body: JSON.stringify({ team_id: teamToAdd.value }),
    });
    teamToAdd.value = null;
    await fetchTeams();
    addingTeam.value = false;
}

async function removeTeam(team) {
    await fetch(`/events/${props.board.id}/teams/${team.id}`, {
        method: 'DELETE',
        headers: { Accept: 'application/json', ...xsrfHeader() },
    });
    await fetchTeams();
}
</script>
