<script setup lang="ts">
/**
 * @component AppSidebarHeader
 * @description The top section of the sidebar, typically for logos or workspace switchers.
 */
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppSidebarHeaderProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppSidebarHeaderProps>();
</script>

<template>
    <div data-sidebar="header" :class="cn('flex flex-col gap-2 p-2', props.class)">
        <slot />
    </div>
</template>
