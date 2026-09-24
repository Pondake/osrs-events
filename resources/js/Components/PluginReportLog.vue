<template>
    <div class="space-y-2 text-sm">
        <u-input v-if="reports.length > 5" v-model="search" icon="i-lucide-search" :placeholder="$t('plugin_tests.log_search')" class="w-full" />
        <p v-if="!reports.length" class="text-muted">{{ $t('plugin_tests.log_empty') }}</p>
        <p v-else-if="!shown.length" class="text-muted">{{ $t('plugin_tests.log_no_match') }}</p>
        <ul v-else class="space-y-1.5">
            <li v-for="report in shown" :key="report.id">
                <plugin-test-report :report="report" :detailed="detailed" />
            </li>
        </ul>
        <p v-if="reports.length >= limit" class="text-xs text-muted">{{ $t('plugin_tests.log_limit', { count: limit }) }}</p>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import PluginTestReport from '@/Components/PluginTestReport.vue';

const props = defineProps({
    reports: { type: Array, required: true },
    detailed: { type: Boolean, default: false },
    limit: { type: Number, default: 200 },
});

const search = ref('');

// Name, character, source or the event a claim landed in.
const shown = computed(() => {
    const needle = search.value.trim().toLowerCase();
    if (!needle) return props.reports;

    return props.reports.filter((report) => [
        report.name,
        report.rsn,
        report.source,
        report.pluginVersion,
        ...report.claims.map((claim) => claim.eventTitle),
    ].some((value) => value?.toLowerCase().includes(needle)));
});
</script>
