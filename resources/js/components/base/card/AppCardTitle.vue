<script setup lang="ts">
/**
 * @component AppCardTitle
 * @description Main heading for a card.
 */
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppCardTitleProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppCardTitleProps>();
</script>

<template>
    <h3 :class="cn('leading-none font-semibold tracking-tight', props.class)">
        <slot />
    </h3>
</template>
