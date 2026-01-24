<script setup lang="ts">
/**
 * @component AppCardHeader
 * @description Header container for card title and description.
 */
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppCardHeaderProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppCardHeaderProps>();
</script>

<template>
    <div :class="cn('flex flex-col gap-y-1.5 p-6', props.class)">
        <slot />
    </div>
</template>
