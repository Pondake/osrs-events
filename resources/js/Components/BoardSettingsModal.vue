<template>
    <u-modal v-model:open="isOpen" :title="isEdit ? 'Edit board' : 'Create board'">
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
                        <u-form-field label="Title" required>
                            <u-input v-model="form.title" class="w-full" placeholder="Winter Clan Grind" />
                        </u-form-field>

                        <u-form-field label="Description">
                            <u-textarea v-model="form.description" class="w-full" :rows="3" />
                        </u-form-field>

                        <div class="grid grid-cols-2 gap-4">
                            <u-form-field label="Board size" required>
                                <u-select v-model="form.size" :items="sizeOptions" class="w-full" />
                            </u-form-field>

                            <u-form-field label="Mode">
                                <u-select v-model="form.mode" :items="modeOptions" class="w-full" />
                            </u-form-field>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <u-form-field label="Start date">
                                <u-input v-model="form.start_date" type="date" class="w-full" />
                            </u-form-field>
                            <u-form-field label="End date">
                                <u-input v-model="form.end_date" type="date" class="w-full" />
                            </u-form-field>
                        </div>

                        <u-form-field label="Daily dice roll limit" description="Leave empty for unlimited.">
                            <u-input v-model.number="form.dice_roll_limit" type="number" min="1" class="w-full" />
                        </u-form-field>

                        <u-switch v-model="form.is_listed" label="Listed on the public boards page" />
                    </div>
                </template>

                <template #access>
                    <div class="space-y-4 py-2">
                        <u-form-field label="Who can join">
                            <u-select v-model="form.access_mode" :items="accessOptions" class="w-full" />
                        </u-form-field>

                        <u-form-field
                            v-if="form.access_mode === 'GUILD'"
                            label="Required Discord server ID"
                            description="Only members of this Discord server can join."
                        >
                            <u-input v-model="form.required_guild_id" class="w-full" />
                        </u-form-field>
                    </div>
                </template>

                <template #invites>
                    <div class="py-2">
                        <p v-if="!isEdit" class="text-sm text-muted py-8 text-center">Save the board first, then come back here to create invites.</p>
                        <p v-else-if="form.access_mode !== 'INVITE'" class="text-sm text-muted py-8 text-center">
                            Invites only apply to "Invite only" boards — set that on the Access tab first.
                        </p>
                        <div v-else class="space-y-4">
                            <div class="flex justify-end">
                                <u-button size="sm" color="primary" icon="i-lucide-plus" label="New invite" :loading="creatingInvite" @click="createInvite" />
                            </div>

                            <div class="divide-y divide-default rounded-md ring ring-default">
                                <div v-for="invite in invites" :key="invite.id" class="flex items-center justify-between gap-3 px-3 py-2">
                                    <div class="min-w-0">
                                        <div class="font-mono text-sm">{{ invite.short_code }}</div>
                                        <div class="text-xs text-muted">
                                            {{ invite.use_count }}{{ invite.max_uses ? ` / ${invite.max_uses}` : '' }} uses
                                            <span v-if="invite.expires_at"> · expires {{ new Date(invite.expires_at).toLocaleDateString() }}</span>
                                        </div>
                                    </div>
                                    <u-button icon="i-lucide-trash-2" size="xs" color="error" variant="ghost" @click="revokeInvite(invite)" />
                                </div>
                                <p v-if="!invites.length" class="px-3 py-4 text-center text-sm text-muted">No invites yet.</p>
                            </div>
                        </div>
                    </div>
                </template>
            </u-tabs>
        </template>

        <template #footer>
            <div class="flex justify-end gap-2 w-full">
                <u-button color="neutral" variant="outline" label="Cancel" @click="isOpen = false" />
                <u-button color="primary" label="Save" :loading="form.processing" @click="submit" />
            </div>
        </template>
    </u-modal>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { BOARD_SIZE_LABEL, BOARD_TILE_COUNT } from '@/Support/board';

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

// Static. A computed() version was tried first, on the theory that a new
// `items` array reference on every render was resetting u-tabs back to the
// first tab — that theory turned out to be unverified and probably wrong:
// clicking ANY tab (even the pre-existing Access one) appeared to silently
// fail during testing, static array or not, until the test itself turned
// out to be the problem — Reka UI's Tabs (underneath u-tabs) requires the
// tab trigger to actually hold DOM focus (checks document.activeElement) to
// register a click; a synthetic el.click() without el.focus() first is a
// silent no-op regardless of how `items` is computed. Kept as a static
// array anyway since content-gating inside the tabpanel (see the template's
// v-if on the invites slot) is the more normal Vue pattern either way, not
// because the reactive-array theory was confirmed.
const tabs = [
    { label: 'Basics', slot: 'basics' },
    { label: 'Access', slot: 'access' },
    { label: 'Invites', slot: 'invites' },
];

const sizeOptions = ['SIZE_5X5', 'SIZE_7X7', 'SIZE_9X9'].map((size) => ({
    label: `${BOARD_SIZE_LABEL[size]} (${BOARD_TILE_COUNT[size]} tiles)`,
    value: size,
}));

const modeOptions = [
    { label: 'Solo', value: 'SOLO' },
    { label: 'Team', value: 'TEAM' },
];

const accessOptions = [
    { label: 'Open to everyone', value: 'OPEN' },
    { label: 'Discord server members only', value: 'GUILD' },
    { label: 'Invite only', value: 'INVITE' },
];

function blankForm() {
    return {
        title: '',
        description: '',
        size: 'SIZE_7X7',
        mode: 'SOLO',
        start_date: '',
        end_date: '',
        dice_roll_limit: null,
        is_listed: true,
        access_mode: 'OPEN',
        required_guild_id: '',
    };
}

const form = useForm(blankForm());

// Re-seed the form whenever a different board is opened for editing, or the
// modal is reopened in create mode after a previous edit.
watch(
    () => props.board,
    (board) => {
        form.defaults(board ? { ...blankForm(), ...board } : blankForm());
        form.reset();
        if (board && board.access_mode === 'INVITE') fetchInvites();
    },
    { immediate: true },
);

function submit() {
    if (isEdit.value) {
        form.patch(`/boards/${props.board.id}`, { onSuccess: () => (isOpen.value = false) });
    } else {
        form.post('/boards', { onSuccess: () => (isOpen.value = false) });
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
    const response = await fetch(`/boards/${props.board.id}/invites`, { headers: { Accept: 'application/json' } });
    invites.value = await response.json();
}

async function createInvite() {
    creatingInvite.value = true;
    await fetch(`/boards/${props.board.id}/invites`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', ...xsrfHeader() },
        body: JSON.stringify({}),
    });
    await fetchInvites();
    creatingInvite.value = false;
}

async function revokeInvite(invite) {
    await fetch(`/boards/${props.board.id}/invites/${invite.id}`, {
        method: 'DELETE',
        headers: { Accept: 'application/json', ...xsrfHeader() },
    });
    await fetchInvites();
}
</script>
