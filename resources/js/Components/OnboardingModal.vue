<template>
    <u-modal v-model:open="isOpen" :title="$t('onboarding.title')" :ui="{ content: 'max-w-4xl' }">
        <template #body>
            <u-stepper :items="steps" :model-value="stepIndex" class="mb-6" disabled />

            <!-- Two columns: the step's own content on the left, a live
                 preview on the right that reacts to whatever the form holds
                 right now. Not a canned animation — it renders the same
                 board layout the real page does, so what's previewed is
                 what gets built (see BoardPreview.vue). -->
            <!-- min-h only from md up: it exists to stop the modal resizing
                 as you step through it, but the preview column it's sized
                 for is hidden on mobile, so there it just adds dead space. -->
            <div class="grid md:grid-cols-2 gap-6 items-start md:min-h-[22rem]">
                <div class="space-y-4">
                    <template v-if="step === 'welcome'">
                        <h3 class="text-lg font-semibold text-highlighted">{{ $t('onboarding.welcome_heading', { name: displayName }) }}</h3>
                        <p class="text-sm text-muted leading-relaxed">{{ $t('onboarding.welcome_body') }}</p>
                        <p class="text-sm text-muted leading-relaxed">{{ $t(canCreateBoards ? 'onboarding.welcome_why_host' : 'onboarding.welcome_why') }}</p>

                        <div class="space-y-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted">{{ $t('onboarding.your_access') }}</p>
                            <div class="flex flex-wrap gap-2">
                                <u-badge v-for="role in roles" :key="role" :label="role" :color="roleColor(role)" variant="subtle" />
                                <u-badge v-if="!roles.length" label="PLAYER" color="primary" variant="subtle" />
                            </div>
                            <ul class="text-sm text-muted space-y-1 mt-2">
                                <li class="flex items-start gap-2">
                                    <u-icon name="i-lucide-check" class="size-4 text-success mt-0.5 shrink-0" />
                                    {{ $t('onboarding.perm_play') }}
                                </li>
                                <li v-if="canCreateBoards" class="flex items-start gap-2">
                                    <u-icon name="i-lucide-check" class="size-4 text-success mt-0.5 shrink-0" />
                                    {{ $t('onboarding.perm_create_yes') }}
                                </li>
                            </ul>
                        </div>
                    </template>

                    <!-- Asked here, in the tour, rather than only by the
                         standalone gate. Finishing the wizard and being met
                         immediately by a page demanding this was the worst
                         possible moment for it: the one screen that reads as
                         "you did the setup wrong" arriving directly after
                         the setup. The gate still exists for anyone who
                         skips the tour, and for mutations. -->
                    <template v-else-if="step === 'osrs'">
                        <h3 class="text-lg font-semibold text-highlighted">{{ $t('onboarding.osrs_heading') }}</h3>
                        <p class="text-sm text-muted leading-relaxed">{{ $t('onboarding.osrs_body') }}</p>

                        <u-form-field
                            :label="$t('auth.field_osrs_username')"
                            :description="$t('auth.field_osrs_username_desc')"
                            :error="osrsForm.errors.osrs_username"
                            required
                        >
                            <u-input v-model="osrsForm.osrs_username" maxlength="12" icon="i-lucide-user-round" class="w-full" />
                        </u-form-field>

                        <p class="text-xs text-muted">{{ $t('auth.osrs_change_later') }}</p>
                    </template>

                    <!-- Only reached when something's actually missing (see
                         steps computed) — never shown to an account that
                         already has both a Discord link and an email. -->
                    <template v-else-if="step === 'connect'">
                        <h3 class="text-lg font-semibold text-highlighted">{{ $t('onboarding.connect_heading') }}</h3>

                        <div v-if="!hasDiscord" class="rounded-lg border border-default p-4 space-y-2">
                            <div class="flex items-center gap-2">
                                <u-icon name="i-simple-icons-discord" class="size-4 text-primary" />
                                <p class="font-medium text-sm">{{ $t('onboarding.connect_discord_title') }}</p>
                            </div>
                            <p class="text-sm text-muted leading-relaxed">{{ $t('onboarding.connect_discord_body') }}</p>
                            <u-button :href="route('settings.discord.connect')" color="primary" variant="outline" size="sm" icon="i-simple-icons-discord" :label="$t('profile.connect_discord')" />
                        </div>

                        <div v-if="!hasEmail" class="rounded-lg border border-default p-4 space-y-2">
                            <div class="flex items-center gap-2">
                                <u-icon name="i-lucide-mail" class="size-4 text-primary" />
                                <p class="font-medium text-sm">{{ $t('onboarding.connect_email_title') }}</p>
                            </div>
                            <p class="text-sm text-muted leading-relaxed">{{ $t('onboarding.connect_email_body') }}</p>
                            <u-form-field :label="$t('auth.field_email')" :error="emailForm.errors.email">
                                <u-input
                                    v-model="emailForm.email"
                                    type="email"
                                    autocomplete="email"
                                    icon="i-lucide-mail"
                                    :placeholder="$t('onboarding.connect_email_placeholder')"
                                    class="w-full"
                                    @keydown.enter.prevent="next"
                                />
                            </u-form-field>
                        </div>

                        <p class="text-xs text-muted italic">{{ $t(missingBoth ? 'onboarding.connect_optional' : 'onboarding.connect_optional_one') }}</p>
                    </template>

                    <template v-else-if="step === 'board'">
                        <h3 class="text-lg font-semibold text-highlighted">{{ $t('onboarding.board_heading') }}</h3>
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

                    <!-- The counterpart to 'board' for accounts that can't
                         create one. The old flow showed them the create step
                         with a "you're not allowed" notice, which is a dead
                         end — this offers the thing they CAN do instead. -->
                    <template v-else-if="step === 'join'">
                        <h3 class="text-lg font-semibold text-highlighted">{{ $t('onboarding.join_heading') }}</h3>
                        <p class="text-sm text-muted">{{ $t('onboarding.join_body') }}</p>

                        <div v-if="loadingBoards" class="space-y-2">
                            <u-skeleton v-for="i in 3" :key="i" class="h-14 rounded-lg" />
                        </div>

                        <div v-else-if="joinableBoards.length" class="space-y-2">
                            <!-- Kept a real link (middle-click, right-click,
                                 focus order) but handled in JS: opening an
                                 event IS finishing the tour, and without the
                                 complete post it reopened on arrival. -->
                            <a
                                v-for="board in joinableBoards"
                                :key="board.id"
                                :href="`/events/${board.id}`"
                                class="flex items-center justify-between gap-3 rounded-lg border border-default p-3 hover:border-primary transition-colors"
                                @click.prevent="finish(`/events/${board.id}`)"
                            >
                                <div class="min-w-0">
                                    <p class="font-medium text-sm truncate">{{ board.title }}</p>
                                    <p class="text-xs text-muted"><template v-if="board.size">{{ formatBoardSize(board.size) }} · </template>{{ board.mode === 'TEAM' ? $t('board.mode_team') : $t('board.mode_solo') }}</p>
                                </div>
                                <u-icon name="i-lucide-arrow-right" class="size-4 text-muted shrink-0" />
                            </a>
                        </div>

                        <u-alert
                            v-else-if="boardsFailed"
                            color="warning"
                            variant="subtle"
                            icon="i-lucide-triangle-alert"
                            :title="$t('onboarding.join_failed_title')"
                            :description="$t('onboarding.join_failed_body')"
                        />

                        <u-alert
                            v-else
                            color="neutral"
                            variant="subtle"
                            icon="i-lucide-search-x"
                            :title="$t('onboarding.join_empty_title')"
                            :description="$t('onboarding.join_empty_desc')"
                        />

                        <p class="text-xs text-muted">{{ $t('onboarding.join_invite_hint') }}</p>
                    </template>

                    <template v-else-if="step === 'runelite'">
                        <div class="flex items-center gap-2">
                            <h3 class="text-lg font-semibold text-highlighted">{{ $t('onboarding.runelite_heading') }}</h3>
                            <u-badge v-if="pluginMode === 'testing'" :label="$t('plugin.testing_badge')" color="warning" variant="subtle" size="sm" icon="i-lucide-flask-conical" />
                        </div>
                        <p class="text-sm text-muted leading-relaxed">{{ $t('onboarding.runelite_body') }}</p>

                        <u-alert
                            v-if="pluginMode === 'testing'"
                            color="warning"
                            variant="subtle"
                            icon="i-lucide-flask-conical"
                            :description="$t('plugin.testing_desc')"
                        />

                        <ul class="text-sm text-muted space-y-2">
                            <li v-for="(line, i) in runeliteSteps" :key="i" class="flex items-start gap-2">
                                <span class="size-5 rounded-full bg-elevated text-xs flex items-center justify-center shrink-0 mt-0.5">{{ i + 1 }}</span>
                                {{ line }}
                            </li>
                        </ul>

                        <div v-if="pluginCode" class="rounded-lg border border-success/40 bg-success/5 p-3 space-y-2">
                            <p class="text-sm font-medium">{{ $t('plugin.new_code_title') }}</p>
                            <div class="flex items-center gap-2 flex-wrap">
                                <code class="flex-1 min-w-0 break-all rounded-md bg-default px-3 py-2 text-sm font-mono select-all">{{ pluginCode }}</code>
                                <u-button
                                    color="success"
                                    icon="i-lucide-copy"
                                    :label="codeCopied ? $t('plugin.copied') : $t('plugin.copy')"
                                    @click="copyPluginCode"
                                />
                            </div>
                            <p class="text-xs text-muted">{{ $t('plugin.new_code_desc') }}</p>
                        </div>

                        <div v-else-if="hasPluginCode" class="space-y-2">
                            <div class="flex items-start gap-2 text-sm">
                                <u-icon name="i-lucide-check-circle-2" class="size-4 text-success mt-0.5 shrink-0" />
                                <span class="text-muted">{{ $t('onboarding.runelite_code_exists') }}</span>
                            </div>

                            <u-button
                                v-if="!confirmingReplace"
                                color="neutral"
                                variant="outline"
                                icon="i-lucide-refresh-cw"
                                :label="$t('plugin.replace_code')"
                                @click="confirmingReplace = true"
                            />
                            <div v-else class="rounded-lg ring ring-default px-3 py-2 space-y-2">
                                <p class="text-sm">{{ $t('plugin.replace_warning') }}</p>
                                <div class="flex items-center gap-2">
                                    <u-button color="neutral" variant="ghost" size="sm" :label="$t('common.cancel')" @click="confirmingReplace = false" />
                                    <u-button color="primary" size="sm" :label="$t('plugin.replace_code')" :loading="creatingCode" @click="createPluginCode" />
                                </div>
                            </div>
                        </div>

                        <div v-else class="space-y-1.5">
                            <u-button
                                color="primary"
                                icon="i-lucide-key-round"
                                :label="$t('plugin.create_code')"
                                :loading="creatingCode"
                                @click="createPluginCode"
                            />
                            <p class="text-xs text-muted">{{ $t('onboarding.runelite_code_later') }}</p>
                        </div>

                        <u-alert
                            v-if="needsProof"
                            color="warning"
                            variant="subtle"
                            icon="i-lucide-shield-alert"
                            :description="$t('plugin.status_unproven', { name: osrsUsername })"
                        />
                    </template>
                </div>

                <div class="hidden md:block">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted mb-2">{{ previewLabel }}</p>

                    <board-preview v-if="step === 'welcome' || step === 'board'" :size="form.size" :mode="form.mode" />

                    <div v-else-if="step === 'runelite'" class="space-y-3">
                        <!-- Where to look, then where to paste: the hub listing
                             until the account has a code, the plugin's own
                             settings once it does. -->
                        <figure v-if="pluginCode || hasPluginCode" class="space-y-1.5">
                            <img
                                src="/images/guides/plugin-settings.png"
                                :alt="$t('onboarding.runelite_settings_alt')"
                                width="246"
                                height="433"
                                class="max-w-full h-auto rounded-lg border border-default"
                            >
                            <figcaption class="text-xs text-muted">{{ $t('onboarding.runelite_settings_caption') }}</figcaption>
                        </figure>
                        <figure v-else class="space-y-1.5">
                            <img
                                src="/images/guides/plugin-hub-search.png"
                                :alt="$t('onboarding.runelite_hub_alt')"
                                width="276"
                                height="252"
                                class="max-w-full h-auto rounded-lg border border-default"
                            >
                            <figcaption class="text-xs text-muted">{{ $t('onboarding.runelite_hub_caption') }}</figcaption>
                        </figure>

                        <div class="rounded-xl border border-default bg-elevated/50 p-4">
                            <ul class="space-y-2 text-sm">
                                <li v-for="row in pluginSummary" :key="row.label" class="flex items-start gap-2">
                                    <u-icon :name="row.ok ? 'i-lucide-check-circle-2' : 'i-lucide-circle-dashed'" class="size-4 mt-0.5 shrink-0" :class="row.ok ? 'text-success' : 'text-muted'" />
                                    <span :class="row.ok ? '' : 'text-muted'">{{ row.label }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- connect / join: what the account unlocks, rather than
                         a board preview that has nothing to do with the step. -->
                    <div v-else class="rounded-xl border border-default bg-elevated/50 p-4 space-y-3">
                        <p class="text-sm font-medium">{{ $t('onboarding.access_summary_title') }}</p>
                        <ul class="space-y-2 text-sm">
                            <li v-for="row in accessSummary" :key="row.label" class="flex items-start gap-2">
                                <u-icon :name="row.ok ? 'i-lucide-check-circle-2' : 'i-lucide-circle-dashed'" class="size-4 mt-0.5 shrink-0" :class="row.ok ? 'text-success' : 'text-muted'" />
                                <span :class="row.ok ? '' : 'text-muted'">{{ row.label }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <div class="flex items-center justify-between w-full gap-2">
                <u-button color="neutral" variant="ghost" :label="$t('onboarding.skip')" @click="finish()" />

                <div class="flex gap-2">
                    <u-button v-if="stepIndex > 0" color="neutral" variant="outline" :label="$t('common.back')" @click="stepIndex--" />
                    <u-button v-if="!isLastStep" color="primary" :loading="form.processing || osrsForm.processing || emailForm.processing" :disabled="nextDisabled" :label="nextLabel" @click="next" />
                    <u-button v-else color="primary" :label="$t('onboarding.finish')" @click="finish()" />
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
import BoardPreview from '@/Components/BoardPreview.vue';
import { BOARD_SIZE_LABEL, BOARD_TILE_COUNT, formatBoardSize } from '@/Support/board';

const props = defineProps({
    open: { type: Boolean, default: false },
});

const emit = defineEmits(['update:open']);

const isOpen = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value),
});

const { user, canCreateBoards } = useAuth();
const page = usePage();

const displayName = computed(() => user.value?.nickname ?? user.value?.discordUsername ?? '');
const roles = computed(() => user.value?.roles ?? []);
const hasDiscord = computed(() => !!user.value?.discordUsername);
const hasEmail = computed(() => !!page.props?.auth?.user?.hasEmail);

// This step renders a card per MISSING method, so it is reached with either
// one or two of them showing. The copy underneath said "Both are optional"
// in each case — wrong, and confusingly so right after signup, where it
// reads as though it might be excusing the OSRS username too. That one is
// required and is asked for elsewhere: on the register form directly, and
// behind the RequireOsrsUsername gate for Discord logins.
const missingBoth = computed(() => !hasDiscord.value && !hasEmail.value);

const osrsUsername = computed(() => page.props?.auth?.user?.osrsUsername ?? null);

const pluginMode = computed(() => page.props?.site?.runelitePluginMode ?? 'off');
const hasPluginCode = computed(() => !!page.props?.auth?.user?.hasPluginCode);
const needsProof = computed(() => !!page.props?.auth?.user?.needsOsrsProof);

// `stay` is what stops the controller redirecting to /events on success,
// which would navigate the page out from under this modal and end the tour
// on its second step.
const osrsForm = useForm({ osrs_username: '', stay: true });

const emailForm = useForm({ email: '' });

const ROLE_COLORS = { ADMIN: 'error', EDITOR: 'warning', PLAYER: 'primary' };
const roleColor = (name) => ROLE_COLORS[name] ?? 'neutral';

/**
 * Steps are assembled per account rather than fixed, because the same three
 * screens don't make sense for everyone:
 *  - 'connect' only when something's actually missing. Without a Discord
 *    link a user can never join a GUILD board or see a guild team at all
 *    (UserGuild rows come only from Discord's sync), which is invisible
 *    otherwise; without an email they can't recover the account.
 *  - 'board' vs 'join' — creating one needs canCreateBoards. The previous
 *    version always showed 'board' and told users without the permission
 *    that they weren't allowed, which is a dead end in the middle of a
 *    first-run flow. They now get the thing they CAN do instead.
 */
const stepDefs = computed(() => {
    const defs = [{ key: 'welcome', title: trans('onboarding.step_welcome'), icon: 'i-lucide-hand' }];

    // Before the optional ones: this is the only field in the whole flow
    // that anything actually depends on — a race scores nothing without it.
    if (!osrsUsername.value) {
        defs.push({ key: 'osrs', title: trans('onboarding.step_osrs'), icon: 'i-lucide-user-round' });
    }

    if (!hasDiscord.value || !hasEmail.value) {
        defs.push({ key: 'connect', title: trans('onboarding.step_connect'), icon: 'i-lucide-link' });
    }

    // 'join' goes last, after the plugin step. Picking an event from it
    // navigates away and ends the tour, so anything placed after it was
    // never reached — you left on step three and came back to a modal
    // starting again from step one. 'board' has no such exit and keeps its
    // place before the plugin.
    // Left out of the list while the plugin is off, not hidden: the list
    // shrinks, so nothing may advance stepIndex assuming the step is there.
    const pluginStep = pluginMode.value === 'off'
        ? []
        : [{ key: 'runelite', title: trans('onboarding.step_runelite'), icon: 'i-lucide-puzzle' }];

    if (canCreateBoards.value) {
        defs.push({ key: 'board', title: trans('onboarding.step_board'), icon: 'i-lucide-layout-grid' }, ...pluginStep);
    } else {
        defs.push(...pluginStep, { key: 'join', title: trans('onboarding.step_join'), icon: 'i-lucide-compass' });
    }

    return defs;
});

const steps = computed(() => stepDefs.value.map(({ title, icon }) => ({ title, icon })));

const stepIndex = ref(0);
const step = computed(() => stepDefs.value[stepIndex.value]?.key ?? 'welcome');
const isLastStep = computed(() => stepIndex.value === stepDefs.value.length - 1);

const accessSummary = computed(() => [
    { label: trans('onboarding.access_open_boards'), ok: true },
    { label: trans('onboarding.access_guild_boards'), ok: hasDiscord.value },
    { label: trans('onboarding.access_recovery'), ok: hasEmail.value },
    { label: trans('onboarding.access_create'), ok: canCreateBoards.value },
]);

const previewLabel = computed(() => {
    if (step.value === 'runelite') return trans('onboarding.preview_plugin');
    if (step.value === 'connect' || step.value === 'join' || step.value === 'osrs') return trans('onboarding.preview_access');

    return trans('onboarding.preview_board', { size: BOARD_SIZE_LABEL[form.size], tiles: BOARD_TILE_COUNT[form.size] });
});

const pluginSummary = computed(() => {
    const rows = [{ label: trans('onboarding.plugin_row_code'), ok: hasPluginCode.value || !!pluginCode.value }];

    if (pluginMode.value === 'live') {
        rows.push({ label: trans('onboarding.plugin_row_proven'), ok: !needsProof.value });
    }

    return rows;
});

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

// The plain code exists only in the response that created it, so it is kept
// here; the page prop it arrived in is gone by the next visit.
const pluginCode = ref(null);
const creatingCode = ref(false);
const confirmingReplace = ref(false);
const codeCopied = ref(false);

let toast = null;

onMounted(async () => {
    const { useToast } = await import('@nuxt/ui/composables/useToast');
    toast = useToast();
});

// The same route the settings page posts to, so the mode gate and the
// throttle apply here too. Nothing to do on the server for this step.
function createPluginCode() {
    creatingCode.value = true;

    router.post('/settings/runelite/code', {}, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: (response) => {
            pluginCode.value = response.props?.flash?.pluginCode ?? null;
            confirmingReplace.value = false;
        },
        onError: (errors) => console.error(errors),
        onFinish: () => (creatingCode.value = false),
    });
}

async function copyPluginCode() {
    try {
        await navigator.clipboard.writeText(pluginCode.value ?? '');
        codeCopied.value = true;
    } catch (error) {
        console.error(error);
        toast?.add({ id: 'plugin-code-copy', title: trans('errors.copy_failed'), color: 'error' });
    }
}

const joinableBoards = ref([]);
const loadingBoards = ref(false);
// Told apart from an empty list on purpose — see the fetch below.
const boardsFailed = ref(false);

// Fetched when the step opens rather than up front — most accounts reaching
// this modal are admins in practice and never see the join step at all.
watch(step, async (value) => {
    if (value !== 'join' || joinableBoards.value.length || loadingBoards.value) return;

    loadingBoards.value = true;
    boardsFailed.value = false;

    try {
        const response = await fetch('/onboarding/joinable-boards', { headers: { Accept: 'application/json' } });

        // A refused request is not an empty result. This used to swallow the
        // status and fall through to "no events to join" — reported while
        // fourteen open events existed, because the site lock answers this
        // endpoint with 423 for anyone who has not typed the shared
        // password. Saying "there are none" when the answer was "you may not
        // ask" sends somebody looking for a bug in the wrong place.
        if (!response.ok) throw new Error(`joinable boards refused: ${response.status}`);

        joinableBoards.value = (await response.json()).boards ?? [];
    } catch (error) {
        console.error(error);
        boardsFailed.value = true;
    } finally {
        loadingBoards.value = false;
    }
});

const nextLabel = computed(() =>
    step.value === 'board' && form.title.trim()
        ? trans('onboarding.create_and_continue')
        : trans('common.next'),
);

// The one step that cannot be walked past empty. Skipping the whole tour is
// still allowed — that hands the user to the standalone gate, which is the
// right place to be nagged, rather than letting them through with nothing.
const nextDisabled = computed(() => step.value === 'osrs' && !osrsForm.osrs_username.trim());

function next() {
    // Saved as the step is left, so the rest of the tour runs with the name
    // already set — the board step posts to a route the gate would otherwise
    // block. Advances only on success; a rejected name keeps the step open
    // with its error rather than moving on and losing it.
    if (step.value === 'osrs') {
        osrsForm.post('/welcome/osrs-username', {
            preserveScroll: true,
            preserveState: true,
            // Saving the name removes this step from the list, which already
            // moves the next one into its index; advancing again skipped it.
            onSuccess: () => {
                if (step.value === 'osrs') stepIndex.value++;
            },
            onError: (errors) => console.error(errors),
        });

        return;
    }

    // Optional: an empty field just moves on. Same trap as above — when the
    // account already has Discord, saving the address is what removes this
    // step, so the next one is already at this index by the time it succeeds.
    if (step.value === 'connect' && !hasEmail.value && emailForm.email.trim()) {
        emailForm.post('/onboarding/email', {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                if (step.value === 'connect') stepIndex.value++;
            },
            onError: (errors) => console.error(errors),
        });

        return;
    }

    // The board step optionally creates a board on its way past. An empty
    // title just moves on — this whole flow is skippable, so requiring one
    // would turn a tour into a wall.
    if (step.value === 'board' && form.title.trim()) {
        form.post('/events', {
            preserveScroll: true,
            onSuccess: () => stepIndex.value++,
        });

        return;
    }

    stepIndex.value++;
}

/**
 * Leaving the tour — by Skip, by Finish, or from any step.
 *
 * Saves a name that has been typed but not yet submitted. Only "Next" posted
 * it, so somebody who filled the field and then pressed Skip, or pressed
 * Finish from a later step, lost it without a word — and met the standalone
 * name page the next time they tried to do anything, being asked for the one
 * thing they had just given. Reported exactly that way.
 *
 * Not a validation gate: the tour stays skippable, and an empty field still
 * skips. This only stops an answer being thrown away.
 */
function finish(destination = null) {
    saveTypedName(() => saveTypedEmail(() => completeOnboarding(destination)));
}

function saveTypedName(then) {
    if (osrsUsername.value || !osrsForm.osrs_username.trim()) return then();

    osrsForm.post('/welcome/osrs-username', {
        preserveScroll: true,
        preserveState: true,
        // Complete either way. A name their hiscores do not know is still
        // saved (see OsrsIdentityService), and a validation failure is not
        // a reason to trap somebody in a tour they asked to leave.
        onFinish: then,
    });
}

// Same reasoning as the name: an address typed and then left behind by Skip
// or Finish is an answer thrown away.
function saveTypedEmail(then) {
    if (hasEmail.value || !emailForm.email.trim()) return then();

    emailForm.post('/onboarding/email', {
        preserveScroll: true,
        preserveState: true,
        onFinish: then,
    });
}

// `destination` is the event picked on the join step. Recorded first, then
// navigated to — the other way round the page changes under the request and
// the tour comes back on the event's own page.
function completeOnboarding(destination = null) {
    router.post('/onboarding/complete', {}, {
        preserveScroll: true,
        onFinish: () => {
            isOpen.value = false;

            if (destination) router.visit(destination);
        },
    });
}
</script>
