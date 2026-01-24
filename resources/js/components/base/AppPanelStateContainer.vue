<script setup lang="ts">
/**
 * @component AppPanelStateContainer
 * @description A layout container for panel content with an integrated ripple background effect.
 */
import AppPanelRipple from '@/components/base/AppPanelRipple.vue';
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppPanelStateContainerProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppPanelStateContainerProps>();
</script>

<template>
    <div :class="cn('relative h-full flex-1 overflow-hidden p-2', props.class)">
        <div class="max-w-md">
            <slot />
        </div>
        <AppPanelRipple />
    </div>
</template>
