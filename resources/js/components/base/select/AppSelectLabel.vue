<script setup lang="ts">
/**
 * @component AppSelectLabel
 * @description A label used to categorize or group select items.
 */
import { cn } from '@/utils/ui';
import { SelectLabel, type SelectLabelProps } from 'reka-ui';
import { type HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppSelectLabelProps extends SelectLabelProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppSelectLabelProps>();
</script>

<template>
    <SelectLabel :class="cn('p-1 text-xs font-semibold', props.class)">
        <slot />
    </SelectLabel>
</template>
