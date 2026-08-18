<template>
    <div class="flex flex-col items-center gap-3">
        <button
            type="button"
            :disabled="!canRoll"
            class="group relative focus:outline-none"
            :aria-label="canRoll ? $t('dice.roll') : $t('dice.cannot_roll')"
            @click="canRoll && emit('roll')"
        >
            <svg
                viewBox="0 0 100 100"
                class="w-20 h-20 select-none transition-transform duration-150"
                :class="{
                    'dice-rolling': rolling,
                    'opacity-40 cursor-not-allowed': !canRoll,
                    'cursor-pointer hover:scale-110 active:scale-95': canRoll,
                }"
            >
                <rect x="5" y="5" width="90" height="90" rx="15" ry="15" class="fill-primary stroke-primary" stroke-width="3" />
                <circle v-for="(dot, i) in dots" :key="i" :cx="dot.cx" :cy="dot.cy" r="7" class="fill-white" />
            </svg>

            <div v-if="rolling" class="absolute inset-0 flex items-center justify-center">
                <u-icon name="i-lucide-loader-circle" class="size-8 text-white animate-spin" />
            </div>
        </button>

        <div class="text-center">
            <p v-if="lastRoll && !isLimited" class="text-2xl font-bold text-primary osrs-font">🎲 {{ lastRoll }}</p>
            <p v-else-if="!isLimited" class="text-muted text-sm">{{ $t('dice.roll') }}</p>

            <div v-if="rollLimit !== null" class="mt-1">
                <u-badge :color="isLimited ? 'error' : 'neutral'" variant="subtle" size="sm">
                    <u-icon name="i-lucide-dice-6" class="size-3 mr-1" />
                    {{ $t('dice.rolls_today', { used: rollsToday, limit: rollLimit }) }}
                </u-badge>
            </div>
        </div>

        <div v-if="isLimited" class="w-full rounded-lg border border-warning/40 bg-warning/10 px-3 py-2.5 text-center">
            <u-icon name="i-lucide-clock-4" class="size-5 text-warning mx-auto mb-1" />
            <p class="text-sm font-semibold text-warning">{{ $t('dice.rolls_used_title') }}</p>
            <p class="text-xs text-warning/80 mt-0.5 leading-snug">{{ $t('dice.rolls_used_desc') }}</p>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    rolling: { type: Boolean, default: false },
    lastRoll: { type: Number, default: null },
    rollsToday: { type: Number, default: 0 },
    rollLimit: { type: Number, default: null },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['roll']);

// Ported from the old Dice/Roller.vue's diceFaces map.
const DICE_FACES = {
    1: [{ cx: 50, cy: 50 }],
    2: [{ cx: 25, cy: 25 }, { cx: 75, cy: 75 }],
    3: [{ cx: 25, cy: 25 }, { cx: 50, cy: 50 }, { cx: 75, cy: 75 }],
    4: [{ cx: 25, cy: 25 }, { cx: 75, cy: 25 }, { cx: 25, cy: 75 }, { cx: 75, cy: 75 }],
    5: [{ cx: 25, cy: 25 }, { cx: 75, cy: 25 }, { cx: 50, cy: 50 }, { cx: 25, cy: 75 }, { cx: 75, cy: 75 }],
    6: [{ cx: 25, cy: 20 }, { cx: 75, cy: 20 }, { cx: 25, cy: 50 }, { cx: 75, cy: 50 }, { cx: 25, cy: 80 }, { cx: 75, cy: 80 }],
};

const dots = computed(() => DICE_FACES[props.lastRoll ?? 1] ?? DICE_FACES[1]);
const isLimited = computed(() => props.rollLimit !== null && props.rollsToday >= (props.rollLimit ?? Infinity));
const canRoll = computed(() => !props.disabled && !props.rolling && !isLimited.value);
</script>
