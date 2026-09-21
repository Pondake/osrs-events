<!--
    A grid of tiles or squares, the surface it sits on, and everything edit
    mode adds to it. Every event type with a grid renders this and nothing of
    its own, so a board and a bingo card cannot look like two different
    products for the same job.

    It owns the notice too. While that was a separate component with an
    `overlap` prop, the board passed it and the card did not, so the pill hung
    on the board's border and floated above the card's — a prop is a way for
    two pages to drift apart, which is the thing this exists to stop.
-->
<template>
    <!-- `isolate` seals the pill's z-index into this box. Without it that
         z-10 sits in the root stacking context and climbs over a teleported
         modal. -->
    <div class="isolate">
        <div v-if="editing" class="relative z-10 flex justify-start sm:justify-center mb-2 sm:-mb-4">
            <div class="inline-flex max-w-full items-center gap-2 rounded-full bg-default ring-1 ring-primary/50 shadow-sm py-1 pl-3 pr-1">
                <u-icon name="i-lucide-grid-2x2-plus" class="size-3.5 shrink-0 text-primary" />
                <span class="text-xs font-semibold truncate">{{ label }}</span>
                <!-- Round, and inset from the pill's own edge: a
                     square-cornered button flush against a fully rounded pill
                     reads as two shapes fighting rather than one control. -->
                <u-button
                    size="xs"
                    color="neutral"
                    variant="solid"
                    class="rounded-full shrink-0"
                    icon="i-lucide-check"
                    :label="$t('common.done_editing')"
                    @click="emit('done')"
                />
            </div>
        </div>

        <!-- The grid can be wider than the column, so it scrolls. No padding,
             on purpose: the scroller clips the edit ring along its straight
             edges and leaves only the corner arcs — see `.is-editing` in
             app.css. -->
        <div class="overflow-x-auto">
            <!-- osrs-border/board-parchment as Tailwind utilities rather than
                 the old app's custom CSS: this codebase writes custom CSS only
                 where Tailwind cannot, and it can. `.is-editing` is the one
                 exception, in app.css, because a ring that has to survive this
                 scroller is not a utility. -->
            <div
                class="relative rounded-xl p-3 border-2 border-stone-400 dark:border-stone-600 bg-amber-50/90 dark:bg-stone-900"
                :class="[minWidth, editing ? 'is-editing sm:pt-5' : '']"
            >
                <slot />
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    editing: { type: Boolean, default: false },
    // What is being edited, in the words of the format: a board has tiles, a
    // card has squares. The only thing the two are allowed to differ on.
    label: { type: String, required: true },
    // A board below its minimum width scrolls rather than squeezing its tiles
    // into nothing; a card has no such floor.
    minWidth: { type: String, default: '' },
});

const emit = defineEmits(['done']);
</script>
