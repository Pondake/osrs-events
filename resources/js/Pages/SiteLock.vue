<template>
    <Head :title="$t('lock.title')">
        <!-- The one page on the site that must never be indexed: it is a
             door, and a search result pointing at it says the site exists
             while telling a visitor nothing they can act on. -->
        <meta name="robots" content="noindex, nofollow" />
    </Head>

    <u-main>
        <u-container class="py-20 sm:py-28">
            <div class="max-w-sm mx-auto text-center">
                <app-logo class="!size-16 mx-auto mb-6" />

                <h1 class="text-2xl font-bold text-highlighted">{{ heading }}</h1>
                <p class="text-sm text-muted mt-2">{{ body }}</p>

                <!-- Full lockdown refuses the shared password outright — see
                     SiteLockController::unlock(). Offering the form anyway
                     would look like it works right up until it errors, so it
                     isn't rendered at all in this state. -->
                <form v-if="!fullLockdown" class="mt-8 space-y-3 text-left" @submit.prevent="submit">
                    <u-form-field :error="form.errors.password">
                        <u-input
                            v-model="form.password"
                            type="password"
                            autocomplete="current-password"
                            :placeholder="$t('lock.password_placeholder')"
                            size="lg"
                            class="w-full"
                            autofocus
                        />
                    </u-form-field>

                    <u-button
                        type="submit"
                        color="primary"
                        size="lg"
                        block
                        :loading="form.processing"
                        :label="$t('lock.submit')"
                    />
                </form>

                <!-- The other way in. An admin should not have to be told the
                     shared password to reach a site they run. -->
                <p class="text-xs text-muted" :class="fullLockdown ? 'mt-4' : 'mt-6'">
                    {{ $t('lock.admin_hint') }}
                    <a href="/login" class="text-primary hover:underline">{{ $t('common.login') }}</a>
                </p>
            </div>
        </u-container>
    </u-main>
</template>

<script setup>
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import AppLogo from '@/Components/AppLogo.vue';

const props = defineProps({
    fullLockdown: { type: Boolean, default: false },
});

const heading = computed(() => (props.fullLockdown ? trans('lock.full_lockdown_heading') : trans('lock.heading')));
const body = computed(() => (props.fullLockdown ? trans('lock.full_lockdown_body') : trans('lock.body')));

const form = useForm({ password: '' });

function submit() {
    form.post('/locked', {
        // Cleared whatever the outcome: a wrong password left in the box
        // invites the same wrong password again.
        onFinish: () => form.reset('password'),
    });
}
</script>
