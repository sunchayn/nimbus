<script setup lang="ts">
/**
 * @component AppSidebarInput
 * @description A specialized input component for use within the sidebar.
 */
import { AppInput } from '@/components/base/input';
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppSidebarInputProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppSidebarInputProps>();
</script>

<template>
    <AppInput
        data-sidebar="input"
        :class="
            cn(
                'focus-visible:ring-sidebar-ring h-8 w-full bg-white shadow-none focus-visible:ring-2 dark:bg-zinc-950',
                props.class,
            )
        "
    >
        <slot />
    </AppInput>
</template>
