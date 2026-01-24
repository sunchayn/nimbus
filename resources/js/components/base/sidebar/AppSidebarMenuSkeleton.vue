<script setup lang="ts">
/**
 * @component AppSidebarMenuSkeleton
 * @description Loading placeholder for sidebar menu items.
 */
import { AppSkeleton } from '@/components/base/skeleton';
import { cn } from '@/utils/ui';
import { computed, type HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppSidebarMenuSkeletonProps {
    showIcon?: boolean;
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppSidebarMenuSkeletonProps>();

/*
 * Computed & Methods.
 */

const width = computed(() => {
    return `${Math.floor(Math.random() * 40) + 50}%`;
});
</script>

<template>
    <div
        data-sidebar="menu-skeleton"
        :class="cn('flex h-8 items-center gap-2 rounded-md px-2', props.class)"
    >
        <AppSkeleton
            v-if="showIcon"
            class="size-4 rounded-md"
            data-sidebar="menu-skeleton-icon"
        />

        <AppSkeleton
            class="h-4 max-w-[--skeleton-width] flex-1"
            data-sidebar="menu-skeleton-text"
            :style="{ '--skeleton-width': width }"
        />
    </div>
</template>
