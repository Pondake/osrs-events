<template>
    <u-input v-model="model" :type="visible ? 'text' : 'password'" :ui="{ trailing: 'pe-1' }">
        <template #trailing>
            <u-button
                color="neutral"
                variant="link"
                size="sm"
                :icon="visible ? 'i-lucide-eye-off' : 'i-lucide-eye'"
                :aria-label="visible ? $t('common.hide_password') : $t('common.show_password')"
                :aria-pressed="visible"
                @click="visible = !visible"
            />
        </template>
    </u-input>
</template>

<script setup>
import { ref } from 'vue';

const model = defineModel({ type: String, default: '' });
const visible = ref(false);
</script>
