<script setup lang="ts">
/**
 * @component AppSkeleton
 * @description A placeholder component used to indicate loading states.
 */
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppSkeletonProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppSkeletonProps>();
</script>

<template>
    <div
        :class="
            cn('animate-pulse rounded-md bg-zinc-900/10 dark:bg-zinc-50/10', props.class)
        "
    />
</template>
