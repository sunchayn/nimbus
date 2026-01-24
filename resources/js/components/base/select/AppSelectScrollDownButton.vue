<script setup lang="ts">
/**
 * @component AppSelectScrollDownButton
 * @description A button displayed at the bottom of the select viewport to enable scrolling down.
 */
import { cn } from '@/utils/ui';
import { reactiveOmit } from '@vueuse/core';
import { ChevronDown } from 'lucide-vue-next';
import {
    SelectScrollDownButton,
    type SelectScrollDownButtonProps,
    useForwardProps,
} from 'reka-ui';
import { type HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppSelectScrollDownButtonProps extends SelectScrollDownButtonProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppSelectScrollDownButtonProps>();

/*
 * Computed & Methods.
 */

const delegatedProps = reactiveOmit(props, 'class');

const forwardedProps = useForwardProps(delegatedProps);
</script>

<template>
    <SelectScrollDownButton
        v-bind="forwardedProps"
        :class="cn('flex cursor-default items-center justify-center py-1', props.class)"
    >
        <slot>
            <ChevronDown />
        </slot>
    </SelectScrollDownButton>
</template>
