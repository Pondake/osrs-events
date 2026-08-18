<template>
    <Head :title="resolved.title">
        <meta name="description" :content="resolved.description" />
        <link rel="canonical" :href="canonical" />
    </Head>

    <u-main>
        <u-page>
            <u-container class="max-w-3xl py-12 prose prose-sm dark:prose-invert max-w-none">
                <h1 class="text-3xl font-bold text-highlighted mb-1">Privacy Policy</h1>
                <p class="text-muted">We believe your data is yours. Here is exactly what we collect and why.</p>
                <p class="text-sm text-muted">Last updated: {{ lastUpdated }}</p>

                <h2>Introduction</h2>
                <p>
                    OSRS Events ("the Service") is a fan-made web application. We are committed to protecting your privacy and
                    being transparent about what little data we store. This page explains what information is collected when you
                    use the Service, how it is used, and your rights.
                </p>

                <h2>Data we collect</h2>
                <p>When you log in with Discord, we receive the following information from Discord's OAuth2 API:</p>
                <ul>
                    <li><strong>Discord User ID</strong> — Your unique Discord identifier. Used to link your account across sessions and to identify you within the game.</li>
                    <li><strong>Discord Username</strong> — Your Discord username at the time of login. Displayed on your profile and to other players on shared boards.</li>
                    <li><strong>Discord Avatar URL</strong> — A link to your Discord profile picture. Shown on your profile card and as your token on the game board.</li>
                    <li><strong>Display Name (optional)</strong> — A custom display name you may set yourself in your profile. Replaces your Discord username wherever your name appears.</li>
                    <li><strong>Game Progress</strong> — Your current tile position, completed tiles, and daily dice roll count on each board you participate in.</li>
                </ul>

                <h2>What we do NOT collect</h2>
                <p>We intentionally collect as little as possible. The following data is never collected:</p>
                <ul>
                    <li>Email addresses</li>
                    <li>Passwords</li>
                    <li>Discord messages or server memberships</li>
                    <li>Analytics, page views, or behavioural tracking</li>
                    <li>Advertising cookies or tracking pixels</li>
                    <li>Any data sold or shared with third parties for commercial purposes</li>
                </ul>

                <h2>How data is stored</h2>
                <p>
                    Your data is stored in a private PostgreSQL database. A session is kept in your browser to keep you logged in.
                    You can clear it at any time by logging out.
                </p>

                <h2>Third-party services</h2>
                <p>We use one external service as part of the login flow:</p>
                <p>
                    Discord is used solely for authentication. Once logged in, we do not send any further data to Discord. Discord
                    may log the OAuth2 authorisation request on their end. See
                    <a href="https://discord.com/privacy" target="_blank" rel="noopener">Discord's Privacy Policy</a>.
                </p>

                <h2>Your rights</h2>
                <p>
                    You have the right to access, correct, or delete the data we hold about you at any time. Logging out removes
                    your local session. To request permanent deletion of your account and all associated data, please contact us.
                </p>
                <p>
                    Send us an email with the subject "Account deletion request" and we will remove your account and all
                    associated data within a reasonable timeframe.
                    <u-button href="mailto:dev@absolit.nl?subject=Account deletion request" size="sm" color="primary" variant="outline" label="Send deletion request" class="not-prose mt-2" />
                </p>

                <h2>Changes to this policy</h2>
                <p>
                    We may update this Privacy Policy from time to time. Significant changes will be announced via our Discord
                    server. Continued use of the Service after changes are posted constitutes your acceptance of the updated
                    policy.
                </p>
            </u-container>
        </u-page>
    </u-main>
</template>

<script setup>
import { useSeoData } from '@/Composables/useSeo';

const { resolved, canonical, Head } = useSeoData({
    title: 'Privacy Policy',
    description:
        'How OSRS Events handles your data: what is stored from Discord login, what is never collected, and how to have your account removed.',
    noindex: false,
});

// Static rather than dynamic — a legal document's "last updated" date should
// change only when its content actually changes, not on every deploy.
const lastUpdated = 'August 2026';
</script>
