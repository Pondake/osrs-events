<template>
    <div class="rounded-md ring ring-default bg-default px-2.5 py-1.5 text-xs space-y-0.5">
        <div class="flex items-center justify-between gap-2 flex-wrap">
            <span class="font-medium text-sm">
                {{ report.kind }} · {{ report.name }}<span v-if="report.quantity > 1"> × {{ report.quantity }}</span>
            </span>
            <span class="text-muted">{{ formatTimestamp(report.createdAt) }}</span>
        </div>
        <p class="text-muted">
            {{ [
                report.source ? $t('plugin_tests.expect_source', { source: report.source }) : $t('plugin_tests.no_source'),
                report.rsn,
                report.pluginVersion ? $t('plugin_tests.version', { version: report.pluginVersion }) : $t('plugin_tests.no_version'),
            ].join(' · ') }}
        </p>
        <p v-if="detailed && contextLine" class="text-muted break-words">{{ contextLine }}</p>
        <p v-if="detailed && report.items.length" class="text-muted break-words">{{ $t('plugin_tests.items', { items: report.items.join(', ') }) }}</p>
        <p v-for="(claim, index) in report.claims" :key="`c${index}`">
            {{ $t('plugin_tests.claimed', { label: claim.label, event: claim.eventTitle, status: claim.status }) }}
        </p>
        <p v-for="(entry, index) in report.progress" :key="`p${index}`">
            {{ $t('plugin_tests.progressed', { label: entry.label, done: entry.done, required: entry.required }) }}
        </p>
        <p v-if="!report.claims.length && !report.progress.length" class="text-muted">{{ $t('plugin_tests.no_outcome') }}</p>
        <p v-if="report.doubts.length" class="text-warning">{{ $t('plugin_tests.doubts', { doubts: report.doubts.join(', ') }) }}</p>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { formatTimestamp } from '@/Support/audit';

const props = defineProps({
    report: { type: Object, required: true },
    detailed: { type: Boolean, default: false },
});

const contextLine = computed(() => Object.entries(props.report.context)
    .filter(([key, value]) => key !== 'source' && value !== null && value !== '')
    .map(([key, value]) => `${key}=${value}`)
    .join(' · '));
</script>
