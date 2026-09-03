<template>
    <div v-if="hasNotice" class="space-y-3">
        <!-- An admin looking at an event they do not host. The public side
             gives them no controls at all — deliberately — and said nothing
             about where the power went, so it read as buttons going missing.
             This is the only place the two sides are joined up. -->
        <u-alert v-if="adminEditUrl" color="neutral" variant="subtle" icon="i-lucide-shield" :description="$t('events.open_in_admin_hint')">
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

        <!-- Reading somebody else's private event on an admin's pass.
             The power is deliberate (BoardAccessService::canBypass) and
             moderating is what it is for, but exercising it silently is a
             different thing from having it. /teams has said this out loud
             since it was built; an invite-only clan event is at least as
             good a place to say it. -->
        <u-alert
            v-if="viewingAsAdmin"
            color="neutral"
            variant="subtle"
            icon="i-lucide-eye"
            :description="$t('events.viewing_as_admin')"
        />

        <!-- On hold, said once and where everybody looks.
             Here rather than on each event page for the same reason the
             save-as-template prompt is: this component is the one thing all
             three types share, and a pause means the same thing on a race as
             on a bingo card. The status dot already says "paused"; this says
             what that costs, because "can I still claim this?" is the next
             question and a coloured word does not answer it. -->
        <u-alert
            v-if="status === 'paused'"
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

        <!-- Somebody got home. Everyone sees this, not only the person it
             happened to: the celebration this replaces was a modal in one
             browser that a refresh erased, so an event could be won and the
             other twenty people playing it would never find out.

             Two sentences, because the difference between them is the only
             thing anyone needs: under CONTINUE the podium is still filling
             up and the board is still open, under STOP the first finish
             ended it — which is also why the dice have gone, a question the
             page should answer before it is asked. -->
        <u-alert
            v-if="finishNotice"
            :color="event.closed_at ? 'success' : 'primary'"
            variant="subtle"
            :icon="event.closed_at ? 'i-lucide-trophy' : 'i-lucide-flag'"
            :description="finishNotice"
        />

        <!-- The moment a host knows whether the format was worth keeping.
             Here rather than on each event page because this component is
             the one thing all three of them share, and it already works out
             whether the event has ended. -->
        <u-alert v-if="canEdit && status === 'ended'" color="neutral" variant="subtle" icon="i-lucide-layout-template">
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
</template>

<script setup>
/**
 * The banners an event page puts under its header.
 *
 * Split out of EventTypeHeading on 2026-09-02: the heading is the `flex-1`
 * half of a row whose other half is the action bar, so everything inside it
 * — a full-width alert included — stopped where the buttons began. A notice
 * about admin powers or a paused event is page-wide news and now gets the
 * page's width, while the heading keeps only what belongs beside the
 * buttons.
 */
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { eventStatus } from '@/Support/board';
import ClientOnly from '@/Components/ClientOnly.vue';
import BlueprintSaveModal from '@/Components/BlueprintSaveModal.vue';

const props = defineProps({
    event: { type: Object, required: true },
    // Whether this viewer runs the event. Only a host is offered the
    // save-as-template prompt, and the resume button on the paused banner.
    canEdit: { type: Boolean, default: false },
    // Set when the only reason this page opened is a site-admin pass.
    viewingAsAdmin: { type: Boolean, default: false },
    // Where an admin who does not host this event goes to change it. Null
    // for everybody else, including an admin who does host it.
    adminEditUrl: { type: String, default: null },
    // The podium as it stands — see EventFinishService::places(). Empty
    // until somebody finishes, which is most of an event's life.
    finishes: { type: Array, default: () => [] },
});

const status = computed(() => eventStatus(props.event));

/**
 * Who won, and whether that was the end of it.
 *
 * Reads the first row rather than a "winner" field: the list is already in
 * finish order, and a second source for the same fact is a second thing to
 * keep in step.
 */
const finishNotice = computed(() => {
    const first = props.finishes[0];

    if (!first) return null;

    // Nothing while the top of the podium can still change. Reported
    // directly: a host approved the second submission first and every player
    // was told that team had got home first, while the claim that actually
    // won was still sitting unopened in the review queue. Saying nothing for
    // the minute it takes to open that claim is the honest option — the
    // correction is what makes it look broken.
    if (first.provisional) return null;

    if (props.event.closed_at) {
        return trans('events.finish_banner_won', { name: first.label });
    }

    return trans('events.finish_banner_continue', {
        name: first.label,
        count: props.finishes.length,
    });
});

// Nothing to say is nothing to render — an empty wrapper would still hand
// the page its own margin.
const hasNotice = computed(() => Boolean(props.adminEditUrl)
    || props.viewingAsAdmin
    || status.value === 'paused'
    || finishNotice.value !== null
    || (props.canEdit && status.value === 'ended'));

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
</script>
