<template>
    <div class="space-y-5 py-2">
        <!-- How claims are handled, apart from what the game is: the Format
             step had grown to a page and a half once lockout and reveal
             joined it. -->
        <u-form-field :description="$t(isBingo ? 'bingo.requires_approval_desc' : 'board.requires_approval_desc')">
            <u-switch v-model="form.requires_approval" :label="$t(isBingo ? 'bingo.requires_approval' : 'board.requires_approval')" />
        </u-form-field>

        <u-form-field v-if="form.requires_approval" :description="$t('board.trust_runelite_desc')">
            <u-switch v-model="form.trust_runelite_completions" :label="$t('board.trust_runelite')" />
        </u-form-field>

        <template v-if="isBingo">
            <u-separator />

            <!-- First player or team to a square keeps it. -->
            <u-form-field :description="$t('bingo.lockout_desc')" :error="form.errors.lockout">
                <u-switch v-model="form.lockout" :label="$t('bingo.lockout')" />
            </u-form-field>

            <!-- Squares drawn one at a time, so nobody farms ahead. -->
            <u-form-field :description="$t('bingo.reveal_desc')" :error="form.errors.reveal">
                <u-switch v-model="form.reveal" :label="$t('bingo.reveal')" />
            </u-form-field>

            <div v-if="form.reveal" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <u-form-field :label="$t('bingo.reveal_limit')" :description="$t('bingo.reveal_limit_desc')" :error="form.errors.reveal_limit">
                    <u-input
                        v-model.number="form.reveal_limit"
                        type="number"
                        min="1"
                        max="100"
                        :placeholder="$t('bingo.reveal_limit_placeholder')"
                        class="w-full sm:max-w-40"
                    />
                </u-form-field>

                <u-form-field :label="$t('bingo.reveal_every')" :description="$t('bingo.reveal_every_desc')" :error="form.errors.reveal_every_minutes">
                    <u-input
                        v-model.number="form.reveal_every_minutes"
                        type="number"
                        min="1"
                        max="10080"
                        :placeholder="$t('bingo.reveal_every_placeholder')"
                        class="w-full sm:max-w-40"
                    />
                </u-form-field>
            </div>
        </template>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    form: { type: Object, required: true },
});

const isBingo = computed(() => props.form.type === 'BINGO');
</script>
