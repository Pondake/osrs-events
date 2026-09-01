<template>
    <!-- One control for what is really one decision. It was two
         `<input type="date">` fields: two clicks into two separate pickers to
         express a single span, with nothing on screen showing how long the
         event actually runs.

         SSR: `u-popover` and `u-calendar` both reach @nuxt/ui's `#imports`
         barrel, which is the crash documented in docs/ssr-gotchas.md #6. Safe
         here only because this component is reached exclusively through
         BoardSettingsModal, which every page loads via defineAsyncComponent
         inside <client-only> — it never enters the SSR module graph. Do not
         render this from a server-rendered page without that wrapper. -->
    <u-popover v-model:open="open">
        <u-button
            color="neutral"
            variant="outline"
            icon="i-lucide-calendar"
            class="w-full justify-start font-normal"
            :class="hasError ? 'ring ring-error' : ''"
        >
            <span v-if="label" class="truncate">{{ label }}</span>
            <span v-else class="text-dimmed">{{ $t('admin.date_range_placeholder') }}</span>

            <!-- The span in days, pushed to the trailing edge of a control
                 that is wide enough to have one. The dates answer "when",
                 this answers "how long" — which is the half a host is
                 actually deciding, and it was only visible inside the open
                 popover until now. -->
            <span v-if="duration" class="ms-auto shrink-0 ps-3 text-xs text-muted">{{ duration }}</span>
        </u-button>

        <template #content>
            <div class="p-2">
                <u-calendar
                    v-model="range"
                    range
                    :ui="{ cellTrigger: CELL }"
                    class="p-2"
                />

                <!-- The span in words, under the calendar rather than only in
                     the button, because the length is the thing a host is
                     actually deciding — "is a fortnight right" is the
                     question, not "is the 12th right". -->
                <p v-if="duration" class="px-2 pb-1 text-xs text-muted text-center">{{ duration }}</p>
            </div>
        </template>
    </u-popover>
</template>

<script setup>
import { computed, ref } from 'vue';
import { CalendarDate, parseDate } from '@internationalized/date';
// transChoice, not trans — "1 days" is the same small wrongness
// Support/duration.js already avoids for its own unit labels.
import { transChoice } from 'laravel-vue-i18n';

const props = defineProps({
    // Both `YYYY-MM-DD` or empty — the same strings the form posts and the
    // server validates, so this component changes the control and not the
    // payload.
    start: { type: String, default: '' },
    end: { type: String, default: '' },
    hasError: { type: Boolean, default: false },
});

const emit = defineEmits(['update:start', 'update:end']);

const open = ref(false);

/**
 * Every day in the range carried `data-selected`, and the solid variant
 * paints that `bg-primary` — so a fortnight rendered as fourteen filled
 * swatches, which reads as fourteen separate choices rather than one span.
 *
 * reka-ui also marks the two ends (`data-selection-start` /
 * `data-selection-end`), so the ends stay solid and everything between them
 * drops to a tint. Written out in full rather than composed: Tailwind scans
 * source text for class names, so an interpolated colour never gets
 * generated (same reason Support/announcement.js spells its backgrounds out).
 */
const CELL = [
    'data-selected:bg-primary/15 data-selected:text-highlighted data-selected:rounded-none',
    'data-selection-start:bg-primary data-selection-start:text-inverted data-selection-start:rounded-full',
    'data-selection-end:bg-primary data-selection-end:text-inverted data-selection-end:rounded-full',
].join(' ');

/** `YYYY-MM-DD` in, CalendarDate out; anything unparseable reads as unset. */
function toCalendarDate(value) {
    if (! value) return undefined;

    try {
        return parseDate(value);
    } catch {
        // A stored date the picker cannot read is not worth crashing a form
        // over — the field simply shows as empty and the host picks again.
        return undefined;
    }
}

function toIsoDate(value) {
    return value instanceof CalendarDate ? value.toString() : '';
}

const range = computed({
    get: () => ({ start: toCalendarDate(props.start), end: toCalendarDate(props.end) }),
    set: (value) => {
        emit('update:start', toIsoDate(value?.start));
        emit('update:end', toIsoDate(value?.end));

        // Closed only once BOTH ends exist. A range calendar's first click
        // sets the start and clears the end, so closing on any change would
        // shut the picker halfway through the one gesture it exists for.
        if (value?.start && value?.end) {
            open.value = false;
        }
    },
});

const formatter = new Intl.DateTimeFormat('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });

function readable(value) {
    const date = toCalendarDate(value);

    return date ? formatter.format(date.toDate('UTC')) : null;
}

const label = computed(() => {
    const from = readable(props.start);
    const to = readable(props.end);

    if (! from) return null;

    return to ? `${from} — ${to}` : from;
});

/** How long the event runs, in the unit a host would say it in. */
const duration = computed(() => {
    const from = toCalendarDate(props.start);
    const to = toCalendarDate(props.end);

    if (! from || ! to) return null;

    // Inclusive: a board that starts and ends on the same day lasts a day,
    // not zero.
    const days = Math.round((to.toDate('UTC') - from.toDate('UTC')) / 86400000) + 1;

    return transChoice('admin.date_range_days', days, { count: days });
});
</script>
