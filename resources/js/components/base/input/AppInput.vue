<script setup lang="ts">
/**
 * @component AppInput
 * @description Text input built on Nuxt UI.
 */
import { useForwardedProps } from '@/components/base/useForwardedProps';
import type { InputProps } from '@nuxt/ui/components/Input.vue';
import UInput from '@nuxt/ui/components/Input.vue';
import { useTemplateRef } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppInputProps extends InputProps<string | number> {}

/*
 * Component Setup.
 */

const props = defineProps<AppInputProps>();

const emit = defineEmits<{
    'update:modelValue': [value: string | number];
}>();

const forwarded = useForwardedProps(props);
const input = useTemplateRef<{ inputRef: HTMLInputElement | null }>('input');

defineExpose({
    get inputRef() {
        return input.value?.inputRef ?? null;
    },
});
</script>

<template>
    <UInput
        ref="input"
        v-bind="forwarded"
        @update:model-value="value => emit('update:modelValue', value)"
    >
        <template v-if="$slots.leading" #leading="slotProps">
            <slot name="leading" v-bind="slotProps" />
        </template>
        <template v-if="$slots.trailing" #trailing="slotProps">
            <slot name="trailing" v-bind="slotProps" />
        </template>
    </UInput>
</template>
