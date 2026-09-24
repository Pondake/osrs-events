<template>
    <Head :title="$t('settings.nav_admin_plugin_tests')" />

    <admin-layout current="plugin-tests" :title="$t('settings.nav_admin_plugin_tests')" :description="$t('plugin_tests.admin_subtitle')">
        <div class="space-y-4">
            <u-alert
                v-if="mode !== 'testing'"
                color="neutral"
                variant="subtle"
                icon="i-lucide-flask-conical"
                :description="$t('plugin_tests.admin_not_testing')"
            />

            <template v-else>
                <p class="text-sm">
                    {{ $t('plugin_tests.admin_tester_count', { count: testers.length }) }}
                    <template v-if="testSet">
                        · <a :href="testSet.url" class="text-primary hover:underline focus-visible:outline-2 focus-visible:outline-primary rounded">{{ testSet.title }}</a>
                    </template>
                    <span v-else class="text-warning"> · {{ $t('plugin_tests.admin_no_event') }}</span>
                </p>

                <p v-if="!testers.length" class="text-sm text-muted">{{ $t('plugin_tests.admin_no_testers') }}</p>

                <div v-else class="overflow-x-auto rounded-lg ring ring-default">
                    <table class="w-full text-sm">
                        <thead class="bg-elevated/50 text-left">
                            <tr>
                                <th class="px-3 py-2 font-medium">{{ $t('plugin_tests.admin_tester') }}</th>
                                <th class="px-3 py-2 font-medium whitespace-nowrap">{{ $t('plugin_tests.admin_version') }}</th>
                                <th class="px-3 py-2 font-medium whitespace-nowrap text-right">{{ $t('plugin_tests.admin_reports') }}</th>
                                <th class="px-3 py-2 font-medium whitespace-nowrap">{{ $t('plugin_tests.admin_last_seen') }}</th>
                                <th v-for="scenario in scenarioKeys" :key="scenario" class="px-1.5 py-2 font-medium text-center whitespace-nowrap" :title="$t(`plugin_tests.scenario_${scenario}`)">
                                    {{ $t(`plugin_tests.short_${scenario}`) }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="tester in testers"
                                :key="tester.user.id"
                                role="button"
                                tabindex="0"
                                class="border-t border-default hover:bg-elevated/50 focus-visible:outline-2 focus-visible:outline-primary"
                                :class="{ 'bg-elevated/60': selected?.user.id === tester.user.id }"
                                @click="select(tester.user.id)"
                                @keydown.enter.prevent="select(tester.user.id)"
                                @keydown.space.prevent="select(tester.user.id)"
                            >
                                <td class="px-3 py-2">
                                    <p class="font-medium">{{ tester.user.name }}</p>
                                    <p class="text-xs text-muted">{{ tester.characters.map((c) => c.rsn).join(', ') || '—' }}</p>
                                </td>
                                <td class="px-3 py-2 font-mono text-xs whitespace-nowrap">{{ tester.pluginVersion ?? '—' }}</td>
                                <td class="px-3 py-2 text-xs whitespace-nowrap text-right">
                                    {{ tester.reportCount }}
                                    <span v-if="tester.doubtedCount" class="text-warning">({{ $t('plugin_tests.admin_doubted', { count: tester.doubtedCount }) }})</span>
                                </td>
                                <td class="px-3 py-2 text-xs whitespace-nowrap">{{ lastSeen(tester) }}</td>
                                <td v-for="scenario in tester.scenarios" :key="scenario.key" class="px-1.5 py-2 text-center">
                                    <u-icon :name="statusIcon(scenario.status)" class="size-5" :class="statusClass(scenario.status)" :aria-label="$t(`plugin_tests.status_${scenario.status}`)" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <u-card v-if="selected">
                    <template #header>
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <div>
                                <p class="font-semibold">{{ selected.user.name }}</p>
                                <p class="text-xs text-muted">
                                    {{ selected.characters.map((c) => `${c.rsn}${c.proven ? ' ✓' : ''}`).join(', ') || $t('plugin_tests.no_characters') }}
                                </p>
                            </div>
                            <span class="text-xs text-muted">
                                {{ selected.since ? $t('plugin_tests.since', { time: formatTimestamp(selected.since) }) : $t('plugin_tests.since_always') }}
                                · {{ selected.testSet?.joined ? $t('plugin_tests.in_test_set') : $t('plugin_tests.not_in_test_set') }}
                            </span>
                        </div>
                    </template>

                    <div class="space-y-6">
                        <section>
                            <h3 class="font-semibold mb-2">{{ $t('plugin_tests.checklist_title') }}</h3>
                            <plugin-test-checklist :scenarios="selected.scenarioDetails" detailed />
                        </section>

                        <section>
                            <h3 class="font-semibold mb-2">{{ $t('plugin_tests.log_title', { count: selected.reportCount }) }}</h3>
                            <plugin-report-log :key="selected.user.id" :reports="selected.log" detailed />
                        </section>
                    </div>
                </u-card>
            </template>
        </div>
    </admin-layout>
</template>

<script setup>
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Components/AdminLayout.vue';
import PluginReportLog from '@/Components/PluginReportLog.vue';
import PluginTestChecklist from '@/Components/PluginTestChecklist.vue';
import { formatTimestamp } from '@/Support/audit';
import { pluginTestStatusClass as statusClass, pluginTestStatusIcon as statusIcon } from '@/Support/pluginTests';

const props = defineProps({
    mode: { type: String, required: true },
    testSet: { type: Object, default: null },
    testers: { type: Array, required: true },
    selected: { type: Object, default: null },
});

const scenarioKeys = computed(() => props.testers[0]?.scenarios.map((scenario) => scenario.key) ?? []);

function lastSeen(tester) {
    const latest = [tester.lastReportAt, tester.lastUsedAt].filter(Boolean).sort().at(-1);

    return latest ? formatTimestamp(latest) : '—';
}

function select(id) {
    router.get('/admin/plugin-tests', { tester: id }, {
        only: ['selected'],
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onError: (errors) => console.error(errors),
    });
}
</script>
