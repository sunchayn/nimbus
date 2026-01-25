<script setup lang="ts">
/**
 * @component AppCardDescription
 * @description Supporting text for a card title.
 */
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppCardDescriptionProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppCardDescriptionProps>();
</script>

<template>
    <p :class="cn('text-subtle-foreground text-sm', props.class)">
        <slot />
    </p>
</template>
