<template>
    <seo-head :options="seo" />

    <u-main>
        <u-container class="py-16 sm:py-24">
            <div class="max-w-xl mx-auto">
                <!-- The panel. `landing-page` (set by AppRoot for every page
                     in LANDING_PAGES, this one included) turns any
                     rounded-xl + border-default box into the OSRS interface
                     bevel — so the styling is inherited rather than
                     re-invented here. See .landing-page in app.css. -->
                <!-- Centred from `sm` up, left-aligned below it — including
                     the logo and the button row, which are flex and so are
                     untouched by the rule that does it (see the max-width
                     639px block in app.css). Getting only the text and not
                     the rest is what a plain `mx-auto` produced: a centred
                     logo over a left-aligned 404, which reads as a mistake
                     rather than as either alignment. -->
                <div class="rounded-xl border border-default px-6 py-10 sm:px-10 sm:py-14 text-center">
                    <app-logo class="!size-14 sm:mx-auto" />

                    <!-- The status code, at display size. Cinzel Decorative
                         via .osrs-title rather than .osrs-game-font: the
                         pixel face is pinned to 24px and only legal at
                         integer multiples, so it cannot be a hero numeral.
                         aria-hidden because the sentence below already says
                         what happened — read aloud, a bare "404" between the
                         logo and the heading is noise. -->
                    <p
                        class="osrs-title mt-6 text-7xl sm:text-8xl font-bold leading-none tracking-tight text-primary"
                        aria-hidden="true"
                    >
                        {{ status }}
                    </p>

                    <h1 class="mt-6 text-2xl sm:text-3xl font-bold text-highlighted">{{ copy.heading }}</h1>
                    <p class="mt-3 text-muted">{{ copy.body }}</p>

                    <div class="mt-8 flex flex-wrap items-center justify-start sm:justify-center gap-3">
                        <u-button
                            to="/"
                            size="lg"
                            color="primary"
                            icon="i-lucide-house"
                            :label="$t('errors.back_home')"
                        />
                        <u-button
                            to="/events"
                            size="lg"
                            color="neutral"
                            variant="subtle"
                            icon="i-lucide-layout-grid"
                            :label="$t('errors.browse_events')"
                        />
                    </div>

                    <!-- Only offered where it can work: a visitor who arrived
                         on this URL from a search result has nothing behind
                         them, and a "Go back" that does nothing is worse than
                         no button. history.length is client-only, hence the
                         mounted flag — rendering it server-side would mean a
                         hydration mismatch on the one row that changes. -->
                    <p v-if="canGoBack" class="mt-6 text-sm">
                        <button type="button" class="text-primary hover:underline" @click="goBack">
                            {{ $t('errors.go_back') }}
                        </button>
                    </p>
                </div>

                <!-- The usual suspects. Somebody who mistyped a guide URL is
                     one click from the right one instead of one click from
                     the home page and a second from here. -->
                <p class="mt-8 text-center text-sm text-muted">
                    {{ $t('errors.try_instead') }}
                    <a href="/osrs-snakes-and-ladders" class="text-primary hover:underline">{{ $t('nav.snakes') }}</a>
                    <span aria-hidden="true"> · </span>
                    <a href="/osrs-clan-events" class="text-primary hover:underline">{{ $t('nav.clan_events') }}</a>
                    <span aria-hidden="true"> · </span>
                    <a href="/about" class="text-primary hover:underline">{{ $t('nav.about') }}</a>
                </p>
            </div>
        </u-container>
    </u-main>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { trans } from 'laravel-vue-i18n';
import SeoHead from '@/Components/SeoHead.vue';
import AppLogo from '@/Components/AppLogo.vue';

const props = defineProps({
    status: { type: Number, required: true },
});

/**
 * One page for every HTTP status the app hands back, because they are the
 * same screen with a different sentence — a code, an explanation, and a way
 * out. Adding a status means adding two translation keys, not a component.
 *
 * `trans()` rather than `$t()`: this is script scope, where the template-only
 * global does not exist (see CLAUDE.md's i18n section).
 */
const COPY = {
    403: () => ({ heading: trans('errors.forbidden_heading'), body: trans('errors.forbidden_body') }),
    404: () => ({ heading: trans('errors.not_found_heading'), body: trans('errors.not_found_body') }),
    419: () => ({ heading: trans('errors.expired_heading'), body: trans('errors.expired_body') }),
    429: () => ({ heading: trans('errors.throttled_heading'), body: trans('errors.throttled_body') }),
    500: () => ({ heading: trans('errors.server_heading'), body: trans('errors.server_body') }),
    503: () => ({ heading: trans('errors.maintenance_heading'), body: trans('errors.maintenance_body') }),
};

const copy = computed(
    () => (COPY[props.status] ?? (() => ({
        heading: trans('errors.unknown_heading'),
        body: trans('errors.generic_with_status', { status: props.status }),
    })))(),
);

// noindex on every one of them. A 404 already carries the status header, but
// the other five are real pages as far as a crawler that followed a link is
// concerned, and none of them is worth a search result.
const seo = computed(() => ({
    title: copy.value.heading,
    description: copy.value.body,
    noindex: true,
}));

const canGoBack = ref(false);

onMounted(() => {
    canGoBack.value = window.history.length > 1;
});

function goBack() {
    window.history.back();
}
</script>
