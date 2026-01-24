<script setup lang="ts">
/**
 * @component AppResizablePanelGroup
 * @description Root container for managing multiple resizable panels.
 */
import { cn } from '@/utils/ui';
import { reactiveOmit } from '@vueuse/core';
import {
    SplitterGroup,
    type SplitterGroupEmits,
    type SplitterGroupProps,
    useForwardPropsEmits,
} from 'reka-ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppResizablePanelGroupProps extends SplitterGroupProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppResizablePanelGroupProps>();
const emits = defineEmits<SplitterGroupEmits>();

const delegatedProps = reactiveOmit(props, 'class');

const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>

<template>
    <SplitterGroup
        data-slot="resizable-panel-group"
        v-bind="forwarded"
        :class="
            cn('flex h-full w-full data-[orientation=vertical]:flex-col', props.class)
        "
    >
        <slot />
    </SplitterGroup>
</template>
