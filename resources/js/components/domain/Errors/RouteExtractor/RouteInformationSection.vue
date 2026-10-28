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

export interface RouteInformationSectionProps {
    routeContext: ExceptionRouteContext;
}

/*
 * Component Setup.
 */

defineProps<RouteInformationSectionProps>();
</script>

<template>
    <div class="gap-base flex flex-col">
        <h3 class="text-highlighted text-xs font-medium">Route Information</h3>
        <dl
            class="gap-x-base gap-y-tight bg-muted p-base grid grid-cols-[auto_1fr] items-center rounded-md border text-xs"
        >
            <template v-if="routeContext.uri">
                <dt class="text-muted">URI</dt>
                <dd>
                    <code class="bg-default px-tight rounded-sm border">
                        {{ routeContext.uri }}
                    </code>
                </dd>
            </template>

            <template v-if="routeContext.methods">
                <dt class="text-muted">Methods</dt>
                <dd class="gap-tight flex">
                    <HttpVerbLabel
                        v-for="method in routeContext.methods"
                        :key="method"
                        :method="method"
                    />
                </dd>
            </template>

            <template v-if="routeContext.controllerClass">
                <dt class="text-muted">Controller</dt>
                <dd>
                    <code class="bg-default px-tight rounded-sm border break-all">
                        {{ routeContext.controllerClass }}
                    </code>
                </dd>
            </template>

            <template v-if="routeContext.controllerMethod">
                <dt class="text-muted">Method</dt>
                <dd>
                    <code class="bg-default px-tight rounded-sm border">
                        {{ routeContext.controllerMethod }}
                    </code>
                </dd>
            </template>
        </dl>
    </div>
</template>
