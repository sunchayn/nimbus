<script setup lang="ts">
/**
 * @component AppCardContent
 * @description Main content area of a card.
 */
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppCardContentProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppCardContentProps>();
</script>

<template>
    <div :class="cn('p-4 pt-0', props.class)">
        <slot />
    </div>
</template>
