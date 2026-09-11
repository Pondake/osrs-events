<template>
    <div
        ref="root"
        :id="`setting-${name}`"
        class="scroll-mt-24 rounded-md transition-shadow duration-300"
        :class="highlighted ? 'ring-2 ring-primary ring-offset-4 ring-offset-(--ui-bg)' : ''"
    >
        <u-form-field :label="$t(`admin.setting_${name}`)" :description="description ?? defaultDescription" :error="error">
            <slot />
        </u-form-field>
    </div>
</template>

<script setup>
import { computed, inject, onBeforeUnmount, onMounted, ref } from 'vue';
import { trans } from 'laravel-vue-i18n';

/**
 * One site setting on the admin form. The label is `admin.setting_<name>`
 * and the description `admin.setting_<name>_desc` — the same keys
 * Setting::searchIndex() reads, so a field and its search entry cannot
 * drift apart. Registers itself with the page so a search hit can open the
 * right section and point at it.
 */
const props = defineProps({
    name: { type: String, required: true },
    section: { type: String, required: true },
    description: { type: String, default: null },
    error: { type: String, default: null },
});

const registry = inject('settingFields', null);
const root = ref(null);
const highlighted = ref(false);
let timer = null;

const defaultDescription = computed(() => {
    const key = `admin.setting_${props.name}_desc`;
    const text = trans(key);
    return text === key ? undefined : text;
});

function highlight() {
    root.value?.scrollIntoView({ block: 'center', behavior: 'smooth' });
    highlighted.value = true;
    clearTimeout(timer);
    timer = setTimeout(() => { highlighted.value = false; }, 2500);
}

onMounted(() => registry?.register(props.name, { section: props.section, highlight }));
onBeforeUnmount(() => {
    clearTimeout(timer);
    registry?.unregister(props.name);
});
</script>
