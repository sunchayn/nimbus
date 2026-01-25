<script setup lang="ts">
/**
 * @component AppPanel
 * @description A dense container component for panel-based layouts.
 */
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppPanelProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppPanelProps>();
</script>

<template>
    <div
        :class="
            cn(
                'flex flex-col bg-white text-zinc-950 dark:bg-zinc-950 dark:text-zinc-50',
                props.class,
            )
        "
    >
        <slot />
    </div>
</template>
