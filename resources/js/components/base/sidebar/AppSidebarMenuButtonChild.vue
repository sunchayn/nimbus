<script setup lang="ts">
/**
 * @component AppSidebarMenuButtonChild
 * @description Internal primitive for the sidebar menu button, handling base styles and active indicators.
 */
import { cn } from '@/utils/ui';
import { Primitive, type PrimitiveProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { type SidebarMenuButtonVariants, sidebarMenuButtonVariants } from './index';

/*
 * Types & Interfaces.
 */

export interface AppSidebarMenuButtonChildProps extends PrimitiveProps {
    variant?: SidebarMenuButtonVariants['variant'];
    size?: SidebarMenuButtonVariants['size'];
    isActive?: boolean;
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = withDefaults(defineProps<AppSidebarMenuButtonChildProps>(), {
    as: 'button',
    variant: 'default',
    size: 'default',
    isActive: false,
    class: '',
});
</script>

<template>
    <Primitive
        data-sidebar="menu-button"
        :data-size="size"
        :data-active="isActive"
        :class="cn(sidebarMenuButtonVariants({ variant, size }), props.class)"
        :as="as"
        :as-child="asChild"
        v-bind="$attrs"
    >
        <span
            v-if="isActive"
            class="absolute left-0 z-10 h-full w-[1px] -translate-x-[3px] bg-gray-500 dark:bg-gray-400"
        ></span>
        <slot />
    </Primitive>
</template>
