<script setup lang="ts">
/**
 * @component AppDropdownMenuShortcut
 * @description Displays keyboard shortcuts for a dropdown menu item.
 */
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppDropdownMenuShortcutProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppDropdownMenuShortcutProps>();
</script>

<template>
    <span :class="cn('ml-auto text-xs tracking-widest opacity-60', props.class)">
        <slot />
    </span>
</template>
