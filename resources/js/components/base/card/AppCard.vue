<script setup lang="ts">
/**
 * @component AppCard
 * @description Root container for card layouts.
 */
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppCardProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppCardProps>();
</script>

<template>
    <div
        :class="
            cn(
                'rounded-xl border border-zinc-200 bg-white text-zinc-950 shadow dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-50',
                props.class,
            )
        "
    >
        <slot />
    </div>
</template>
