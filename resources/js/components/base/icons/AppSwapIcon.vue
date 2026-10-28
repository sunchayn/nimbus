<script setup lang="ts">
/**
 * @component AppSwapIcon
 * @description Shows one icon and crossfades to the next one when the name changes.
 */
import AppIcon from '@/components/base/icons/AppIcon.vue';
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppSwapIconProps {
    /**
     * Icon name. Pass a full literal string so the build can bundle it.
     */
    name: string;
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppSwapIconProps>();
</script>

<template>
    <span
        :class="cn('inline-grid shrink-0 *:col-start-1 *:row-start-1', props.class)"
        aria-hidden="true"
    >
        <Transition name="swap-icon">
            <AppIcon :key="name" :name="name" class="size-full" />
        </Transition>
    </span>
</template>

<style>
/* Both icons share one grid cell, so the leaving icon fades out under the entering one. */
.swap-icon-enter-active,
.swap-icon-leave-active {
    transition:
        opacity var(--duration-base) var(--ease-emphasized),
        scale var(--duration-base) var(--ease-emphasized),
        filter var(--duration-base) var(--ease-emphasized);
}

.swap-icon-enter-from,
.swap-icon-leave-to {
    opacity: 0;
    scale: 0.25;
    filter: blur(var(--spacing-tight));
}

/* Reduced motion keeps a plain opacity crossfade. */
@media (prefers-reduced-motion: reduce) {
    .swap-icon-enter-active,
    .swap-icon-leave-active {
        transition: opacity var(--duration-fast) linear;
    }

    .swap-icon-enter-from,
    .swap-icon-leave-to {
        scale: none;
        filter: none;
    }
}
</style>
