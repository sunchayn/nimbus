<script setup lang="ts">
/**
 * @component AppSelectLabel
 * @description A label used to categorize or group select items.
 */
import { cn } from '@/utils/ui';
import { SelectLabel, type SelectLabelProps } from 'reka-ui';
import { reactiveOmit } from '@vueuse/core';
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

/*
 * Computed & Methods.
 */

const delegatedProps = reactiveOmit(props, 'class');
</script>

<template>
    <SelectLabel :class="cn('px-2 py-1.5 text-sm font-semibold', props.class)">
        <slot />
    </SelectLabel>
</template>
