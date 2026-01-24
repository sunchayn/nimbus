<script setup lang="ts">
/**
 * @component AppPopoverContent
 * @description The main content area for a popover, including portal and animations.
 */
import { cn } from '@/utils/ui';
import { reactiveOmit } from '@vueuse/core';
import type { PopoverContentEmits, PopoverContentProps } from 'reka-ui';
import { PopoverContent, PopoverPortal, useForwardPropsEmits } from 'reka-ui';
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

/*
 * Types & Interfaces.
 */

export interface AppPopoverContentProps extends PopoverContentProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = withDefaults(defineProps<AppPopoverContentProps>(), {
    align: 'center',
    sideOffset: 4,
    class: undefined,
});
const emits = defineEmits<PopoverContentEmits>();

const delegatedProps = reactiveOmit(props, 'class');

const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>

<template>
    <PopoverPortal>
        <PopoverContent
            data-slot="popover-content"
            v-bind="{ ...forwarded, ...$attrs }"
            :class="
                cn(
                    'bg-popover text-popover-foreground data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2 z-50 w-72 origin-(--reka-popover-content-transform-origin) rounded-md border p-4 shadow-md outline-hidden',
                    props.class,
                )
            "
        >
            <slot />
        </PopoverContent>
    </PopoverPortal>
</template>
