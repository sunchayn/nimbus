<script setup lang="ts">
/**
 * @component AppSidebarSeparator
 * @description A visual divider tailored for the sidebar layout.
 */
import { AppSeparator } from '@/components/base/separator';
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppSidebarSeparatorProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppSidebarSeparatorProps>();
</script>

<template>
    <AppSeparator
        data-sidebar="separator"
        :class="cn('bg-sidebar-border mx-2 w-auto', props.class)"
    >
        <slot />
    </AppSeparator>
</template>
