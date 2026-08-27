<template>
    <figure>
        <!-- No `src` yet: a real screenshot is still pending (see the
             backlog entry this component was built for) — a dashed box
             naming what will go here, rather than a broken <img> or nothing
             at all. Once a real file exists under public/images/guides/,
             pass it as `src` and this renders the actual image instead,
             still with the same alt text and caption. -->
        <div
            v-if="!src"
            class="rounded-lg ring-2 ring-dashed ring-default bg-elevated/50 aspect-video flex flex-col items-center justify-center gap-2 px-6 text-center"
        >
            <u-icon name="i-lucide-image" class="size-8 text-muted/60 shrink-0" />
            <p class="text-sm text-muted max-w-sm">{{ alt }}</p>
        </div>

        <img v-else :src="src" :alt="alt" class="rounded-lg ring ring-default w-full" loading="lazy" />

        <!-- Shown either way, placeholder or real image — alt text answers
             "what is this for a screen reader", the caption answers "what
             does this show" for everyone looking at the page, sighted or
             not. Losing the caption once a real screenshot lands would make
             the page worse for sighted users, not better. -->
        <figcaption v-if="caption" class="text-xs text-muted text-center mt-2">{{ caption }}</figcaption>
    </figure>
</template>

<script setup>
defineProps({
    // Left null until a real screenshot exists under public/images/guides/.
    src: { type: String, default: null },
    // Required either way: this is what a screen reader announces for the
    // placeholder AND for the eventual real image — written as a full
    // description of what the screenshot shows, not a short label, since
    // it's also the only content of the placeholder box.
    alt: { type: String, required: true },
    // Optional, visible caption below the image/placeholder. Defaults to
    // `alt` when not given, so a caller that only has one description isn't
    // forced to write it twice.
    caption: { type: String, default: null },
});
</script>
