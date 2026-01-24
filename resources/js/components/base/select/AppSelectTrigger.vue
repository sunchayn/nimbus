<script setup lang="ts">
/**
 * @component AppSelectTrigger
 * @description The interactive element that opens the select menu.
 */
import { cn } from '@/utils/ui';
import { ChevronDown } from 'lucide-vue-next';
import {
    SelectIcon,
    SelectTrigger,
    type SelectTriggerProps,
    useForwardProps,
} from 'reka-ui';
import { reactiveOmit } from '@vueuse/core';
import { type HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

/*
 * Types & Interfaces.
 */

export interface AppSelectTriggerProps extends SelectTriggerProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppSelectTriggerProps>();

/*
 * Computed & Methods.
 */

const delegatedProps = reactiveOmit(props, 'class');

const forwardedProps = useForwardProps(delegatedProps);
</script>

<template>
    <SelectTrigger
        v-bind="{ ...forwardedProps, ...$attrs }"
        :class="
            cn(
                'flex h-9 items-center justify-between rounded-md border border-zinc-200 bg-transparent px-3 py-2 text-start text-sm whitespace-nowrap shadow-sm ring-offset-white focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 data-[placeholder]:text-zinc-500 dark:border-zinc-800 dark:data-[placeholder]:text-zinc-400 [&>span]:truncate',
                props.class,
            )
        "
    >
        <slot />
        <SelectIcon as-child>
            <ChevronDown class="h-4 w-4 shrink-0 opacity-50" />
        </SelectIcon>
    </SelectTrigger>
</template>
