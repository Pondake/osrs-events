<template>
    <div class="py-2 space-y-4">
        <!-- Where the event actually is, before any button that changes it.
             A list of stop controls can tell you what you may do and not one
             thing about what is true right now — and "is this still running?"
             is the question a host opens this panel to answer at least as
             often as they open it to act. -->
        <div class="rounded-lg ring ring-default p-3 flex items-start gap-3">
            <span class="size-2.5 rounded-full shrink-0 mt-1.5" :class="statusDot" />
            <div class="min-w-0">
                <p class="font-medium text-sm">{{ $t(statusMeta.labelKey) }}</p>
                <p class="text-xs text-muted leading-relaxed mt-0.5">{{ statusDetail }}</p>
            </div>
        </div>

        <!-- Who gets told, decided once for every action below.
             It is a checkbox rather than a silent default because the two
             sensible answers are genuinely different: cancelling an event
             forty people entered is news, and pausing for ten minutes to fix
             a typo in the rules is not. The hint is there because the honest
             answer to "will they all get this?" is no — see
             EventNotificationService. -->
        <div class="rounded-lg ring ring-default p-3 space-y-2">
            <u-checkbox v-model="notify" :label="$t('events.danger_notify_label')" />
            <p class="text-xs text-muted leading-relaxed">{{ $t('events.danger_notify_hint') }}</p>
        </div>

        <!-- Three rungs of one ladder, in order of how final they are:
             pause (back shortly), end (that is the result), delete (it never
             happened). Ending used to be the missing middle one, which is
             why "we are done, stop the clock" had no answer but editing the
             end date and hoping. -->

        <!-- Pausing. Reversible, so it gets a plain button and no ceremony. -->
        <div class="rounded-lg ring ring-default p-4 space-y-3">
            <div class="flex items-start gap-3">
                <u-icon :name="paused ? 'i-lucide-play' : 'i-lucide-pause'" class="size-5 shrink-0 mt-0.5 text-warning" />
                <div class="min-w-0">
                    <p class="font-medium text-sm">{{ paused ? $t('events.danger_resume_title') : $t('events.danger_pause_title') }}</p>
                    <p class="text-sm text-muted leading-relaxed mt-0.5">
                        {{ paused ? $t('events.danger_resume_body', { when: pausedSince }) : $t('events.danger_pause_body') }}
                    </p>
                </div>
            </div>

            <!-- Optional, and only on the way in. "Paused" tells a player
                 their claim will bounce; it does not tell them the host is
                 waiting on a screenshot from team B, which is the thing the
                 clan is asking in Discord. It rides along to the banner, the
                 email and the webhook post. -->
            <u-form-field v-if="!paused" :label="$t('events.danger_pause_reason')" :hint="$t('common.optional')">
                <u-input
                    v-model="reason"
                    :placeholder="$t('events.danger_pause_reason_placeholder')"
                    maxlength="200"
                    class="w-full"
                />
            </u-form-field>

            <u-button
                :color="paused ? 'success' : 'warning'"
                variant="soft"
                size="sm"
                :icon="paused ? 'i-lucide-play' : 'i-lucide-pause'"
                :label="paused ? $t('events.danger_resume_cta') : $t('events.danger_pause_cta')"
                :loading="pausing"
                @click="emit('pause', { paused: !paused, notify, reason })"
            />
        </div>

        <!-- Ending. More final than a pause and nothing like a delete: every
             row stays, the page stays readable, the podium stands. So the
             ceremony is scaled to match — a confirmation that names the
             consequence, not a typed-out title.

             Under the hood this is the same `closed_at` column the STOP
             finish rule stamps by itself, which is the argument for the
             button existing at all: it is a handle on a state machine that
             had to be built anyway, not a second way to stop an event. -->
        <div class="rounded-lg ring p-4 space-y-3" :class="closed ? 'ring-success/40 bg-success/5' : 'ring-default'">
            <div class="flex items-start gap-3">
                <u-icon :name="closed ? 'i-lucide-rotate-ccw' : 'i-lucide-flag'" class="size-5 shrink-0 mt-0.5" :class="closed ? 'text-success' : 'text-primary'" />
                <div class="min-w-0">
                    <p class="font-medium text-sm">{{ closed ? $t('events.danger_reopen_title') : $t('events.danger_end_title') }}</p>
                    <p class="text-sm text-muted leading-relaxed mt-0.5">
                        {{ closed ? $t('events.danger_reopen_body', { when: closedSince }) : $t('events.danger_end_body') }}
                    </p>
                </div>
            </div>

            <u-button
                :color="closed ? 'success' : 'primary'"
                variant="soft"
                size="sm"
                :icon="closed ? 'i-lucide-rotate-ccw' : 'i-lucide-flag'"
                :label="closed ? $t('events.danger_reopen_cta') : $t('events.danger_end_cta')"
                :loading="closing"
                @click="closed ? emit('close-event', { closed: false, notify }) : (confirmEnd = true)"
            />
        </div>

        <!-- Deleting. Behind the title, typed out, because this is the one
             control in the app that takes away work other people did — and
             because "are you sure?" has been clicked through by everyone who
             has ever used a computer.
             Only for the owner: a co-host can stop an event, but ending
             somebody else's is not theirs to do. The server says the same
             thing (BoardController::destroy); this is so nobody meets that
             rule as a 403. -->
        <div v-if="canDelete" class="rounded-lg ring ring-error/40 bg-error/5 p-4 space-y-3">
            <div class="flex items-start gap-3">
                <u-icon name="i-lucide-trash-2" class="size-5 shrink-0 mt-0.5 text-error" />
                <div class="min-w-0">
                    <p class="font-medium text-sm text-error">{{ $t('events.danger_delete_title') }}</p>
                    <p class="text-sm text-muted leading-relaxed mt-0.5">{{ $t('events.danger_delete_body') }}</p>
                </div>
            </div>

            <u-form-field :label="$t('events.danger_delete_confirm', { title })">
                <u-input v-model="typedTitle" :placeholder="title" autocomplete="off" class="w-full" />
            </u-form-field>

            <u-button
                color="error"
                size="sm"
                icon="i-lucide-trash-2"
                :label="$t('events.danger_delete_cta')"
                :disabled="!titleMatches"
                :loading="deleting"
                @click="emit('destroy', { notify })"
            />
        </div>

        <!-- The confirmation names the podium as it stands, because that IS
             the consequence: whatever this list says is the result, and the
             people below it stop where they are. A generic "are you sure?"
             would be asking about something the host cannot see. -->
        <u-modal v-model:open="confirmEnd" :title="$t('events.confirm_end_title')">
            <template #body>
                <p class="text-sm text-muted leading-relaxed">{{ $t('events.confirm_end_body') }}</p>

                <ol v-if="podium.length" class="mt-3 space-y-1.5">
                    <li v-for="place in podium" :key="place.id" class="flex items-center gap-2 text-sm">
                        <span class="w-6 text-center shrink-0">{{ medal(place.rank) }}</span>
                        <span class="truncate">{{ place.label }}</span>
                    </li>
                </ol>

                <p v-else class="mt-3 text-sm text-muted italic">{{ $t('events.confirm_end_nobody') }}</p>
            </template>
            <template #footer>
                <div class="flex justify-end gap-2 w-full">
                    <u-button color="neutral" variant="ghost" :label="$t('common.cancel')" @click="confirmEnd = false" />
                    <u-button
                        color="primary"
                        icon="i-lucide-flag"
                        :label="$t('events.danger_end_cta')"
                        :loading="closing"
                        @click="confirmEnd = false; emit('close-event', { closed: true, notify })"
                    />
                </div>
            </template>
        </u-modal>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { trans } from 'laravel-vue-i18n';
import { BOARD_STATUS_STYLE, eventStatus, formatDate } from '@/Support/board';

/**
 * Where the event stands, and the three ways to change it.
 *
 * Two of these used to be unreachable from anywhere. Deleting existed as a
 * route with no button outside the admin area — so a host could create an
 * event and never get rid of it — and pausing did not exist at all, which
 * left "we need to sort out a dispute before anyone rolls again" with no
 * answer but editing the end date and hoping.
 *
 * Ending was the third gap, and the one the finish work turned up: a board
 * could be won and go on offering dice rolls, because "over" was a pure
 * function of the calendar and nothing else could say it. It is deliberately
 * NOT a header button and not a dialog of its own — a host looking for how
 * to stop an event should find every answer to that question in one panel,
 * and the reason Manage exists at all is that this page once carried six
 * equally-weighted buttons (see EventManageMenu).
 */
const props = defineProps({
    title: { type: String, required: true },
    // The event itself, for the status line's dates. Optional: the panel
    // still works without it, it just has less to say.
    event: { type: Object, default: null },
    // The podium as it stands, for the confirmation to name. Passed in
    // rather than read off the event: the finishes are a page prop of their
    // own (they update over the live stream), not part of the event card.
    finishes: { type: Array, default: () => [] },
    // ISO timestamp, or null. The presence of it IS the paused state.
    pausedAt: { type: String, default: null },
    // Same again for "called": set by a host ending it, or by the first
    // finish on an event whose rule is STOP.
    closedAt: { type: String, default: null },
    // Whether this viewer owns the event (or is an admin). Deleting is
    // theirs alone; pausing and ending are open to any host.
    canDelete: { type: Boolean, default: false },
    pausing: { type: Boolean, default: false },
    closing: { type: Boolean, default: false },
    deleting: { type: Boolean, default: false },
});

const emit = defineEmits(['pause', 'close-event', 'destroy']);

const notify = ref(true);
const typedTitle = ref('');
const reason = ref('');
const confirmEnd = ref(false);

const paused = computed(() => Boolean(props.pausedAt));
const closed = computed(() => Boolean(props.closedAt));

/**
 * The same four-state answer the badge on the page gives, from the same
 * function — a panel that disagreed with the header about whether an event
 * is live would be worse than one that said nothing.
 */
const status = computed(() => eventStatus({
    start_date: props.event?.start_date,
    end_date: props.event?.end_date,
    paused_at: props.pausedAt,
    closed_at: props.closedAt,
}));

const statusMeta = computed(() => BOARD_STATUS_STYLE[status.value] ?? BOARD_STATUS_STYLE.live);

const statusDot = computed(() => ({
    upcoming: 'bg-info',
    live: 'bg-success',
    paused: 'bg-warning',
    ended: 'bg-error',
}[status.value] ?? 'bg-muted'));

/**
 * The sentence under the label — why it is in that state, which the label
 * itself cannot say. "Ended" reads the same whether the clock ran out or
 * somebody won, and those are different things to a host deciding what to do
 * next.
 */
const statusDetail = computed(() => {
    if (closed.value) return trans('events.status_detail_closed', { when: closedSince.value });
    if (paused.value) return trans('events.status_detail_paused', { when: pausedSince.value });
    if (status.value === 'ended') return trans('events.status_detail_ended', { when: formatDate(props.event?.end_date) });
    if (status.value === 'upcoming') return trans('events.status_detail_upcoming', { when: formatDate(props.event?.start_date) });

    return trans('events.status_detail_live', { when: formatDate(props.event?.end_date) });
});

/**
 * Trimmed and case-insensitive: the point is to make somebody read the title
 * and type it, not to test their shift key. A stray trailing space from a
 * copy-paste is not a reason to refuse.
 */
const titleMatches = computed(() => typedTitle.value.trim().toLowerCase() === props.title.trim().toLowerCase());

function whenText(value) {
    return value
        ? new Date(value).toLocaleString('en-GB', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })
        : trans('common.unknown');
}

const pausedSince = computed(() => whenText(props.pausedAt));
const closedSince = computed(() => whenText(props.closedAt));

/** Only the top three: the confirmation is a reminder, not the leaderboard. */
const podium = computed(() => props.finishes.slice(0, 3));

function medal(rank) {
    return ['🥇', '🥈', '🥉'][rank - 1] ?? `${rank}.`;
}
</script>
