<script setup lang="ts">
/**
 * @component AppSidebarFooter
 * @description The bottom section of the sidebar, typically for user profiles or settings.
 */
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppSidebarFooterProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppSidebarFooterProps>();
</script>

<template>
    <div data-sidebar="footer" :class="cn('flex flex-col gap-2 p-2', props.class)">
        <slot />
    </div>
</template>
