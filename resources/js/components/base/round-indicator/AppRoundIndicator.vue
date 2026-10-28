<script setup lang="ts">
/**
 * @component AppRoundIndicator
 * @description A small circular indicator used for visual status or marking.
 */
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppRoundIndicatorProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppRoundIndicatorProps>();
</script>

<template>
    <div
        :class="
            cn(
                props.class,
                'bg-default z-sticky size-dot-md relative flex items-center justify-center rounded-full',
            )
        "
    >
        <div class="indicator-core size-dot-sm rounded-full border border-current" />
        <div class="indicator-glow size-dot-xs absolute -z-1 rounded-full opacity-40" />
    </div>
</template>

<style scoped>
.indicator-core {
    background: radial-gradient(circle at bottom, currentColor 75%, var(--ui-bg) 100%);
}

.indicator-glow {
    box-shadow:
        var(--spacing-dot-xs) 0 var(--spacing-dot-xs) currentColor,
        -var(--spacing-dot-xs) 0 var(--spacing-dot-xs) currentColor;
}
</style>
