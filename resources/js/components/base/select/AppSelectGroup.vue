<script setup lang="ts">
/**
 * @component AppSelectGroup
 * @description A logical grouping for select items.
 */
import { cn } from '@/utils/ui';
import { SelectGroup, type SelectGroupProps } from 'reka-ui';
import { reactiveOmit } from '@vueuse/core';
import { type HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppSelectGroupProps extends SelectGroupProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppSelectGroupProps>();

/*
 * Computed & Methods.
 */

const delegatedProps = reactiveOmit(props, 'class');
</script>

<template>
    <SelectGroup :class="cn('w-full p-1', props.class)" v-bind="delegatedProps">
        <slot />
    </SelectGroup>
</template>
