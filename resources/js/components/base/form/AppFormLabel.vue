<script setup lang="ts">
/**
 * @component AppFormLabel
 * @description A label for a form field that automatically handles error states.
 */
import { AppLabel } from '@/components/base/label';
import { cn } from '@/utils/ui';
import type { LabelProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { useFormField } from './useFormField';

/*
 * Types & Interfaces.
 */

export interface AppFormLabelProps extends LabelProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppFormLabelProps>();

const { error, formItemId } = useFormField();
</script>

<template>
    <AppLabel
        :class="cn(error && 'text-red-500 dark:text-red-900', props.class)"
        :for="formItemId"
    >
        <slot />
    </AppLabel>
</template>
