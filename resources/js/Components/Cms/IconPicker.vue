<template>
    <u-popover v-model:open="open" :ui="{ content: 'w-80' }">
        <!-- Shows the chosen icon rather than its name: the name is a
             lookup key, the glyph is the thing being picked. -->
        <u-button color="neutral" variant="outline" class="w-full justify-start" :aria-label="$t('cms.pick_icon')">
            <u-icon v-if="modelValue" :name="modelValue" class="size-4 shrink-0" />
            <u-icon v-else name="i-lucide-image" class="size-4 shrink-0 text-dimmed" />
            <span class="truncate" :class="modelValue ? '' : 'text-dimmed'">
                {{ modelValue || $t('cms.no_icon') }}
            </span>
            <u-icon
                v-if="modelValue"
                name="i-lucide-x"
                class="size-4 ms-auto shrink-0 text-dimmed hover:text-highlighted"
                role="button"
                :aria-label="$t('cms.clear_icon')"
                @click.stop="select(null)"
            />
        </u-button>

        <template #content>
            <!-- virtualize keeps the list cheap as the catalog grows; groups
                 are what make ~90 icons browsable rather than a wall. -->
            <u-command-palette
                :groups="groups"
                :placeholder="$t('cms.search_icons')"
                :virtualize="{ estimateSize: 32 }"
                class="h-80"
                @update:model-value="onSelect"
            />
        </template>
    </u-popover>
</template>

<script setup>
import { computed, ref } from 'vue';
import { trans } from 'laravel-vue-i18n';
import { ICON_GROUPS } from '@/Support/iconCatalog';

const props = defineProps({
    modelValue: { type: String, default: null },
});

const emit = defineEmits(['update:modelValue']);

const open = ref(false);

/**
 * The catalog is deliberately finite — see Support/iconCatalog.js. Only
 * icons listed there are bundled by the build, so offering anything else
 * would let an admin choose one that never draws.
 */
const groups = computed(() =>
    ICON_GROUPS.map((group) => ({
        id: group.key,
        label: trans(group.label),
        items: group.icons.map((name) => ({
            // The bare name is the searchable label: an admin looking for a
            // sword types "sword", and that is what the name contains.
            label: name.replace('i-lucide-', ''),
            icon: name,
            value: name,
        })),
    })),
);

function onSelect(item) {
    if (item?.value) select(item.value);
}

function select(value) {
    emit('update:modelValue', value);
    open.value = false;
}
</script>
