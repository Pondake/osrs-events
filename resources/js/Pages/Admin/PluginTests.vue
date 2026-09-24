<template>
    <Head :title="$t('settings.nav_admin_plugin_tests')" />

    <admin-layout current="plugin-tests" :title="$t('settings.nav_admin_plugin_tests')" :description="$t('plugin_tests.admin_subtitle')">
        <div class="space-y-4">
            <u-alert
                v-if="!event"
                color="warning"
                variant="subtle"
                icon="i-lucide-database"
                :description="$t('plugin_tests.admin_no_event', { title: eventTitle })"
            />
            <u-alert
                v-else-if="mode !== 'testing'"
                color="neutral"
                variant="subtle"
                icon="i-lucide-flask-conical"
                :description="$t('plugin_tests.admin_not_testing')"
            />

            <p v-if="event" class="text-sm">
                <a :href="event.url" class="text-primary hover:underline focus-visible:outline-2 focus-visible:outline-primary rounded">{{ event.title }}</a>
                · {{ $t('plugin_tests.admin_tester_count', { count: testers.length }) }}
            </p>

            <p v-if="event && !testers.length" class="text-sm text-muted">{{ $t('plugin_tests.admin_no_testers') }}</p>

            <div v-if="testers.length" class="overflow-x-auto rounded-lg ring ring-default">
                <table class="w-full text-sm">
                    <thead class="bg-elevated/50 text-left">
                        <tr>
                            <th class="px-3 py-2 font-medium">{{ $t('plugin_tests.admin_tester') }}</th>
                            <th v-for="scenario in scenarioKeys" :key="scenario" class="px-2 py-2 font-medium text-center whitespace-nowrap" :title="$t(`plugin_tests.scenario_${scenario}`)">
                                {{ $t(`plugin_tests.short_${scenario}`) }}
                            </th>
                            <th class="px-3 py-2 font-medium whitespace-nowrap">{{ $t('plugin_tests.admin_version') }}</th>
                            <th class="px-3 py-2 font-medium whitespace-nowrap">{{ $t('plugin_tests.admin_last_report') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="tester in testers"
                            :key="tester.user.id"
                            role="button"
                            tabindex="0"
                            class="border-t border-default hover:bg-elevated/50 focus-visible:outline-2 focus-visible:outline-primary"
                            :class="{ 'bg-elevated/40': selected === tester.user.id }"
                            @click="select(tester.user.id)"
                            @keydown.enter.prevent="select(tester.user.id)"
                            @keydown.space.prevent="select(tester.user.id)"
                        >
                            <td class="px-3 py-2">
                                <p class="font-medium">{{ tester.user.name }}</p>
                                <p class="text-xs text-muted">{{ tester.user.rsn ?? '—' }}</p>
                            </td>
                            <td v-for="scenario in tester.scenarios" :key="scenario.key" class="px-2 py-2 text-center">
                                <u-icon :name="statusIcon(scenario.status)" class="size-5" :class="statusClass(scenario.status)" :aria-label="$t(`plugin_tests.status_${scenario.status}`)" />
                            </td>
                            <td class="px-3 py-2 font-mono text-xs whitespace-nowrap">{{ tester.pluginVersion ?? '—' }}</td>
                            <td class="px-3 py-2 text-xs whitespace-nowrap">{{ tester.lastReportAt ? formatTimestamp(tester.lastReportAt) : '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <u-card v-if="current">
                <template #header>
                    <div class="flex items-center justify-between gap-2 flex-wrap">
                        <span class="font-semibold">{{ current.user.name }}</span>
                        <span class="text-xs text-muted">
                            {{ current.since ? $t('plugin_tests.since', { time: formatTimestamp(current.since) }) : $t('plugin_tests.not_joined') }}
                        </span>
                    </div>
                </template>
                <plugin-test-checklist :tester="current" detailed />
            </u-card>
        </div>
    </admin-layout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Components/AdminLayout.vue';
import PluginTestChecklist from '@/Components/PluginTestChecklist.vue';
import { formatTimestamp } from '@/Support/audit';

const props = defineProps({
    mode: { type: String, required: true },
    event: { type: Object, default: null },
    eventTitle: { type: String, required: true },
    testers: { type: Array, required: true },
});

const selected = ref(props.testers[0]?.user.id ?? null);
const current = computed(() => props.testers.find((tester) => tester.user.id === selected.value) ?? null);
const scenarioKeys = computed(() => props.testers[0]?.scenarios.map((scenario) => scenario.key) ?? []);

function select(id) {
    selected.value = id;
}

function statusIcon(status) {
    return { ok: 'i-lucide-circle-check', partial: 'i-lucide-circle-alert' }[status] ?? 'i-lucide-circle-dashed';
}

function statusClass(status) {
    return { ok: 'text-success', partial: 'text-warning' }[status] ?? 'text-muted';
}
</script>
