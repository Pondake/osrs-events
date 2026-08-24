<template>
    <u-dropdown-menu :items="menuItems" :ui="{ content: 'w-56' }">
        <u-button
            :color="badgeTotal ? 'warning' : 'neutral'"
            variant="outline"
            size="sm"
            icon="i-lucide-sliders-horizontal"
            trailing-icon="i-lucide-chevron-down"
            :label="$t('board.manage')"
        >
            <!-- `xs`, not `sm`: a badge one size up makes the trigger 32px
                 tall next to 28px siblings, and a row of three buttons where
                 one is taller reads as a mistake rather than as emphasis. -->
            <template v-if="badgeTotal" #trailing>
                <u-badge :label="String(badgeTotal)" color="warning" variant="solid" size="xs" />
                <u-icon name="i-lucide-chevron-down" class="size-4" />
            </template>
        </u-button>
    </u-dropdown-menu>
</template>

<script setup>
import { computed } from 'vue';

/**
 * The host's tools, behind one button.
 *
 * An event page carried six equally-weighted buttons — who is playing, join
 * or leave, edit tiles, fill in tiles, event settings, review — and on a
 * phone that is two full rows of 44px targets above the thing you came to
 * look at. Two kinds of action were sitting in one row: playing (constant,
 * everybody) and running the event (occasional, hosts only). A host is both,
 * so a host got all six and a player's two drowned among them.
 *
 * Only the second kind moves in here. The count of anything waiting — a
 * review queue — rides on the trigger, because hiding a number a host is
 * meant to act on would be trading one problem for a worse one.
 */
const props = defineProps({
    /**
     * `[{ key, label, icon, badge, active }]`. `active` marks a mode that is
     * currently on (editing tiles), which the menu shows with a check rather
     * than by changing the item's colour — a menu row has no room to say
     * "this is a toggle and it is on" any other way.
     */
    items: { type: Array, required: true },
});

const emit = defineEmits(['select']);

const badgeTotal = computed(() => props.items.reduce((total, item) => total + (item.badge ?? 0), 0));

/**
 * A count goes into the LABEL rather than into a slot on the row: the menu
 * renders items from a plain array, and a number that only survives if a
 * particular slot is supported is a number that quietly disappears one
 * upgrade later. On the trigger it is a badge, where it has room to be one.
 */
const menuItems = computed(() => [props.items.map((item) => ({
    label: item.badge ? `${item.label} · ${item.badge}` : item.label,
    icon: item.active ? 'i-lucide-check' : item.icon,
    onSelect: () => emit('select', item.key),
}))]);
</script>
