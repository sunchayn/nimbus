<script setup lang="ts">
/**
 * @component AppSidebarMenuSubButton
 * @description An interactive button within a sidebar sub-menu.
 */
import { cn } from '@/utils/ui';
import type { PrimitiveProps } from 'reka-ui';
import { Primitive } from 'reka-ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppSidebarMenuSubButtonProps extends PrimitiveProps {
    size?: 'sm' | 'md';
    isActive?: boolean;
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = withDefaults(defineProps<AppSidebarMenuSubButtonProps>(), {
    as: 'a',
    size: 'md',
    isActive: false,
    class: '',
});
</script>

<template>
    <Primitive
        data-sidebar="menu-sub-button"
        :as="as"
        :as-child="asChild"
        :data-size="size"
        :data-active="isActive"
        :class="
            cn(
                'text-sidebar-foreground ring-sidebar-ring hover:bg-sidebar-accent hover:text-sidebar-accent-foreground active:bg-sidebar-accent active:text-sidebar-accent-foreground [&>svg]:text-sidebar-accent-foreground flex h-8 min-w-0 -translate-x-px items-center gap-2 overflow-hidden rounded-md px-2 outline-none focus-visible:ring-2 disabled:pointer-events-none disabled:opacity-50 aria-disabled:pointer-events-none aria-disabled:opacity-50 [&>span:last-child]:truncate [&>svg]:size-4 [&>svg]:shrink-0',
                'data-[active=true]:bg-sidebar-accent data-[active=true]:text-sidebar-accent-foreground',
                size === 'sm' && 'text-xs',
                size === 'md' && 'text-sm',
                'group-data-[collapsible=icon]:hidden',
                props.class,
            )
        "
    >
        <slot />
    </Primitive>
</template>
