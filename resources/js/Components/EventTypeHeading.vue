<template>
    <div class="min-w-0">
        <!-- What this event IS, at a size you can read.
             It was three `size="sm"` badges stacked above the title — type,
             status and win condition, all the same weight, all small enough
             that the one thing you most want to know at a glance (is this a
             bingo card or a race, and is it running) was the least legible
             thing on the page.

             Now: the type is a lockup with a real icon beside the title, the
             status is a coloured dot with a word, and everything else drops
             to a meta line underneath where secondary detail belongs. -->
        <div class="flex items-center gap-3 mb-1.5 flex-wrap">
            <span
                v-if="typeMeta"
                class="inline-flex items-center gap-2 rounded-full bg-primary/10 ring-1 ring-primary/30 pl-2 pr-3 py-1"
            >
                <u-icon :name="typeMeta.icon" class="size-4 text-primary shrink-0" />
                <span class="text-sm font-medium text-primary">{{ typeMeta.label }}</span>
            </span>

            <!-- A dot rather than another pill, so status reads as state and
                 not as a second category.

                 The live channel reports itself HERE rather than as a second
                 chip beside it. "Running" and "Updating live" were two green
                 dots saying overlapping things, and the second one made no
                 sense on an event that had ended — nothing is streaming into
                 a finished event, and nobody is waiting for it to. So the
                 pulse IS the stream: it beats while the connection is good,
                 stops when it drops, and never appears on an event that is
                 not running. -->
            <span class="inline-flex items-center gap-2" :title="liveTitle">
                <span class="relative flex size-2.5">
                    <span
                        v-if="pulsing"
                        class="absolute inline-flex size-full rounded-full opacity-60 animate-ping"
                        :class="DOT[status]"
                    />
                    <span class="relative inline-flex size-2.5 rounded-full" :class="DOT[status]" />
                </span>
                <span class="text-sm font-medium" :class="TEXT[status]">{{ $t(`boards.status_${status}`) }}</span>
                <!-- Only when the stream has actually fallen over. A healthy
                     connection says so by beating; saying it in words as
                     well is the duplication this replaced. -->
                <span v-if="status === 'live' && streaming && stale" class="text-xs text-muted">
                    {{ $t('events.reconnecting') }}
                </span>
            </span>
        </div>

        <!-- A step smaller on a phone, and balanced. Clan events get titles
             like "The Grand Midsummer Clan Championship — Season Four": at
             the desktop size that is six lines on a 375px screen, pushing
             the status, the dates and every control below the fold. Not
             clamped — a title is the one thing on this page nobody should
             have to expand to read.

             The `!` is load-bearing. Nuxt UI ships an UNLAYERED `h1 {
             font-size: var(--text-3xl) }`, and unlayered CSS beats anything
             inside `@layer utilities` whatever its specificity — so a plain
             `text-2xl` on this element is silently ignored, which is why the
             heading was 30px here no matter what the class said. Only the
             mobile step is forced; above sm the base rule is already right. -->
        <h1 class="max-sm:text-2xl! font-bold text-highlighted text-balance break-words">{{ event.title }}</h1>

        <p v-if="event.description" class="text-muted mt-1">{{ event.description }}</p>

        <!-- Secondary facts: the dates, and whatever the page adds through
             the slot (win condition, board size, what a race ranks on). -->
        <div class="flex items-center gap-x-4 gap-y-1 flex-wrap mt-2 text-sm text-muted">
            <span class="inline-flex items-center gap-1.5">
                <u-icon name="i-lucide-calendar" class="size-4 shrink-0" />
                {{ dateRange }}
            </span>
            <slot name="meta" />
        </div>

        <!-- An admin looking at an event they do not host. The public side
             gives them no controls at all — deliberately — and said nothing
             about where the power went, so it read as buttons going missing.
             This is the only place the two sides are joined up. -->
        <div v-if="adminEditUrl" class="mt-3">
            <u-alert color="neutral" variant="subtle" icon="i-lucide-shield" :description="$t('events.open_in_admin_hint')">
                <template #actions>
                    <u-button
                        :href="adminEditUrl"
                        color="neutral"
                        variant="outline"
                        size="xs"
                        icon="i-lucide-external-link"
                        :label="$t('events.open_in_admin')"
                    />
                </template>
            </u-alert>
        </div>

        <!-- Reading somebody else's private event on an admin's pass.
             The power is deliberate (BoardAccessService::canBypass) and
             moderating is what it is for, but exercising it silently is a
             different thing from having it. /teams has said this out loud
             since it was built; an invite-only clan event is at least as
             good a place to say it. -->
        <div v-if="viewingAsAdmin" class="mt-3">
            <u-alert
                color="neutral"
                variant="subtle"
                icon="i-lucide-eye"
                :description="$t('events.viewing_as_admin')"
            />
        </div>

        <!-- On hold, said once and where everybody looks.
             Here rather than on each event page for the same reason the
             save-as-template prompt is: this component is the one thing all
             three types share, and a pause means the same thing on a race as
             on a bingo card. The dot above already says "paused"; this says
             what that costs, because "can I still claim this?" is the next
             question and a coloured word does not answer it. -->
        <div v-if="status === 'paused'" class="mt-3">
            <u-alert
                color="warning"
                variant="subtle"
                icon="i-lucide-pause"
                :description="pausedDescription"
            >
                <!-- The host who paused it gets the way back on the banner
                     itself. Resuming lived four clicks deep in a settings tab
                     called "Danger zone", which is where you go to end an
                     event, not to un-pause one you paused ten minutes ago. -->
                <template v-if="canEdit" #actions>
                    <u-button
                        color="warning"
                        variant="solid"
                        size="xs"
                        icon="i-lucide-play"
                        :label="$t('events.danger_resume_cta')"
                        :loading="resuming"
                        @click="resume"
                    />
                </template>
            </u-alert>
        </div>

        <!-- The moment a host knows whether the format was worth keeping.
             Here rather than on each event page because this component is
             the one thing all three of them share, and it already works out
             whether the event has ended. -->
        <div v-if="canEdit && status === 'ended'" class="mt-3">
            <u-alert color="neutral" variant="subtle" icon="i-lucide-layout-template">
                <template #description>
                    <div class="flex items-center justify-between gap-3 flex-wrap">
                        <span class="text-sm">{{ $t('blueprints.finished_prompt') }}</span>
                        <client-only>
                            <blueprint-save-modal :event-id="event.id" :event-title="event.title" />
                        </client-only>
                    </div>
                </template>
            </u-alert>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { eventStatus, formatDate } from '@/Support/board';
import { eventTypeMeta } from '@/Support/eventTypes';
import ClientOnly from '@/Components/ClientOnly.vue';
import BlueprintSaveModal from '@/Components/BlueprintSaveModal.vue';

const props = defineProps({
    event: { type: Object, required: true },
    // Whether this viewer runs the event. Only a host is offered the
    // save-as-template prompt below, and the resume button on the banner.
    canEdit: { type: Boolean, default: false },
    // Set when the only reason this page opened is a site-admin pass.
    viewingAsAdmin: { type: Boolean, default: false },
    // Where an admin who does not host this event goes to change it. Null
    // for everybody else, including an admin who does host it.
    adminEditUrl: { type: String, default: null },
    // The live channel's own state, from useEventStream on the page. The
    // status dot is where it is reported — see the note in the template.
    streaming: { type: Boolean, default: false },
    stale: { type: Boolean, default: false },
});

const typeMeta = computed(() => eventTypeMeta(props.event.type));

const status = computed(() => eventStatus(props.event));

/** Beats only while the event is running AND the stream is healthy. */
const pulsing = computed(() => status.value === 'live' && props.streaming && ! props.stale);

const liveTitle = computed(() => {
    if (status.value !== 'live' || ! props.streaming) return undefined;

    return props.stale ? trans('events.reconnecting') : trans('events.auto_updating');
});

/**
 * The banner carries the host's reason when there is one. "Paused" answers
 * "can I still claim this?"; only the host can answer "for how long, and
 * why", and that is the question the clan actually asks in Discord.
 */
const pausedDescription = computed(() => (props.event.pause_reason
    ? trans('events.paused_banner_reason', { reason: props.event.pause_reason })
    : trans('events.paused_banner')));

const resuming = ref(false);

function resume() {
    resuming.value = true;

    router.patch(`/events/${props.event.id}/pause`, { paused: false, notify: true }, {
        preserveScroll: true,
        onError: (errors) => console.error(errors),
        onFinish: () => { resuming.value = false; },
    });
}

const DOT = { upcoming: 'bg-info', live: 'bg-success', paused: 'bg-warning', ended: 'bg-muted' };
const TEXT = { upcoming: 'text-info', live: 'text-success', paused: 'text-warning', ended: 'text-muted' };

const dateRange = computed(() => {
    if (!props.event.start_date && !props.event.end_date) return trans('boards.no_dates');

    return `${formatDate(props.event.start_date)} – ${formatDate(props.event.end_date)}`;
});
</script>
