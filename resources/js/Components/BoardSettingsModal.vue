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
                        <u-form-field :label="$t('events.type_label')" :description="$t('events.type_desc')" required>
                            <u-select v-model="form.type" :items="typeOptions" class="w-full">
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

                        <div class="grid grid-cols-2 gap-4">
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

                        <u-form-field :label="$t('admin.dice_roll_limit')" :description="$t('admin.dice_roll_limit_desc')">
                            <u-input v-model.number="form.dice_roll_limit" type="number" min="1" class="w-full" />
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
                            <u-input v-model="form.required_guild_id" class="w-full" />
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
        description: '',
        size: site.defaultBoardSize ?? 'SIZE_7X7',
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

const form = useForm(blankForm());

// Mirrors form.author_ids, but carrying display data (username/avatar) the
// form itself has no use for — kept in sync by addAuthor()/removeAuthor().
const selectedAuthors = ref([]);
const authorSearch = ref('');
const authorResults = ref([]);
let authorSearchTimeout = null;

// Re-seed the form whenever a different board is opened for editing, or the
// modal is reopened in create mode after a previous edit.
watch(
    () => props.board,
    (board) => {
        form.defaults(board ? { ...blankForm(), ...board } : blankForm());
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
