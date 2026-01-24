<script setup lang="ts">
/**
 * @component AppSidebarProvider
 * @description The context provider for managing sidebar state and keyboard shortcuts.
 */
import { cn } from '@/utils/ui';
import { useEventListener, useVModel } from '@vueuse/core';
import { TooltipProvider } from 'reka-ui';
import { computed, type HTMLAttributes, type Ref } from 'vue';
import {
    provideSidebarContext,
    SIDEBAR_COOKIE_MAX_AGE,
    SIDEBAR_COOKIE_NAME,
    SIDEBAR_KEYBOARD_SHORTCUT,
    SIDEBAR_WIDTH,
    SIDEBAR_WIDTH_ICON,
} from './utils';

/*
 * Types & Interfaces.
 */

export interface AppSidebarProviderProps {
    defaultOpen?: boolean;
    open?: boolean;
    class?: HTMLAttributes['class'];
}

export interface AppSidebarProviderEmits {
    'update:open': [open: boolean];
}

/*
 * Component Setup.
 */

const props = withDefaults(defineProps<AppSidebarProviderProps>(), {
    defaultOpen: false,
    open: undefined,
    class: '',
});

const emits = defineEmits<AppSidebarProviderEmits>();

/*
 * Computed & Methods.
 */

const open = useVModel(props, 'open', emits, {
    defaultValue: props.defaultOpen ?? false,
    passive: (props.open === undefined) as false,
}) as Ref<boolean>;

const setOpen = (value: boolean) => {
    open.value = value; // emits('update:open', value)

    // This sets the cookie to keep the sidebar state.
    document.cookie = `${SIDEBAR_COOKIE_NAME}=${open.value}; path=/; max-age=${SIDEBAR_COOKIE_MAX_AGE}`;
};

// Helper to toggle the sidebar.
const toggleSidebar = () => setOpen(!open.value);

useEventListener('keydown', (event: KeyboardEvent) => {
    if (event.key === SIDEBAR_KEYBOARD_SHORTCUT && (event.metaKey || event.ctrlKey)) {
        event.preventDefault();
        toggleSidebar();
    }
});

// We add a state so that we can do data-state="expanded" or "collapsed".
// This makes it easier to style the sidebar with Tailwind classes.
const state = computed(() => (open.value ? 'expanded' : 'collapsed'));

provideSidebarContext({
    state,
    open,
    setOpen,
    toggleSidebar,
});
</script>

<template>
    <TooltipProvider :delay-duration="0">
        <div
            :style="{
                '--sidebar-width': SIDEBAR_WIDTH,
                '--sidebar-width-icon': SIDEBAR_WIDTH_ICON,
            }"
            :class="
                cn(
                    'group/sidebar-wrapper has-[[data-variant=inset]]:bg-sidebar flex min-h-svh w-full',
                    props.class,
                )
            "
            v-bind="$attrs"
        >
            <slot />
        </div>
    </TooltipProvider>
</template>
