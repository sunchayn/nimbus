<script setup lang="ts">
/**
 * @component AppCommandShortcut
 * @description Displays keyboard shortcuts for a command item.
 */
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppCommandShortcutProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppCommandShortcutProps>();
</script>

<template>
    <span
        data-slot="command-shortcut"
        :class="cn('text-muted-foreground ml-auto text-xs tracking-widest', props.class)"
    >
        <slot />
    </span>
</template>
