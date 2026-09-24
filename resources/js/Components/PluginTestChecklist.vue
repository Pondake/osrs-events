<template>
    <div class="space-y-2">
        <details
            v-for="scenario in scenarios"
            :key="scenario.key"
            class="group rounded-lg ring ring-default"
            :open="!detailed && scenario.status === 'partial'"
        >
            <summary class="flex items-start gap-3 px-3 py-2.5 list-none hover:bg-elevated/50 rounded-lg focus-visible:outline-2 focus-visible:outline-primary min-h-11">
                <u-icon :name="statusIcon(scenario.status)" class="size-5 mt-0.5 shrink-0" :class="statusClass(scenario.status)" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-medium">{{ $t(`plugin_tests.scenario_${scenario.key}`) }}</span>
                        <u-badge :color="statusColor(scenario.status)" variant="subtle" size="sm" :label="$t(`plugin_tests.status_${scenario.status}`)" />
                    </div>
                    <p class="text-sm text-muted mt-0.5">{{ $t(`plugin_tests.scenario_${scenario.key}_do`) }}</p>
                </div>
                <u-icon name="i-lucide-chevron-down" class="size-4 mt-1 shrink-0 text-muted transition-transform group-open:rotate-180" />
            </summary>

            <div class="px-3 pb-3 space-y-3">
                <div v-for="(expect, index) in scenario.expectations" :key="index" class="rounded-md bg-elevated/40 px-3 py-2 text-sm space-y-1.5">
                    <div class="flex items-start gap-2">
                        <u-icon :name="statusIcon(expect.status)" class="size-4 mt-0.5 shrink-0" :class="statusClass(expect.status)" />
                        <p class="flex-1 min-w-0 break-words">{{ describe(expect) }}</p>
                    </div>

                    <ul v-if="expect.problems.length" class="ps-6 space-y-0.5">
                        <li v-for="(problem, p) in expect.problems" :key="p" class="text-warning">{{ problemLabel(problem) }}</li>
                    </ul>

                    <p v-if="scenario.key === 'connect' && expect.lastUsedAt" class="ps-6 text-muted">
                        {{ $t('plugin_tests.last_used', { time: formatTimestamp(expect.lastUsedAt) }) }}
                    </p>

                    <ul v-if="expect.reports.length" class="ps-6 space-y-1.5">
                        <li v-for="report in expect.reports" :key="report.id">
                            <plugin-test-report :report="report" :detailed="detailed" />
                        </li>
                    </ul>
                </div>
            </div>
        </details>
    </div>
</template>

<script setup>
import { trans } from 'laravel-vue-i18n';
import PluginTestReport from '@/Components/PluginTestReport.vue';
import { formatTimestamp } from '@/Support/audit';
import { pluginTestStatusClass as statusClass, pluginTestStatusIcon as statusIcon } from '@/Support/pluginTests';

defineProps({
    scenarios: { type: Array, required: true },
    // The admin view: every context field on every report.
    detailed: { type: Boolean, default: false },
});

function statusColor(status) {
    return { ok: 'success', partial: 'warning' }[status] ?? 'neutral';
}

function describe(expect) {
    if (!expect.kind) return trans('plugin_tests.expect_connect');

    const parts = [
        `${expect.kind} · ${expect.names.length > 3 ? trans('plugin_tests.any_of', { count: expect.names.length }) : expect.names.join(' / ')}`,
        expect.source ? trans('plugin_tests.expect_source', { source: expect.source }) : null,
        expect.fields.length ? trans('plugin_tests.expect_fields', { fields: expect.fields.join(', ') }) : null,
        expect.minQuantity > 1 ? trans('plugin_tests.expect_min_quantity', { min: expect.minQuantity }) : null,
        expect.count > 1 ? trans('plugin_tests.expect_count', { count: expect.count }) : null,
        expect.outcome ? trans(`plugin_tests.expect_outcome_${expect.outcome}`) : null,
    ];

    return parts.filter(Boolean).join(' · ');
}

function problemLabel(problem) {
    if (typeof problem === 'string') return trans(`plugin_tests.problem_${problem}`);

    return trans(`plugin_tests.problem_${problem.code}`, {
        have: problem.have ?? '',
        need: problem.need ?? '',
        fields: (problem.fields ?? []).join(', '),
    });
}
</script>
