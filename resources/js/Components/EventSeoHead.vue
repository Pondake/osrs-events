<template>
    <seo-head :options="options" />
</template>

<script setup>
import { computed } from 'vue';
import { trans } from 'laravel-vue-i18n';
import SeoHead from '@/Components/SeoHead.vue';
import { parseInline } from '@/Support/richtext';

const props = defineProps({
    event: { type: Object, required: true },
});

const DESCRIPTION_MAX = 160;

const FALLBACK_KEYS = {
    SNAKES_LADDERS: 'seo.board_desc',
    BINGO: 'seo.bingo_event_desc',
    SKILL_RACE: 'seo.skill_race_event_desc',
    DROP_RACE: 'seo.drop_race_event_desc',
};

const description = computed(() => {
    const own = parseInline(props.event.description ?? '')
        .map((token) => token.value)
        .join('')
        .replace(/\s+/g, ' ')
        .trim();

    if (own === '') {
        return trans(FALLBACK_KEYS[props.event.type] ?? 'seo.board_desc', { title: props.event.title });
    }

    return own.length > DESCRIPTION_MAX ? `${own.slice(0, DESCRIPTION_MAX - 1).trimEnd()}…` : own;
});

// Listed events open for signed-out visitors (BoardAccessService::canView), so
// they are the ones a search engine may index. An unlisted event is only ever
// rendered to someone with access, and stays out of the index.
const options = computed(() => ({
    title: props.event.title,
    description: description.value,
    noindex: !props.event.is_listed,
}));
</script>
