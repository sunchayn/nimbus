<script setup lang="ts">
/**
 * @component AppDropdownMenuLabel
 * @description A label for a group of dropdown menu items.
 */
import { cn } from '@/utils/ui';
import { DropdownMenuLabel, type DropdownMenuLabelProps, useForwardProps } from 'reka-ui';
import { computed, type HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppDropdownMenuLabelProps extends DropdownMenuLabelProps {
    class?: HTMLAttributes['class'];
    inset?: boolean;
}

/*
 * Component Setup.
 */

const props = defineProps<AppDropdownMenuLabelProps>();

const delegatedProps = computed(() => {
    const { class: _, ...delegated } = props;

    return delegated;
});

const forwardedProps = useForwardProps(delegatedProps);
</script>

<template>
    <DropdownMenuLabel
        v-bind="forwardedProps"
        :class="cn('px-2 py-1.5 text-xs', inset && 'pl-8', props.class)"
    >
        <slot />
    </DropdownMenuLabel>
</template>
