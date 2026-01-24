<script setup lang="ts">
/**
 * @component AppSidebarContent
 * @description Main scrollable content container for the sidebar.
 */
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppSidebarContentProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppSidebarContentProps>();
</script>

<template>
    <div
        data-sidebar="content"
        :class="
            cn(
                'flex min-h-0 flex-1 flex-col gap-2 overflow-auto group-data-[collapsible=icon]:overflow-hidden',
                props.class,
            )
        "
    >
        <slot />
    </div>
</template>
