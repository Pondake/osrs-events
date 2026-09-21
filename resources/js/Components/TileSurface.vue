<!--
    The surface a grid of tiles or squares sits on, and the ring it wears
    while it is being edited.

    One component for every event type that has a grid. The board and the
    bingo card each had their own box and their own edit-mode ring, so the
    same mode looked different depending on which event you opened, and every
    change had to be made twice to stay level.
-->
<template>
    <div
        class="relative rounded-xl p-3 border-2 border-stone-400 dark:border-stone-600 bg-amber-50/90 dark:bg-stone-900"
        :class="[editing ? 'is-editing' : '', editing && overlap ? 'sm:pt-5' : '']"
    >
        <slot />
    </div>
</template>

<script setup>
defineProps({
    editing: { type: Boolean, default: false },
    // Adds the top padding the notice pill dips into. Only where the pill is
    // rendered with its own `overlap` — see EditModeNotice.
    overlap: { type: Boolean, default: false },
});
</script>
