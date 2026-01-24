<script setup lang="ts">
/**
 * @component StatusIndicator
 * @description A visual indicator component showing the overall health status of routes.
 */
import AppRoundIndicator from '@/components/base/round-indicator/AppRoundIndicator.vue';
import { computed } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppStatusIndicatorProps {
    routesWithErrors: number;
    totalRoutes: number;
}

/*
 * Component Setup.
 */

const props = defineProps<AppStatusIndicatorProps>();

/*
 * Constants.
 */

const STATUS_COLORS = {
    success: 'text-success',
    error: 'text-destructive',
    neutral: 'text-muted-foreground',
} as const;

const STATUS_MESSAGES = {
    allGood: 'All good',
    issuesFound: 'Issues found',
    noRoutes: 'No routes',
} as const;

/*
 * Computed & Methods.
 */

const statusIndicator = computed(() => {
    const { routesWithErrors, totalRoutes } = props;

    if (routesWithErrors === 0 && totalRoutes > 0) {
        return {
            color: STATUS_COLORS.success,
            message: STATUS_MESSAGES.allGood,
        };
    }

    if (routesWithErrors > 0) {
        return {
            color: STATUS_COLORS.error,
            message: STATUS_MESSAGES.issuesFound,
        };
    }

    return { color: STATUS_COLORS.neutral, message: STATUS_MESSAGES.noRoutes };
});
</script>

<template>
    <div class="flex items-center space-x-2">
        <AppRoundIndicator :class="statusIndicator.color" />
        <span class="text-muted-foreground text-xs">
            {{ statusIndicator.message }}
        </span>
    </div>
</template>
