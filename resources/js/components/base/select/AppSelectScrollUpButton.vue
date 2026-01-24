<script setup lang="ts">
/**
 * @component AppSelectScrollUpButton
 * @description A button displayed at the top of the select viewport to enable scrolling up.
 */
import { cn } from '@/utils/ui';
import { reactiveOmit } from '@vueuse/core';
import { ChevronUp } from 'lucide-vue-next';
import {
    SelectScrollUpButton,
    type SelectScrollUpButtonProps,
    useForwardProps,
} from 'reka-ui';
import { type HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppSelectScrollUpButtonProps extends SelectScrollUpButtonProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppSelectScrollUpButtonProps>();

/*
 * Computed & Methods.
 */

const delegatedProps = reactiveOmit(props, 'class');

const forwardedProps = useForwardProps(delegatedProps);
</script>

<template>
    <SelectScrollUpButton
        v-bind="forwardedProps"
        :class="cn('flex cursor-default items-center justify-center py-1', props.class)"
    >
        <slot>
            <ChevronUp />
        </slot>
    </SelectScrollUpButton>
</template>
