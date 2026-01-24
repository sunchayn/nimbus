<script setup lang="ts">
/**
 * @component AppSelectSeparator
 * @description A visual divider between select items or groups.
 */
import { cn } from '@/utils/ui';
import { reactiveOmit } from '@vueuse/core';
import { SelectSeparator, type SelectSeparatorProps } from 'reka-ui';
import { type HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppSelectSeparatorProps extends SelectSeparatorProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppSelectSeparatorProps>();

/*
 * Computed & Methods.
 */

const delegatedProps = reactiveOmit(props, 'class');
</script>

<template>
    <SelectSeparator
        v-bind="delegatedProps"
        :class="cn('-mx-1 my-1 h-px bg-zinc-100 dark:bg-zinc-800', props.class)"
    />
</template>
