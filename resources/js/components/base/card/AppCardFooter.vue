<script setup lang="ts">
/**
 * @component AppCardFooter
 * @description Footer container for card actions or metadata.
 */
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppCardFooterProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppCardFooterProps>();
</script>

<template>
    <div :class="cn('flex items-center p-6 pt-0', props.class)">
        <slot />
    </div>
</template>
