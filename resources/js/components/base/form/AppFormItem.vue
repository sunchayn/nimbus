<script setup lang="ts">
/**
 * @component AppFormItem
 * @description Container for a single form field, providing context to its sub-components.
 */
import { cn } from '@/utils/ui';
import { useId } from 'reka-ui';
import { type HTMLAttributes, provide } from 'vue';
import { FORM_ITEM_INJECTION_KEY } from './injectionKeys';

/*
 * Types & Interfaces.
 */

export interface AppFormItemProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppFormItemProps>();

const id = useId();
provide(FORM_ITEM_INJECTION_KEY, id);
</script>

<template>
    <div :class="cn('space-y-2', props.class)">
        <slot />
    </div>
</template>
