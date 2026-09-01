<template>
    <Head :title="$t('settings.account_title')" />

    <settings-layout current="account">
        <u-card>
            <template #header>
                <span class="font-semibold">{{ $t('profile.email_address') }}</span>
            </template>

            <p v-if="!email" class="text-sm text-muted mb-3">{{ $t('profile.email_needed_desc') }}</p>

            <form class="max-w-sm space-y-3" @submit.prevent="submitEmail">
                <u-form-field :error="emailForm.errors.email">
                    <u-input v-model="emailForm.email" type="email" autocomplete="email" class="w-full" />
                </u-form-field>

                <!-- Once there is a password, this address is what a reset
                     link goes to — so changing it is as sensitive as changing
                     the password, and takes the same proof. A Discord login
                     has no password to give and does not see this.
                     AccountController enforces the same rule server-side. -->
                <u-form-field
                    v-if="hasPassword"
                    :label="$t('profile.current_password')"
                    :description="$t('profile.email_needs_password')"
                    :error="emailForm.errors.current_password"
                    required
                >
                    <u-input v-model="emailForm.current_password" type="password" autocomplete="current-password" class="w-full" />
                </u-form-field>

                <u-button type="submit" color="primary" size="sm" :loading="emailForm.processing" :label="$t('profile.save_email')" />
            </form>
        </u-card>

        <u-card>
            <template #header>
                <span class="font-semibold">{{ hasPassword ? $t('profile.change_password') : $t('profile.set_password') }}</span>
            </template>

            <!-- No email means no way to receive a reset link, so a password
                 here would be unrecoverable — AccountController enforces the
                 same rule server-side. -->
            <u-alert
                v-if="!email"
                color="warning"
                variant="subtle"
                icon="i-lucide-mail"
                :description="$t('profile.password_needs_email')"
            />

            <template v-else>
                <p v-if="!hasPassword" class="text-sm text-muted mb-3">{{ $t('profile.no_password_desc') }}</p>

                <form class="space-y-3 max-w-sm" @submit.prevent="submitPassword">
                    <u-form-field v-if="hasPassword" :label="$t('profile.current_password')" :error="passwordForm.errors.current_password" required>
                        <u-input v-model="passwordForm.current_password" type="password" autocomplete="current-password" class="w-full" />
                    </u-form-field>
                    <u-form-field :label="$t('profile.new_password')" :description="$t('auth.password_requirements')" :error="passwordForm.errors.password" required>
                        <u-input v-model="passwordForm.password" type="password" autocomplete="new-password" class="w-full" />
                    </u-form-field>
                    <u-form-field :label="$t('profile.confirm_new_password')" required>
                        <u-input v-model="passwordForm.password_confirmation" type="password" autocomplete="new-password" class="w-full" />
                    </u-form-field>
                    <u-button
                        type="submit"
                        color="primary"
                        size="sm"
                        :loading="passwordForm.processing"
                        :label="hasPassword ? $t('profile.change_password') : $t('profile.set_password')"
                    />

                    <!-- Said out loud rather than left to be discovered.
                         Changing a password is what somebody does when they
                         think a session is not theirs, so what it ends is the
                         thing they need to know. -->
                    <p v-if="hasPassword" class="text-xs text-muted">{{ $t('profile.password_change_signs_out_elsewhere') }}</p>
                </form>
            </template>
        </u-card>

        <!-- Closing the account.

             Last on the page and visibly separate, because everything above
             it is reversible and this is not. The decisions are rendered
             inline rather than behind a wizard: the thing somebody weighing
             this needs is the whole cost on one screen, not a first step. -->
        <u-card :ui="{ root: 'ring-error/30' }">
            <template #header>
                <div class="flex items-center gap-2">
                    <u-icon name="i-lucide-triangle-alert" class="size-4 shrink-0 text-error" />
                    <span class="font-semibold text-error">{{ $t('profile.delete_account') }}</span>
                </div>
            </template>

            <p class="text-sm text-muted mb-4">{{ $t('profile.delete_account_desc') }}</p>

            <!-- Events still running. Each needs an answer, and there is no
                 default, so the select starts empty. Three shapes, not two:
                 hand it to somebody, end it in place (frozen, but everyone's
                 progress and the row itself untouched), or delete it (the
                 event and everyone's progress on it, genuinely gone).

                 Each row also has its own Confirm — settles that one event
                 right now, through its own endpoint, with the account never
                 touched. Deciding a dozen events in one sitting right before
                 an irreversible account close is the wrong moment to be
                 making a dozen decisions; this lets each one be settled
                 whenever it's convenient, and only whatever is left still
                 needs an answer when it's actually time to delete the
                 account. Sliced client-side (visibleEvents) rather than
                 paginated server-side — the full list is already on the
                 page, "Load more" just reveals more of what's there. -->
            <div v-if="deletion.events.length" class="space-y-3 mb-6">
                <p class="text-sm font-medium text-highlighted">{{ $t('profile.delete_events_heading') }}</p>

                <div v-for="event in visibleEvents" :key="event.id" class="border border-default rounded-md p-3 space-y-2">
                    <div class="flex items-start justify-between gap-3 flex-wrap">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-highlighted">{{ event.title }}</p>
                            <p class="text-xs text-muted">
                                {{ event.participants === 0
                                    ? $t('profile.delete_event_nobody')
                                    : $t('profile.delete_event_players', { count: event.participants }) }}
                            </p>
                        </div>
                    </div>

                    <client-only>
                        <u-select
                            v-model="form.events[event.id]"
                            :items="choicesForEvent(event)"
                            :placeholder="$t('profile.delete_choose')"
                            class="w-full"
                        />
                    </client-only>

                    <!-- Explains whichever of the two endings is currently
                         picked — shown only once an answer exists, since a
                         hint about "End it" while "Hand it to X" is selected
                         would describe the wrong outcome. -->
                    <p v-if="form.events[event.id] === 'end'" class="text-xs text-muted">{{ $t('profile.delete_end_it_hint') }}</p>
                    <p v-else-if="form.events[event.id] === 'delete'" class="text-xs text-warning">{{ $t('profile.delete_hard_hint') }}</p>

                    <p v-if="!event.candidates.length" class="text-xs text-warning">
                        {{ $t('profile.delete_no_candidates') }}
                    </p>

                    <confirm-popover
                        v-if="form.events[event.id]"
                        :message="$t('profile.delete_settle_confirm')"
                        :confirm-label="$t('profile.delete_settle')"
                        :loading="settlingEventId === event.id"
                        @confirm="(note, done) => settleEvent(event, done)"
                    >
                        <u-button size="xs" color="neutral" variant="outline" :label="$t('profile.delete_settle')" />
                    </confirm-popover>
                </div>

                <u-button
                    v-if="eventsVisibleCount < deletion.events.length"
                    size="sm"
                    color="neutral"
                    variant="ghost"
                    :label="$t('common.load_more')"
                    @click="eventsVisibleCount += EVENTS_PAGE_SIZE"
                />
            </div>

            <div v-if="deletion.teams.length" class="space-y-3 mb-6">
                <p class="text-sm font-medium text-highlighted">{{ $t('profile.delete_teams_heading') }}</p>

                <div v-for="team in visibleTeams" :key="team.id" class="border border-default rounded-md p-3 space-y-2">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-highlighted">{{ team.name }}</p>
                        <p class="text-xs text-muted">{{ $t('profile.delete_team_members', { count: team.members }) }}</p>
                    </div>

                    <client-only>
                        <u-select
                            v-model="form.teams[team.id]"
                            :items="choicesForTeam(team)"
                            :placeholder="$t('profile.delete_choose')"
                            class="w-full"
                        />
                    </client-only>

                    <p v-if="form.teams[team.id] === 'delete'" class="text-xs text-warning">{{ $t('profile.delete_team_end_it_hint') }}</p>

                    <p v-if="!team.candidates.length" class="text-xs text-warning">
                        {{ $t('profile.delete_no_candidates') }}
                    </p>

                    <confirm-popover
                        v-if="form.teams[team.id]"
                        :message="$t('profile.delete_settle_confirm')"
                        :confirm-label="$t('profile.delete_settle')"
                        :loading="settlingTeamId === team.id"
                        @confirm="(note, done) => settleTeam(team, done)"
                    >
                        <u-button size="xs" color="neutral" variant="outline" :label="$t('profile.delete_settle')" />
                    </confirm-popover>
                </div>

                <u-button
                    v-if="teamsVisibleCount < deletion.teams.length"
                    size="sm"
                    color="neutral"
                    variant="ghost"
                    :label="$t('common.load_more')"
                    @click="teamsVisibleCount += EVENTS_PAGE_SIZE"
                />
            </div>

            <!-- Said before the button, not after: "everything is deleted" is
                 not quite true, and finding that out afterwards is worse than
                 reading it now. -->
            <u-alert
                v-if="deletion.keptEvents"
                color="neutral"
                variant="subtle"
                icon="i-lucide-history"
                class="mb-4"
                :description="$t('profile.delete_history_kept', { count: deletion.keptEvents })"
            />

            <!-- The confirmation itself moved into a modal — typing a
                 RuneScape name and a password inline, on a page, meant a
                 stray click on the page could get partway through an
                 irreversible action. Two ways in: the ordinary button, which
                 needs every per-item choice above answered first, and the
                 fast path beside it, which does not — it answers all of them
                 itself, the same way, for anyone who wants out entirely. -->
            <div class="flex flex-wrap gap-2">
                <u-button
                    color="error"
                    size="sm"
                    icon="i-lucide-trash-2"
                    :disabled="!readyForOrdinaryDelete"
                    :label="$t('profile.delete_account')"
                    @click="openDeleteModal(false)"
                />
                <u-button
                    color="error"
                    variant="outline"
                    size="sm"
                    icon="i-lucide-trash"
                    :label="$t('profile.delete_everything')"
                    @click="openDeleteModal(true)"
                />
            </div>
            <p class="text-xs text-muted mt-2 max-w-md">{{ $t('profile.delete_everything_desc') }}</p>
        </u-card>

        <client-only>
            <u-modal v-model:open="showDeleteModal" :title="$t('profile.delete_account')" :dismissible="!form.processing">
                <template #body>
                    <form class="space-y-3" @submit.prevent="submitDelete">
                        <u-alert
                            color="error"
                            variant="subtle"
                            icon="i-lucide-triangle-alert"
                            :description="deleteEverything ? $t('profile.delete_everything_confirm', { name: deletionPhrase }) : $t('profile.delete_modal_desc')"
                        />

                        <u-form-field
                            v-if="hasPassword"
                            :label="$t('profile.current_password')"
                            :error="form.errors.current_password"
                            required
                        >
                            <u-input v-model="form.current_password" type="password" autocomplete="current-password" class="w-full" />
                        </u-form-field>

                        <u-form-field
                            :label="$t('profile.delete_confirm_label', { name: deletionPhrase })"
                            :description="$t('profile.delete_confirm_help')"
                            :error="form.errors.confirmation"
                            required
                        >
                            <u-input v-model="form.confirmation" class="w-full" autocomplete="off" autofocus />
                        </u-form-field>
                    </form>
                </template>

                <template #footer>
                    <div class="flex justify-end gap-2 w-full">
                        <u-button color="neutral" variant="outline" :label="$t('common.cancel')" :disabled="form.processing" @click="showDeleteModal = false" />
                        <u-button
                            color="error"
                            icon="i-lucide-trash-2"
                            :loading="form.processing"
                            :disabled="!form.confirmation.trim()"
                            :label="$t('profile.delete_account')"
                            @click="submitDelete"
                        />
                    </div>
                </template>
            </u-modal>
        </client-only>
    </settings-layout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import SettingsLayout from '@/Components/SettingsLayout.vue';
import ClientOnly from '@/Components/ClientOnly.vue';
import ConfirmPopover from '@/Components/ConfirmPopover.vue';

const props = defineProps({
    email: { type: String, default: null },
    hasPassword: { type: Boolean, required: true },
    deletionPhrase: { type: String, default: '' },
    deletion: {
        type: Object,
        default: () => ({ events: [], teams: [], keptEvents: 0 }),
    },
});

const emailForm = useForm({ email: props.email ?? '', current_password: '' });

function submitEmail() {
    emailForm.put('/settings/account/email', {
        preserveScroll: true,
        // Never leave a password sitting in a form field after the round
        // trip, whichever way it went.
        onFinish: () => (emailForm.current_password = ''),
    });
}

const passwordForm = useForm({ current_password: '', password: '', password_confirmation: '' });

function submitPassword() {
    passwordForm.put('/settings/account/password', {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
}

// --- closing the account -------------------------------------------------

const form = useForm({
    confirmation: '',
    current_password: '',
    // Keyed by id so the server gets a decision per thing rather than a list
    // it has to line up with one it built separately.
    events: {},
    teams: {},
});

/**
 * Hand an event over, end it in place, or delete it outright. Deliberately no
 * pre-selected option: whichever one this defaulted to would be the wrong
 * thing to do by accident — and defaulting to the destructive one especially.
 */
function choicesForEvent(event) {
    return [
        ...event.candidates.map((candidate) => ({
            label: trans('profile.delete_transfer_to', { name: candidate.name }),
            value: candidate.id,
        })),
        { label: trans('profile.delete_end_it'), value: 'end' },
        { label: trans('profile.delete_hard'), value: 'delete' },
    ];
}

/**
 * Teams only ever had the one ending, and unlike an event's "End it" it is
 * NOT the soft, nothing-lost kind — deleting a team cascades to every
 * PlayerBoard and CompletedTile it holds a position on, and every bingo claim
 * it made, on every board/card it was ever assigned to (see the "Verify what
 * actually happens when a team is deleted" backlog entry). Its own label used
 * to borrow the event's "End it" wording, which is exactly backwards for a
 * choice that destroys progress rather than preserving it — fixed to say so.
 */
function choicesForTeam(team) {
    return [
        ...team.candidates.map((candidate) => ({
            label: trans('profile.delete_transfer_to', { name: candidate.name }),
            value: candidate.id,
        })),
        { label: trans('profile.delete_team_end_it'), value: 'delete' },
    ];
}

// --- settling one item now, and paging a long list -----------------------

// A handful at a time, not the whole list at once — reported as "does this
// list just keep going and going" against an account that owned a lot of
// events. Client-side rather than a server round trip: the full list is
// already on the page (deletion.events/.teams), so "Load more" only ever
// needs to reveal more of what's there.
const EVENTS_PAGE_SIZE = 5;
const eventsVisibleCount = ref(EVENTS_PAGE_SIZE);
const teamsVisibleCount = ref(EVENTS_PAGE_SIZE);
const visibleEvents = computed(() => props.deletion.events.slice(0, eventsVisibleCount.value));
const visibleTeams = computed(() => props.deletion.teams.slice(0, teamsVisibleCount.value));

const settlingEventId = ref(null);
const settlingTeamId = ref(null);

/**
 * Settle one event right now, through its own endpoint — the account is
 * never touched. Once it lands, the reload this triggers naturally drops
 * the row: a settled event no longer shows up in ownedLiveEvents() (handed
 * over, ended, or deleted, all three take it out of that list), so there is
 * nothing to remove from the array by hand here.
 */
function settleEvent(event, done) {
    settlingEventId.value = event.id;

    router.patch(`/settings/account/events/${event.id}`, { choice: form.events[event.id] }, {
        preserveScroll: true,
        onFinish: () => {
            settlingEventId.value = null;
            done?.();
        },
    });
}

function settleTeam(team, done) {
    settlingTeamId.value = team.id;

    router.patch(`/settings/account/teams/${team.id}`, { choice: form.teams[team.id] }, {
        preserveScroll: true,
        onFinish: () => {
            settlingTeamId.value = null;
            done?.();
        },
    });
}

/**
 * Every owned thing answered. The server checks this again — this only
 * stops somebody pressing the ordinary delete button when it was always
 * going to fail. Not required for the fast path below, which answers every
 * owned thing itself.
 */
const readyForOrdinaryDelete = computed(() => {
    const answered = (list, answers) => list.every((thing) => Boolean(answers[thing.id]));

    return answered(props.deletion.events, form.events) && answered(props.deletion.teams, form.teams);
});

// Which button opened the modal, since the two need different confirmation
// copy and the fast path skips validating the per-item selects entirely.
const showDeleteModal = ref(false);
const deleteEverything = ref(false);

function openDeleteModal(everything) {
    deleteEverything.value = everything;
    showDeleteModal.value = true;
}

function submitDelete() {
    form.transform((data) => ({ ...data, delete_everything: deleteEverything.value })).delete('/settings/account', {
        preserveScroll: true,
        onSuccess: () => (showDeleteModal.value = false),
        onError: (errors) => console.error(errors),
        onFinish: () => (form.current_password = ''),
    });
}
</script>
