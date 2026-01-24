<script setup lang="ts">
/**
 * @component AppSidebarMenuSub
 * @description A sub-menu list container within a sidebar menu item.
 */
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppSidebarMenuSubProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppSidebarMenuSubProps>();
</script>

<template>
    <ul
        data-sidebar="menu-badge"
        :class="
            cn(
                'border-sidebar-border ml-2.5 flex min-w-0 translate-x-px flex-col gap-1 border-l py-0.5 pl-0.5',
                'group-data-[collapsible=icon]:hidden',
                props.class,
            )
        "
    >
        <slot />
    </ul>
</template>
