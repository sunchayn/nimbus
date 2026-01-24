<script setup lang="ts">
/**
 * @component AppBadge
 * @description A small visual indicator for status, categories, or labels.
 */
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';
import { type BadgeVariants, badgeVariants } from './index';

/*
 * Types & Interfaces.
 */

export interface AppBadgeProps {
    variant?: BadgeVariants['variant'];
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppBadgeProps>();
</script>

<template>
    <div :class="cn(badgeVariants({ variant }), props.class)">
        <slot />
    </div>
</template>
