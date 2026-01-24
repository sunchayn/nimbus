<script setup lang="ts">
/**
 * @component AppSidebarMenuItem
 * @description A list item used within a sidebar menu layer.
 */
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppSidebarMenuItemProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppSidebarMenuItemProps>();
</script>

<template>
    <li data-sidebar="menu-item" :class="cn('group/menu-item relative', props.class)">
        <slot />
    </li>
</template>
