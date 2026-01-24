<script setup lang="ts">
/**
 * @component AppTooltipContent
 * @description The floating content area for a tooltip, including transitions and offsets.
 */
import { cn } from '@/utils/ui';
import {
    TooltipContent,
    type TooltipContentEmits,
    type TooltipContentProps,
    TooltipPortal,
    useForwardPropsEmits,
} from 'reka-ui';
import { computed, type HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

/*
 * Types & Interfaces.
 */

export interface AppTooltipContentProps extends TooltipContentProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = withDefaults(defineProps<AppTooltipContentProps>(), {
    sideOffset: 4,
    class: '',
});

const emits = defineEmits<TooltipContentEmits>();

/*
 * Computed & Methods.
 */

const delegatedProps = computed(() => {
    const { class: _, ...delegated } = props;

    return delegated;
});

const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>

<template>
    <TooltipPortal>
        <TooltipContent
            v-bind="{ ...forwarded, ...$attrs }"
            :class="
                cn(
                    'animate-in fade-in-0 zoom-in-95 data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=closed]:zoom-out-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2 z-50 overflow-hidden rounded-md bg-zinc-900 px-3 py-1.5 text-xs text-zinc-50 dark:bg-zinc-50 dark:text-zinc-900',
                    props.class,
                )
            "
        >
            <slot />
        </TooltipContent>
    </TooltipPortal>
</template>
