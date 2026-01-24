<script setup lang="ts">
/**
 * @component AppTooltipWrapper
 * @description A high-level helper component that combines provider, root, trigger, and content for quick tooltip implementation.
 */
import {
    AppTooltip,
    AppTooltipContent,
    AppTooltipProvider,
    AppTooltipTrigger,
} from '@/components/base/tooltip/index';
import { AsTag } from 'reka-ui';
import { type Component } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppTooltipWrapperProps {
    value: string;
    delay?: number;
    as?: AsTag | Component;
    onClick?: () => void;
}

/*
 * Component Setup.
 */

const props = withDefaults(defineProps<AppTooltipWrapperProps>(), {
    as: 'button',
    delay: 300,
    onClick: () => {},
});
</script>

<template>
    <AppTooltipProvider :delay-duration="delay">
        <AppTooltip>
            <AppTooltipTrigger :as="as" @click="onClick">
                <span><slot /></span>
            </AppTooltipTrigger>
            <AppTooltipContent>{{ props.value }}</AppTooltipContent>
        </AppTooltip>
    </AppTooltipProvider>
</template>
