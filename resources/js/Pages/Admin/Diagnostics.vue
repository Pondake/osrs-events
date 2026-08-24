<template>
    <Head :title="$t('diagnostics.title')" />

    <admin-layout current="diagnostics" :title="$t('diagnostics.title')" :description="$t('diagnostics.description')">
        <!-- The summary exists so the page answers its own question before
             anyone reads a single row. Almost every visit here is somebody
             asking "is it me or is it broken", and making them scan five
             cards for one red line is the page failing at its only job. -->
        <div class="mb-6 flex items-center gap-3 flex-wrap">
            <u-badge
                :color="overall === 'fail' ? 'error' : overall === 'warn' ? 'warning' : 'success'"
                variant="subtle"
                size="lg"
                :icon="overall === 'fail' ? 'i-lucide-circle-x' : overall === 'warn' ? 'i-lucide-triangle-alert' : 'i-lucide-circle-check'"
                :label="overallLabel"
            />

            <u-button
                color="neutral"
                variant="outline"
                size="sm"
                icon="i-lucide-refresh-cw"
                :loading="refreshing"
                :label="$t('diagnostics.refresh')"
                @click="refresh"
            />
        </div>

        <div class="space-y-6">
            <u-card v-for="group in groups" :key="group.key">
                <template #header>
                    <div class="flex items-start justify-between gap-4 flex-wrap">
                        <div class="flex items-start gap-3 min-w-0">
                            <u-icon :name="group.icon" class="size-5 shrink-0 mt-0.5" :class="toneText[group.status]" />
                            <div class="min-w-0">
                                <p class="font-semibold text-highlighted">{{ group.label }}</p>
                                <p class="text-sm text-muted">{{ group.description }}</p>
                            </div>
                        </div>

                        <u-badge
                            :color="badgeColor[group.status]"
                            variant="subtle"
                            size="sm"
                            :label="$t(`diagnostics.status_${group.status}`)"
                        />
                    </div>
                </template>

                <!-- One row per check, and the row carries its own fix. A
                     failure with no next step is the same dead end as no
                     message at all — each of these has a completely different
                     remedy and the page is the only place that knows which. -->
                <div class="divide-y divide-default -my-2">
                    <div v-for="(check, index) in group.checks" :key="index" class="py-3 flex items-start gap-3">
                        <u-icon :name="toneIcon[check.status]" class="size-4 shrink-0 mt-0.5" :class="toneText[check.status]" />

                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-highlighted">{{ check.label }}</p>
                            <p class="text-sm text-muted break-words">{{ check.detail }}</p>
                            <p v-if="check.fix" class="text-sm mt-1" :class="toneText[check.status]">
                                {{ check.fix }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Per-group actions, under the checks they belong to rather
                     than in one toolbar: the button that proves push works is
                     only meaningful next to the push checks. -->
                <template v-if="group.key === 'push'">
                    <div class="mt-4 pt-4 border-t border-default space-y-3">
                        <p class="text-sm text-muted">{{ $t('diagnostics.test_push_desc') }}</p>

                        <div class="flex items-end gap-2 flex-wrap">
                            <client-only>
                                <u-form-field :label="$t('diagnostics.test_push_category')" class="min-w-56">
                                    <u-select v-model="pushCategory" :items="categoryOptions" class="w-full" />
                                </u-form-field>
                            </client-only>

                            <u-button
                                color="primary"
                                size="sm"
                                icon="i-lucide-send"
                                :loading="sending === 'push'"
                                :label="$t('diagnostics.test_push')"
                                @click="testPush"
                            />
                        </div>

                        <p v-if="! myDevices.length" class="text-sm text-warning">
                            {{ $t('diagnostics.test_push_no_devices') }}
                        </p>
                        <p v-else class="text-sm text-muted">
                            {{ $t('diagnostics.test_push_targets', { devices: deviceSummary }) }}
                        </p>
                    </div>
                </template>

                <template v-if="group.key === 'mail'">
                    <div class="mt-4 pt-4 border-t border-default space-y-3">
                        <p class="text-sm text-muted">{{ $t('diagnostics.test_mail_desc') }}</p>

                        <u-button
                            color="primary"
                            variant="outline"
                            size="sm"
                            icon="i-lucide-mail"
                            :loading="sending === 'mail'"
                            :label="$t('diagnostics.test_mail')"
                            @click="testMail"
                        />
                    </div>
                </template>

                <template v-if="group.key === 'wom'">
                    <div class="mt-4 pt-4 border-t border-default space-y-3">
                        <p class="text-sm text-muted">{{ $t('diagnostics.wom_check_desc') }}</p>

                        <form class="flex items-end gap-2 flex-wrap" @submit.prevent="checkWom">
                            <u-form-field :label="$t('diagnostics.wom_check_username')" :error="womForm.errors.username">
                                <u-input v-model="womForm.username" :placeholder="$t('diagnostics.wom_check_placeholder')" class="w-56" />
                            </u-form-field>

                            <u-button
                                type="submit"
                                color="primary"
                                variant="outline"
                                size="sm"
                                icon="i-lucide-search"
                                :loading="womForm.processing"
                                :label="$t('diagnostics.wom_check')"
                            />
                        </form>
                    </div>
                </template>

                <template v-if="group.key === 'schedule'">
                    <div class="mt-4 pt-4 border-t border-default space-y-3">
                        <p class="text-sm text-muted">{{ $t('diagnostics.sweep_desc') }}</p>

                        <u-button
                            color="neutral"
                            variant="outline"
                            size="sm"
                            icon="i-lucide-play"
                            :loading="sending === 'sweep'"
                            :label="$t('diagnostics.sweep_run')"
                            @click="runSweep"
                        />

                        <!-- Command output verbatim, in a scroll box. Parsing
                             it into something prettier would mean deciding
                             which lines matter, and the reason to look at a
                             dry run is the line nobody predicted. -->
                        <pre
                            v-if="sweepOutput"
                            class="text-xs bg-elevated rounded-md p-3 overflow-x-auto max-h-72 overflow-y-auto whitespace-pre"
                        >{{ sweepOutput }}</pre>
                    </div>
                </template>
            </u-card>
        </div>
    </admin-layout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import AdminLayout from '@/Components/AdminLayout.vue';
import ClientOnly from '@/Components/ClientOnly.vue';

const props = defineProps({
    groups: { type: Array, required: true },
    categories: { type: Array, default: () => [] },
    myDevices: { type: Array, default: () => [] },
});

const page = usePage();

const toneIcon = {
    ok: 'i-lucide-circle-check',
    warn: 'i-lucide-triangle-alert',
    fail: 'i-lucide-circle-x',
    info: 'i-lucide-info',
};

const toneText = {
    ok: 'text-success',
    warn: 'text-warning',
    fail: 'text-error',
    info: 'text-muted',
};

const badgeColor = {
    ok: 'success',
    warn: 'warning',
    fail: 'error',
    info: 'neutral',
};

/**
 * The page's own verdict, which is the worst verdict any group reached.
 * `info` never counts — a page of facts with nothing checked is not passing.
 */
const overall = computed(() => {
    const statuses = props.groups.map((group) => group.status);

    if (statuses.includes('fail')) return 'fail';
    if (statuses.includes('warn')) return 'warn';

    return 'ok';
});

const overallLabel = computed(() => {
    const failing = props.groups.filter((group) => group.status === 'fail').length;
    const warning = props.groups.filter((group) => group.status === 'warn').length;

    if (failing > 0) return trans('diagnostics.overall_fail', { count: failing });
    if (warning > 0) return trans('diagnostics.overall_warn', { count: warning });

    return trans('diagnostics.overall_ok');
});

const categoryOptions = computed(() =>
    props.categories.map((category) => ({ label: category.label, value: category.key })),
);

const pushCategory = ref(props.categories[0]?.key ?? 'claim_reviewed');

const deviceSummary = computed(() =>
    props.myDevices
        .filter((device) => ! device.expired)
        .map((device) => `${device.label} (${device.fingerprint})`)
        .join(', '),
);

const refreshing = ref(false);
const sending = ref(null);

function refresh() {
    refreshing.value = true;
    router.reload({
        only: ['groups', 'myDevices'],
        onError: (errors) => console.error(errors),
        onFinish: () => (refreshing.value = false),
    });
}

function post(url, data, key) {
    sending.value = key;

    router.post(url, data, {
        preserveScroll: true,
        // The checks are read from the same request that ran the action, so a
        // test that fixes nothing still shows the state it left behind.
        onError: (errors) => console.error(errors),
        onFinish: () => (sending.value = null),
    });
}

function testPush() {
    post('/admin/diagnostics/push', { category: pushCategory.value }, 'push');
}

function testMail() {
    post('/admin/diagnostics/mail', {}, 'mail');
}

function runSweep() {
    post('/admin/diagnostics/sweep', {}, 'sweep');
}

const womForm = useForm({ username: '' });

function checkWom() {
    womForm.post('/admin/diagnostics/wom', {
        preserveScroll: true,
        onError: (errors) => console.error(errors),
    });
}

// Flashed by the sweep action rather than returned as a page prop — it is a
// one-off payload from one action to one page, the same shape as lastRoll.
const sweepOutput = computed(() => page.props?.flash?.sweepOutput ?? null);
</script>
