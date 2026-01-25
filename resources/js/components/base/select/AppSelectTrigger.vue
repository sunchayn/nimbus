<script setup lang="ts">
/**
 * @component AppSelectTrigger
 * @description The interactive element that opens the select menu.
 */
import { cn } from '@/utils/ui';
import { reactiveOmit } from '@vueuse/core';
import { ChevronDown } from 'lucide-vue-next';
import {
    SelectIcon,
    SelectTrigger,
    type SelectTriggerProps,
    useForwardProps,
} from 'reka-ui';
import { type HTMLAttributes } from 'vue';
import { selectTriggerVariants, type SelectTriggerVariants } from './index';

defineOptions({
    inheritAttrs: false,
});

/*
 * Types & Interfaces.
 */

export interface AppSelectTriggerProps extends SelectTriggerProps {
    class?: HTMLAttributes['class'];
    variant?: SelectTriggerVariants['variant'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppSelectTriggerProps>();

/*
 * Computed & Methods.
 */

const delegatedProps = reactiveOmit(props, 'class', 'variant');

const forwardedProps = useForwardProps(delegatedProps);
</script>

<template>
    <SelectTrigger
        v-bind="{ ...forwardedProps, ...$attrs }"
        :class="cn(selectTriggerVariants({ variant }), props.class)"
    >
        <slot />
        <SelectIcon as-child>
            <ChevronDown class="h-4 w-4 shrink-0 opacity-50" />
        </SelectIcon>
    </SelectTrigger>
</template>
