<template>
    <seo-head :options="seo" />

    <u-main>
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-6 lg:py-10 space-y-4">
            <!-- The title band is a panel like everything else here, not a
                 hero. A hero is a page's opening argument; this page is
                 answering a question somebody already has. -->
            <header class="rounded-lg ring ring-default bg-elevated/50 px-5 py-5 sm:px-6 sm:py-6">
                <div class="sm:flex sm:items-end sm:justify-between sm:gap-6">
                    <div class="min-w-0">
                        <h1 class="font-bold text-highlighted">{{ $t('about.title') }}</h1>
                        <p class="mt-1.5 text-muted leading-relaxed max-w-2xl">{{ $t('about.subtitle') }}</p>
                    </div>

                    <div class="flex flex-wrap gap-2 mt-4 sm:mt-0 shrink-0">
                        <u-button to="/events" size="sm" color="primary" icon="i-lucide-swords" :label="$t('about.cta_events')" />
                        <u-button
                            to="/osrs-clan-events"
                            size="sm"
                            color="neutral"
                            variant="outline"
                            trailing-icon="i-lucide-arrow-right"
                            :label="$t('about.cta_guides')"
                        />
                    </div>
                </div>
            </header>

            <!-- One panel with hairlines through it rather than six cards in a
                 grid. Six bordered boxes read as six things to choose between,
                 which is what the CMS `features` block drew — it was built for
                 a marketing page. These are six facts about one thing, so they
                 share a frame and the gaps do the separating. -->
            <section class="rounded-lg ring ring-default bg-default overflow-hidden">
                <h2 :class="titleBar">
                    <span :class="titleBarLabel">{{ $t('about.offer_title') }}</span>
                </h2>

                <!-- The hairlines are this grid's own background showing
                     through a 1px gap. Drawn per cell with `divide-*` they
                     would follow DOM order and put a line down the left of
                     every cell including the first in each row, differently at
                     each breakpoint. -->
                <dl class="grid sm:grid-cols-2 lg:grid-cols-3 gap-px">
                    <div v-for="feature in features" :key="feature.title" class="bg-elevated/50 p-4 sm:p-5">
                        <dt class="flex items-center gap-2 font-semibold text-highlighted">
                            <u-icon :name="feature.icon" class="size-4 text-primary shrink-0" />
                            {{ feature.title }}
                        </dt>
                        <dd class="mt-1.5 text-sm text-muted leading-relaxed">{{ feature.description }}</dd>
                    </div>
                </dl>
            </section>

            <!-- Privacy across the full width, then the two short answers
                 beside each other. It was a tall left column first, which
                 stretched to the height of the two stacked beside it and left
                 a hand's width of nothing between its paragraph and its
                 button — the same hollow this rebuild is getting rid of. -->
            <section class="rounded-lg ring ring-default bg-elevated/50 overflow-hidden">
                <h2 :class="titleBar">
                    <u-icon name="i-lucide-shield-check" class="size-4 text-primary shrink-0" />
                    <span :class="titleBarLabel">{{ $t('about.privacy_title') }}</span>
                </h2>

                <div class="p-5">
                    <p class="text-sm text-muted leading-relaxed">{{ $t('about.privacy_body') }}</p>

                    <u-button
                        to="/privacy"
                        size="sm"
                        color="neutral"
                        variant="outline"
                        trailing-icon="i-lucide-arrow-right"
                        class="mt-4"
                        :label="$t('about.privacy_cta')"
                    />
                </div>
            </section>

            <!-- One column when there is no Discord invite to show, since a
                 single half-width panel beside nothing reads as a panel that
                 failed to load. -->
            <div class="grid gap-4" :class="discordInviteUrl ? 'sm:grid-cols-2' : 'grid-cols-1'">
                <section class="rounded-lg ring ring-default bg-elevated/50 overflow-hidden">
                    <h2 :class="titleBar">
                        <u-icon name="i-lucide-heart" class="size-4 text-primary shrink-0" />
                        <span :class="titleBarLabel">{{ $t('about.free_title') }}</span>
                    </h2>

                    <div class="p-5">
                        <p class="text-sm text-muted leading-relaxed">{{ $t('about.free_body') }}</p>

                        <!-- A literal rather than the Ko-fi site setting, which
                             is the call the seeded page made for a reason that
                             outlived it: this is the project's own donation
                             link, not a per-environment one. -->
                        <u-button
                            href="https://ko-fi.com/pondake"
                            target="_blank"
                            rel="noopener noreferrer"
                            size="sm"
                            color="warning"
                            variant="outline"
                            icon="i-lucide-coffee"
                            class="mt-4"
                            :label="$t('about.donate_cta')"
                        />
                    </div>
                </section>

                <!-- Rendered only when an admin has actually set an invite.
                     This panel used to publish a personal email address, and
                     what replaced it is the Discord server — so with no invite
                     configured there is no route to offer here, and a "get in
                     touch" heading above nothing is worse than silence. The
                     address has not gone from the site: /privacy keeps it,
                     deliberately, as the way back for somebody who has lost
                     access to their account and so cannot press the delete
                     button in Settings → Account. -->
                <section v-if="discordInviteUrl" class="rounded-lg ring ring-default bg-elevated/50 overflow-hidden">
                    <h2 :class="titleBar">
                        <u-icon name="i-simple-icons-discord" class="size-4 text-primary shrink-0" />
                        <span :class="titleBarLabel">{{ $t('about.support_title') }}</span>
                    </h2>

                    <div class="p-5">
                        <p class="text-sm text-muted leading-relaxed">{{ $t('about.support_body') }}</p>

                        <!-- /discord, not the invite itself — the same call
                             Home.vue makes. The short link resolves the invite
                             per request from site settings, so replacing a
                             revoked one is a form field rather than a deploy,
                             and this template never has to be touched for it.

                             external, and it is not decoration. Nuxt UI's ULink treats a
                             protocol-less href as internal and renders an
                             Inertia <Link>, which turns the click into an XHR
                             visit — and /discord answers 302 to another
                             origin, so the browser refuses the redirect and
                             the button does nothing at all. `external` makes
                             it a plain <a> and a real navigation. Measured on
                             2026-09-07: without it the click produced a CORS
                             error and stayed on the page. -->
                        <u-button
                            href="/discord"
                            external
                            size="sm"
                            color="neutral"
                            variant="outline"
                            icon="i-simple-icons-discord"
                            class="mt-4"
                            :label="$t('about.support_discord')"
                        />
                    </div>
                </section>
            </div>

            <!-- A strip, not an alert box. The footer carries this line on
                 every page already, so the wording here is the same string
                 rather than a second telling of it — two versions of a
                 required attribution is how one of them ends up wrong. -->
            <p class="flex items-start gap-2.5 rounded-lg ring ring-default bg-elevated/50 px-5 py-4 text-xs text-dimmed leading-relaxed">
                <u-icon name="i-lucide-alert-triangle" class="size-4 text-warning shrink-0 mt-px" />
                <span>
                    <span class="font-semibold text-muted">{{ $t('about.disclaimer_title') }}</span>
                    — {{ $t('common.not_affiliated') }}
                </span>
            </p>
        </div>
    </u-main>
</template>

<script setup>
import { computed } from 'vue';
import { trans } from 'laravel-vue-i18n';
import SeoHead from '@/Components/SeoHead.vue';
import { useCurrentPage } from '@/Support/pageState';

/**
 * `/about`, a component since 2026-09-07 — see LandingController::about() for
 * why it stopped being a CMS document.
 *
 * Its own shell rather than GuideLayout: that layout's sidebar is a table of
 * contents and a list of other guides, and this page is neither long enough to
 * need jumping around nor a guide to sit beside them. What it borrows instead
 * is the panel bevel every landing page wears — see .landing-page in app.css,
 * which reaches any rounded-lg carrying ring-default. That is why the page is
 * built out of titled frames rather than headings and air: the frames do the
 * work the whitespace used to, and it reads as an interface rather than as a
 * brochure.
 */
defineProps({
    // { icon, title, description }[] — built server-side so the copy stays in
    // lang/en.json and the order is stated in one place.
    features: { type: Array, required: true },
});

const page = useCurrentPage();

// Null until an admin sets one, and on every fresh install. Read only to
// decide whether the support panel exists at all — the button itself goes to
// /discord, which resolves the invite server-side.
const discordInviteUrl = computed(() => page.value.props?.site?.discordInviteUrl ?? null);

/**
 * A panel's title bar.
 *
 * The size lives on a `<span>` INSIDE the heading rather than on the heading
 * itself, and that is not a style preference. app.css restores `h1`-`h3` sizes
 * with plain element rules, because Tailwind v4's preflight resets them to
 * `inherit` — and those rules are unlayered while every utility class sits in
 * a cascade layer, so `h2 { font-size: var(--text-2xl) }` beats
 * `class="text-sm"` however specific the utility looks. Every `text-*` on an
 * h2 in this codebase is silently doing nothing; measured here, where four
 * headings asked for three different sizes and all rendered at 24px. On a span
 * it applies, and the decorative heading font still inherits, which is the
 * half worth keeping.
 */
const titleBar = 'flex items-center gap-2 bg-elevated px-5 py-2.5 border-b border-default';

const titleBarLabel = 'text-sm uppercase tracking-wide font-semibold text-highlighted';

const seo = {
    title: trans('seo.about_title'),
    description: trans('seo.about_desc'),
};
</script>
