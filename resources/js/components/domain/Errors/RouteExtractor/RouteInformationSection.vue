<script setup lang="ts">
/**
 * @component RouteInformationSection
 * @description Displays details about the route where the exception occurred (URI, method, controller).
 */
import HttpVerbLabel from '@/components/domain/HttpVerbLabel/HttpVerbLabel.vue';
import { type ExceptionRouteContext } from '@/interfaces/routes/exceptions';

/*
 * Types & Interfaces.
 */

export interface AppRouteInformationSectionProps {
    routeContext: ExceptionRouteContext;
}

/*
 * Component Setup.
 */

defineProps<AppRouteInformationSectionProps>();
</script>

<template>
    <div>
        <h3 class="mb-2 font-semibold">Route Information</h3>
        <div class="space-y-2 rounded-lg border p-4">
            <!-- URI -->
            <div v-if="routeContext.uri" class="flex items-center gap-2">
                <span class="text-subtle-foreground w-20 text-sm font-medium">URI:</span>
                <code class="bg-subtle rounded border px-2 py-1 text-sm">
                    {{ routeContext.uri }}
                </code>
            </div>

            <!-- HTTP Methods -->
            <div v-if="routeContext.methods" class="flex items-center gap-2">
                <span class="text-subtle-foreground w-20 text-sm font-medium">
                    Methods:
                </span>
                <div class="flex gap-1">
                    <HttpVerbLabel
                        v-for="method in routeContext.methods"
                        :key="method"
                        :method="method"
                    />
                </div>
            </div>

            <!-- Controller Class -->
            <div v-if="routeContext.controllerClass" class="flex items-center gap-2">
                <span class="text-subtle-foreground w-20 text-sm font-medium">
                    Controller:
                </span>
                <code class="bg-subtle rounded border px-2 py-1 text-sm">
                    {{ routeContext.controllerClass }}
                </code>
            </div>

            <!-- Controller Method -->
            <div v-if="routeContext.controllerMethod" class="flex items-center gap-2">
                <span class="text-subtle-foreground w-20 text-sm font-medium">
                    Method:
                </span>
                <code class="bg-subtle rounded border px-2 py-1 text-sm">
                    {{ routeContext.controllerMethod }}
                </code>
            </div>
        </div>
    </div>
</template>
