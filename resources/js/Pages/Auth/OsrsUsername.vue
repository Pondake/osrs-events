<template>
    <Head :title="$t('auth.osrs_title')" />

    <u-main>
        <u-page>
            <u-container class="max-w-md py-20">
                <u-card>
                    <template #header>
                        <h1 class="text-2xl font-bold text-highlighted">{{ $t('auth.osrs_title') }}</h1>
                        <p class="text-muted text-sm mt-1">{{ $t('auth.osrs_subtitle') }}</p>
                    </template>

                    <form class="space-y-4" @submit.prevent="submit">
                        <u-form-field
                            :label="$t('auth.field_osrs_username')"
                            :description="$t('auth.field_osrs_username_desc')"
                            :error="form.errors.osrs_username"
                            required
                        >
                            <osrs-username-field v-model="form.osrs_username" />
                        </u-form-field>


                        <!-- Not an error on the field: the name is valid, it
                             is simply unproven, and a red mark on correct
                             input teaches people to dismiss the mark. A
                             neutral note, at the moment the question comes
                             up. -->
                        <u-alert
                            v-if="proofMatters"
                            color="neutral"
                            variant="subtle"
                            icon="i-lucide-shield-alert"
                            :description="$t('auth.osrs_proof_notice')"
                        />

                        <u-button type="submit" color="primary" block :loading="form.processing" :label="$t('common.continue')" />
                    </form>

                    <template #footer>
                        <p class="text-xs text-muted">{{ $t('auth.osrs_change_later') }}</p>
                    </template>
                </u-card>
            </u-container>
        </u-page>
    </u-main>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import OsrsUsernameField from '@/Components/OsrsUsernameField.vue';

const props = defineProps({
    suggestion: { type: String, default: '' },
    // Whether an unproven name costs anything yet — see the controller.
    proofMatters: { type: Boolean, default: false },
});

// Prefilled with whatever we already know them by — often the same name, and
// a wrong guess costs one edit where a right one costs nothing.
const form = useForm({ osrs_username: props.suggestion ?? '' });

function submit() {
    form.post('/welcome/osrs-username');
}
</script>
