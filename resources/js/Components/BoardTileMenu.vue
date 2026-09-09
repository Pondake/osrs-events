<template>
    <!-- `as-child` on @nuxt/ui's trigger means this adds no wrapper element:
         the grid the caller passes in stays the trigger's own root, so the
         connectors overlay keeps measuring the same box it always did. -->
    <u-context-menu :items="items" :disabled="disabled" :ui="{ content: 'w-56' }">
        <slot />
    </u-context-menu>
</template>

<script setup>
/**
 * The right-click menu for a board's tiles, in its own file so the page can
 * reach it with a dynamic `import()` from `onMounted()`.
 *
 * That is not indirection for its own sake — `u-context-menu` pulls in
 * `#imports`, which only resolves through the `ui()` Vite plugin's
 * bundler-time pipeline and crashes the SSR process at startup when it ends
 * up in the server graph (docs/ssr-gotchas.md #6). `<ClientOnly>` is the
 * usual answer, but it renders nothing on the server, and the board itself
 * has to be in the server's HTML. So the page renders the grid inside a
 * pass-through component and swaps in this one after mount instead: the
 * markup is written once, the menu is browser-only, and nothing about the
 * board depends on it having loaded.
 *
 * One menu for the whole grid, not one per tile — 81 mounted menus on a 9×9
 * board would be 81 floating-UI instances for a thing at most one of which
 * is ever open. Which tile was clicked is settled by the tile's own
 * `contextmenu` handler before this opens; see BoardShow's tileMenuItems.
 */
defineProps({
    /** Nuxt UI menu items, grouped — `[[…], […]]` renders a separator between. */
    items: { type: Array, required: true },
    /**
     * True when the pointer was not over a tile. The browser's own menu is
     * the right answer there (copy, inspect, reload) — a right-click on the
     * gap between two tiles is not a right-click on a tile.
     */
    disabled: { type: Boolean, default: false },
});
</script>
