<script setup lang="ts">
/**
 * @component AppInputGroupText
 * @description Plain text addon for an input group.
 */
import { cn } from '@/utils';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppInputGroupTextProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppInputGroupTextProps>();
</script>

<template>
    <span
        :class="
            cn(
                'flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400 [&_svg]:pointer-events-none [&_svg:not([class*=\'size-\'])]:size-4',
                props.class,
            )
        "
    >
        <slot />
    </span>
</template>
