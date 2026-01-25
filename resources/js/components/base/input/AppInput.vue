<script setup lang="ts">
/**
 * @component AppInput
 * @description A standard text input component with double-shift value generation support.
 */
import { ValueGeneratorCommandOpenMethod } from '@/interfaces/ui';
import { useValueGeneratorStore } from '@/stores';
import { cn } from '@/utils/ui';
import { useVModel } from '@vueuse/core';
import type { HTMLAttributes } from 'vue';
import { ref } from 'vue';
import { inputVariants, type InputVariants } from './index';

/*
 * Types & Interfaces.
 */

export interface AppInputProps {
    defaultValue?: string | number;
    modelValue?: string | number;
    class?: HTMLAttributes['class'];
    type?: string;
    placeholder?: string;
    disabled?: boolean;
    variant?: InputVariants['variant'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppInputProps>();

const emits = defineEmits<{
    (e: 'update:modelValue', payload: string | number): void;
}>();

const modelValue = useVModel(props, 'modelValue', emits, {
    passive: true,
    defaultValue: props.defaultValue,
});

/*
 * Computed & Methods.
 */

const { openCommand } = useValueGeneratorStore();
const inputRef = ref<HTMLInputElement>();

// Shift+Shift tracking
const lastShiftPress = ref<number>(0);
const shiftPressThreshold = 500; // 500ms window for double shift

const handleKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Shift') {
        const now = Date.now();

        // Check if this is a double shift press within the threshold
        if (now - lastShiftPress.value < shiftPressThreshold) {
            event.preventDefault();
            openCommand(inputRef.value, ValueGeneratorCommandOpenMethod.SHIFT_SHIFT);
        }

        lastShiftPress.value = now;
    }
};
</script>

<template>
    <input
        ref="inputRef"
        v-model="modelValue"
        :type="type ?? 'text'"
        :class="cn(inputVariants({ variant }), props.class)"
        :placeholder="placeholder"
        :disabled="disabled"
        @keydown="handleKeydown"
    />
</template>
