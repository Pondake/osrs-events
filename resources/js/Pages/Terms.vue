<template>
    <Head :title="resolved.title">
        <meta name="description" :content="resolved.description" />
        <link rel="canonical" :href="canonical" />
    </Head>

    <u-main>
        <u-page>
            <u-container class="max-w-3xl py-12 prose prose-sm dark:prose-invert max-w-none">
                <h1 class="text-3xl font-bold text-highlighted mb-1">Terms of Service</h1>
                <p class="text-muted">Please read these terms before using OSRS Events.</p>
                <p class="text-sm text-muted">Last updated: {{ lastUpdated }}</p>

                <h2>Acceptance of Terms</h2>
                <p>
                    By accessing or using OSRS Events ("the Service"), you agree to be bound by these Terms of Service. If you do
                    not agree with any part of these terms, you may not use the Service.
                </p>

                <h2>About the Service</h2>
                <p>
                    OSRS Events is a free, fan-made web application that allows Old School RuneScape players to participate in
                    themed events and Snakes &amp; Ladders competitions. The Service is provided as-is, for entertainment
                    purposes, and is not affiliated with Jagex Ltd.
                </p>

                <h2>User Accounts</h2>
                <p>To use the Service you must authenticate via a valid Discord account. By doing so you agree that:</p>
                <ul>
                    <li>You have permission to use the Discord account you log in with.</li>
                    <li>You will only maintain one account per person.</li>
                    <li>The information provided via Discord (username, avatar) is accurate to your Discord account.</li>
                </ul>

                <h2>Acceptable Use</h2>
                <p>You agree not to use the Service in any way that:</p>
                <ul>
                    <li>Harasses, threatens, or harms other users.</li>
                    <li>Scrapes, crawls, or programmatically harvests data without permission.</li>
                    <li>Attempts to exploit bugs, cheat the game mechanics, or interfere with the Service.</li>
                    <li>Impersonates any person, organisation, or Jagex staff.</li>
                </ul>

                <h2>Intellectual Property</h2>
                <p>
                    The code and design of this platform are the work of its creators. Old School RuneScape content (including
                    task names, item icons and game imagery) belongs to Jagex Ltd. and is used here under fan content guidelines.
                </p>

                <h2>Jagex Fan Content Policy</h2>
                <p>
                    This site is not approved or endorsed by Jagex. Old School RuneScape and the RuneScape logo are trademarks of
                    Jagex Ltd. Icons sourced from the OSRS Wiki are used under the Creative Commons Attribution-ShareAlike licence.
                </p>

                <h2>Disclaimers</h2>
                <p>
                    The Service is provided "as is" without any warranties, express or implied. We do not guarantee uptime, data
                    integrity, or continued availability of the Service. We reserve the right to change, suspend, or discontinue
                    the Service at any time without notice.
                </p>

                <h2>Termination</h2>
                <p>
                    We reserve the right to suspend or terminate your access to the Service at any time, without prior notice, if
                    we determine that you have violated these Terms or have behaved in a way that is harmful to other users or the
                    integrity of the platform.
                </p>

                <h2>Changes to These Terms</h2>
                <p>
                    We may revise these Terms of Service at any time. Changes will be posted on this page with an updated date.
                    Continued use of the Service after changes are published constitutes acceptance of the revised terms.
                </p>
            </u-container>
        </u-page>
    </u-main>
</template>

<script setup>
import { useSeoData } from '@/Composables/useSeo';

const { resolved, canonical, Head } = useSeoData({
    title: 'Terms of Service',
    description:
        'The terms for using OSRS Events, a free fan-made Old School RuneScape clan event platform that is not affiliated with Jagex.',
});

const lastUpdated = 'August 2026';
</script>
