<template>
    <div class="py-2 space-y-4">
        <!-- Who gets told, decided once for both actions below.
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
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { trans } from 'laravel-vue-i18n';

/**
 * The two ways to stop an event, in the one place a host will look for them.
 *
 * Both used to be unreachable from here. Deleting existed as a route with no
 * button outside the admin area — so a host could create an event and never
 * get rid of it — and pausing did not exist at all, which left "we need to
 * sort out a dispute before anyone rolls again" with no answer but editing
 * the end date and hoping.
 */
const props = defineProps({
    title: { type: String, required: true },
    // ISO timestamp, or null. The presence of it IS the paused state.
    pausedAt: { type: String, default: null },
    // Whether this viewer owns the event (or is an admin). Deleting is
    // theirs alone; pausing is open to any host.
    canDelete: { type: Boolean, default: false },
    pausing: { type: Boolean, default: false },
    deleting: { type: Boolean, default: false },
});

const emit = defineEmits(['pause', 'destroy']);

const notify = ref(true);
const typedTitle = ref('');
const reason = ref('');

const paused = computed(() => Boolean(props.pausedAt));

/**
 * Trimmed and case-insensitive: the point is to make somebody read the title
 * and type it, not to test their shift key. A stray trailing space from a
 * copy-paste is not a reason to refuse.
 */
const titleMatches = computed(() => typedTitle.value.trim().toLowerCase() === props.title.trim().toLowerCase());

const pausedSince = computed(() => (props.pausedAt
    ? new Date(props.pausedAt).toLocaleString('en-GB', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })
    : trans('common.unknown')));
</script>
