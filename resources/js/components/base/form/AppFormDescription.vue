<script setup lang="ts">
/**
 * @component AppFormDescription
 * @description Supporting text for a form field.
 */
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';
import { useFormField } from './useFormField';

/*
 * Types & Interfaces.
 */

export interface AppFormDescriptionProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppFormDescriptionProps>();

const { formDescriptionId } = useFormField();
</script>

<template>
    <p :id="formDescriptionId" :class="cn('text-subtle-foreground text-sm', props.class)">
        <slot />
    </p>
</template>
