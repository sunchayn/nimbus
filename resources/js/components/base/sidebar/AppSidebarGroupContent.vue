<script setup lang="ts">
/**
 * @component AppSidebarGroupContent
 * @description The main container for content within a sidebar group.
 */
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppSidebarGroupContentProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppSidebarGroupContentProps>();
</script>

<template>
    <div data-sidebar="group-content" :class="cn('w-full text-sm', props.class)">
        <slot />
    </div>
</template>
