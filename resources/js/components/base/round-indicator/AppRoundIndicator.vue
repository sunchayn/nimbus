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
                'bg-default z-sticky relative flex size-3 items-center justify-center rounded-full',
            )
        "
    >
        <div class="indicator-core size-2 rounded-full border border-current" />
        <div class="indicator-glow absolute -z-1 size-1.25 rounded-full opacity-40" />
    </div>
</template>

<style scoped>
.indicator-core {
    background: radial-gradient(circle at bottom, currentColor 75%, var(--ui-bg) 100%);
}

.indicator-glow {
    box-shadow:
        5px 0 5px currentColor,
        -5px 0 5px currentColor;
}
</style>
