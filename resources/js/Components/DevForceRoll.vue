<template>
    <!-- Two rows of six: the same six steps forwards and backwards, so -3
         sits under 3 and the pair reads as one control. Backwards is something
         no die can do — it is how a snake head or ladder foot is reached a
         second time without editing the board. -->
    <div class="mb-3">
        <span class="block text-[10px] uppercase tracking-wide text-muted text-center mb-1">dev: force roll</span>
        <div class="grid grid-cols-6 gap-1">
            <u-button
                v-for="face in 6"
                :key="face"
                size="xs"
                color="neutral"
                variant="outline"
                class="justify-center"
                :disabled="disabled"
                @click="emit('roll', face)"
            >
                {{ face }}
            </u-button>
            <u-button
                v-for="face in 6"
                :key="`back-${face}`"
                size="xs"
                color="neutral"
                variant="soft"
                class="justify-center"
                :disabled="disabled"
                @click="emit('roll', -face)"
            >
                −{{ face }}
            </u-button>
        </div>
    </div>
</template>

<script setup>
defineProps({
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['roll']);
</script>
