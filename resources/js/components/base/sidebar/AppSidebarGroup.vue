<script setup lang="ts">
/**
 * @component AppSidebarGroup
 * @description A logical grouping for sidebar menu items.
 */
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppSidebarGroupProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppSidebarGroupProps>();
</script>

<template>
    <div
        data-sidebar="group"
        :class="cn('relative flex w-full min-w-0 flex-col p-2', props.class)"
    >
        <slot />
    </div>
</template>
