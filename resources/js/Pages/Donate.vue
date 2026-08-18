<template>
    <Head :title="resolved.title">
        <meta name="description" :content="resolved.description" />
        <link rel="canonical" :href="canonical" />
    </Head>

    <u-main>
        <u-page>
            <u-container class="max-w-3xl py-12">
                <div class="mb-10">
                    <h1 class="text-3xl font-bold text-highlighted">Support Us</h1>
                    <p class="text-muted mt-2">OSRS Events is free — and always will be. But hosting costs gold coins too.</p>
                </div>

                <p class="text-lg text-muted leading-relaxed mb-10">
                    Running this platform takes time and money. If you enjoy it and want to help keep it going, here are two ways
                    to show your support — one real, one very RuneScape.
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-10">
                    <u-card>
                        <template #header>
                            <span class="text-sm font-medium text-muted">Real money ☕</span>
                        </template>
                        <p class="text-muted leading-relaxed">
                            Ko-fi lets you send a small one-time tip (like buying someone a coffee). No subscription required — any
                            amount is greatly appreciated and goes directly towards server costs.
                        </p>
                        <template #footer>
                            <u-button href="https://ko-fi.com" target="_blank" color="warning" icon="i-lucide-coffee" label="Buy me a coffee on Ko-fi" />
                        </template>
                    </u-card>

                    <u-card>
                        <template #header>
                            <span class="text-sm font-medium text-muted">In-game currency 🪙</span>
                        </template>
                        <p class="text-muted leading-relaxed mb-2">
                            Prefer to tip in Gielinor's finest currency? We will genuinely accept it. No, it will not pay any real
                            bills. But it will warm our heart and give us a reason to log in.
                        </p>
                        <u-alert
                            color="neutral"
                            variant="subtle"
                            size="sm"
                            title="GP is accepted purely as a gesture of goodwill."
                            description="We cannot guarantee any response time — the developer may be AFK at Lumbridge."
                        />
                        <template #footer>
                            <u-button href="mailto:dev@absolit.nl?subject=GP Donation" color="neutral" variant="outline" icon="i-lucide-mail" label="Send GP donation email" />
                        </template>
                    </u-card>
                </div>

                <u-alert color="primary" variant="subtle" icon="i-lucide-sword" title="Thank you!">
                    <template #description>
                        Whether you donate or not, thank you for playing. Every player who joins a board and enjoys the game makes
                        this project worthwhile. See you in Gielinor! 🗡️
                    </template>
                </u-alert>
            </u-container>
        </u-page>
    </u-main>
</template>

<script setup>
import { useSeoData } from '@/Composables/useSeo';

const { resolved, canonical, Head } = useSeoData({
    title: 'Support OSRS Events',
    description: 'OSRS Events is free and carries no ads. Donations cover hosting costs so clans can keep running events at no charge.',
});
</script>
