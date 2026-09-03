<template>
    <div class="min-w-0 flex-1">
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
                 not running. And it goes amber when it stops, because a
                 still green dot reads as green — see dotClass. -->
            <span class="inline-flex items-center gap-2" :title="liveTitle">
                <span class="relative flex size-2.5">
                    <span
                        v-if="pulsing"
                        class="absolute inline-flex size-full rounded-full opacity-60 animate-ping"
                        :class="dotClass"
                    />
                    <span class="relative inline-flex size-2.5 rounded-full" :class="dotClass" />
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
            <!-- Native title, not u-tooltip: this heading is shared by
                 BoardShow/Bingo/SkillRace, all rendered server-side, and a
                 real u-tooltip reaches @nuxt/ui's `#imports` specifier — the
                 SSR hazard CLAUDE.md warns about (see SkillRace.vue's own
                 error badge for the same reasoning). -->
            <span class="inline-flex items-center gap-1.5" :title="$t('events.meta_dates_hint')">
                <u-icon name="i-lucide-calendar" class="size-4 shrink-0" />
                {{ dateRange }}
            </span>
            <slot name="meta" />
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { trans } from 'laravel-vue-i18n';
import { eventStatus, formatDate } from '@/Support/board';
import { eventTypeMeta } from '@/Support/eventTypes';

const props = defineProps({
    event: { type: Object, required: true },
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
    if (status.value !== 'live') return undefined;
    if (props.stale) return trans('events.reconnecting');

    return props.streaming ? trans('events.auto_updating') : trans('events.not_updating');
});

const DOT = { upcoming: 'bg-info', live: 'bg-success', paused: 'bg-warning', ended: 'bg-muted' };
const TEXT = { upcoming: 'text-info', live: 'text-success', paused: 'text-warning', ended: 'text-muted' };

/**
 * Amber while a running event is not actually being kept up to date.
 *
 * A green dot that has stopped beating still reads as green — the beat is
 * the thing you notice once, and never again on a page you are reading
 * rather than watching. So the colour carries it too: green means the
 * numbers on this page are current, amber means they were current when it
 * loaded. Not connected yet, backgrounded tab (the stream is dropped on
 * purpose — see useEventStream) and a reconnect that never landed all look
 * the same from here, because they mean the same thing to a reader.
 */
const dotClass = computed(() => (status.value === 'live' && ! pulsing.value ? 'bg-warning' : DOT[status.value]));

const dateRange = computed(() => {
    if (!props.event.start_date && !props.event.end_date) return trans('boards.no_dates');

    return `${formatDate(props.event.start_date)} – ${formatDate(props.event.end_date)}`;
});
</script>
