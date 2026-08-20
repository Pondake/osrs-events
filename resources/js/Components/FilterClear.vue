<template>
    <!-- Goes in a u-select / u-input-menu #trailing slot. Renders the cross
         BESIDE the chevron rather than in place of it — the built-in `clear`
         prop swaps the two, which makes a control holding a value stop
         looking like a dropdown.

         A u-icon and not a u-button: this slot renders inside the
         component's own trigger button, and a nested <button> is invalid
         markup (Nuxt UI's own clear uses `as="span"` for the same reason).
         @click.stop keeps clearing from also toggling the menu open.

         Note the host needs wider end padding than Nuxt UI reserves — it
         sizes that for one trailing icon, so without it the value runs
         underneath the cross. See either page's `:ui` prop. -->
    <u-icon
        v-if="show"
        name="i-lucide-x"
        class="size-4 ms-1.5 shrink-0 text-dimmed hover:text-highlighted cursor-pointer transition-colors"
        role="button"
        :aria-label="$t('common.clear_filter')"
        @click.stop="$emit('clear')"
    />
    <u-icon name="i-lucide-chevron-down" class="size-4 shrink-0 text-dimmed" />
</template>

<script setup>
defineProps({
    show: { type: Boolean, default: false },
});

defineEmits(['clear']);
</script>
