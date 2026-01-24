<script setup lang="ts">
/**
 * @component AppSidebar
 * @description Root sidebar component that handles layout, collapsible states, and variants.
 */
import { cn } from '@/utils/ui';
import type { SidebarProps } from './index';
import { useSidebar } from './index';

defineOptions({
    inheritAttrs: false,
});

/*
 * Types & Interfaces.
 */

export interface AppSidebarComponentProps extends SidebarProps {}

/*
 * Component Setup.
 */

const props = withDefaults(defineProps<AppSidebarComponentProps>(), {
    side: 'left',
    variant: 'sidebar',
    collapsible: 'icon',
});

const { state } = useSidebar();
</script>

<template>
    <div
        v-if="collapsible === 'none'"
        :class="
            cn(
                'bg-sidebar text-sidebar-foreground flex h-full w-[var(--sidebar-width)] flex-col',
                props.class,
            )
        "
        v-bind="$attrs"
    >
        <slot />
    </div>

    <div
        v-else
        class="group peer block"
        :data-state="state"
        :data-collapsible="state === 'collapsed' ? collapsible : ''"
        :data-variant="variant"
        :data-side="side"
    >
        <!-- This is what handles the sidebar gap on desktop  -->
        <div
            :class="
                cn(
                    'relative h-svh w-[var(--sidebar-width)] bg-transparent transition-[width] duration-200 ease-linear',
                    'group-data-[collapsible=offcanvas]:w-0',
                    'group-data-[side=right]:rotate-180',
                    variant === 'floating' || variant === 'inset'
                        ? 'group-data-[collapsible=icon]:w-[calc(var(--sidebar-width-icon)_+_theme(spacing.4))]'
                        : 'group-data-[collapsible=icon]:w-[var(--sidebar-width-icon)]',
                )
            "
        />
        <div
            :class="
                cn(
                    'fixed inset-y-0 z-10 flex h-svh w-[var(--sidebar-width)] transition-[left,right,width] duration-200 ease-linear',
                    side === 'left'
                        ? 'left-0 group-data-[collapsible=offcanvas]:left-[calc(var(--sidebar-width)*-1)]'
                        : 'right-0 group-data-[collapsible=offcanvas]:right-[calc(var(--sidebar-width)*-1)]',
                    // Adjust the padding for floating and inset variants.
                    variant === 'floating' || variant === 'inset'
                        ? 'p-2 group-data-[collapsible=icon]:w-[calc(var(--sidebar-width-icon)_+_theme(spacing.4)_+2px)]'
                        : 'group-data-[collapsible=icon]:w-[var(--sidebar-width-icon)] group-data-[side=left]:border-r group-data-[side=right]:border-l',
                    props.class,
                )
            "
            v-bind="$attrs"
        >
            <div
                data-sidebar="sidebar"
                class="text-sidebar-foreground bg-sidebar group-data-[variant=floating]:border-sidebar-border flex h-full w-full flex-col group-data-[variant=floating]:rounded-lg group-data-[variant=floating]:border group-data-[variant=floating]:shadow"
            >
                <slot />
            </div>
        </div>
    </div>
</template>
