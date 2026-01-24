<script setup lang="ts">
/**
 * @component AppSelectItem
 * @description An individual selectable item within a select menu.
 */
import { cn } from '@/utils/ui';
import { Check } from 'lucide-vue-next';
import {
    SelectItem,
    SelectItemIndicator,
    type SelectItemProps,
    SelectItemText,
    useForwardProps,
} from 'reka-ui';
import { reactiveOmit } from '@vueuse/core';
import { type HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppSelectItemProps extends SelectItemProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppSelectItemProps>();

/*
 * Computed & Methods.
 */

const delegatedProps = reactiveOmit(props, 'class');

const forwardedProps = useForwardProps(delegatedProps);
</script>

<template>
    <SelectItem
        v-bind="forwardedProps"
        :class="
            cn(
                'relative flex w-full cursor-default items-center rounded-sm py-1.5 pr-8 pl-2 text-sm outline-none select-none focus:bg-zinc-100 focus:text-zinc-900 data-[disabled]:pointer-events-none data-[disabled]:opacity-50 dark:focus:bg-zinc-800 dark:focus:text-zinc-50',
                props.class,
            )
        "
    >
        <span class="absolute right-2 flex h-3.5 w-3.5 items-center justify-center">
            <SelectItemIndicator>
                <Check class="h-4 w-4" />
            </SelectItemIndicator>
        </span>

        <SelectItemText>
            <slot />
        </SelectItemText>
    </SelectItem>
</template>
