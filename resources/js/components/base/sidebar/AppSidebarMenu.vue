<script setup lang="ts">
/**
 * @component AppSidebarMenu
 * @description The main menu list container within the sidebar.
 */
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppSidebarMenuProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppSidebarMenuProps>();
</script>

<template>
    <ul
        data-sidebar="menu"
        :class="cn('flex w-full min-w-0 flex-col gap-1', props.class)"
    >
        <slot />
    </ul>
</template>
