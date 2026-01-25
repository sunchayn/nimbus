<script setup lang="ts">
/**
 * @component RouteStatistics
 * @description Displays statistical metrics about the application routes (total, success, errors, error rate).
 */
import { ChartAreaIcon, CheckCircleIcon, RouteIcon, XCircleIcon } from 'lucide-vue-next';

/*
 * Types & Interfaces.
 */

export interface AppRouteStatisticsProps {
    totalRoutes: number;
    routesWithErrors: number;
    routesWithoutErrors: number;
    errorRate: number;
}

/*
 * Component Setup.
 */

defineProps<AppRouteStatisticsProps>();
</script>

<template>
    <div class="flex items-center space-x-4">
        <!-- Total Routes -->
        <div class="flex items-center space-x-2">
            <RouteIcon class="size-4" />
            <div>
                <span class="text-sm font-medium">{{ totalRoutes }}</span>
                <span class="text-subtle-foreground ml-1 text-xs">routes</span>
            </div>
        </div>

        <!-- Successful Routes -->
        <div class="xs:flex flex hidden items-center space-x-2">
            <CheckCircleIcon class="size-4" />
            <div>
                <span class="text-sm font-medium">{{ routesWithoutErrors }}</span>
                <span class="text-subtle-foreground ml-1 text-xs">success</span>
            </div>
        </div>

        <!-- Routes with Errors -->
        <div class="flex hidden items-center space-x-2 sm:flex">
            <XCircleIcon class="size-4" />
            <div>
                <span class="text-sm font-medium">{{ routesWithErrors }}</span>
                <span class="text-subtle-foreground ml-1 text-xs">errors</span>
            </div>
        </div>

        <!-- Error Rate -->
        <div class="hidden items-center space-x-2 sm:flex">
            <ChartAreaIcon class="size-4" />
            <div>
                <span class="text-sm font-medium">{{ errorRate }}%</span>
                <span class="text-subtle-foreground ml-1 text-xs">error rate</span>
            </div>
        </div>
    </div>
</template>
