<script setup lang="ts">
/**
 * @component AppTextarea
 * @description Multi-line text input built on Nuxt UI.
 */
import { useForwardedProps } from '@/components/base/useForwardedProps';
import type { TextareaProps } from '@nuxt/ui/components/Textarea.vue';
import UTextarea from '@nuxt/ui/components/Textarea.vue';

/*
 * Types & Interfaces.
 */

export interface AppTextareaProps
    extends Omit<TextareaProps, 'modelValue' | 'defaultValue'> {}

/*
 * Component Setup.
 */

const props = defineProps<AppTextareaProps>();

const modelValue = defineModel<string>({ default: '' });

const forwarded = useForwardedProps(props);
</script>

<template>
    <UTextarea v-bind="forwarded" v-model="modelValue" />
</template>
