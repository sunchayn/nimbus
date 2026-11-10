<script setup lang="ts">
/**
 * @component RequestBuilderEndpointParameterWarningContent
 * @description Renders the content for the route parameter warning panel.
 */
import { AppPanel, AppPanelDescription, AppPanelTitle } from '@/components/base/panel';
import type { RouteDefinition } from '@/interfaces/routes/routes';
import { describeRouteParameter } from '@/utils/routes';

/*
 * Types & Interfaces.
 */

interface Props {
    parameters: string[];
    route?: RouteDefinition;
}

/*
 * Component Setup.
 */

defineProps<Props>();
</script>

<template>
    <AppPanel class="gap-base flex flex-col shadow-none">
        <AppPanelTitle>Please resolve the missing route parameters</AppPanelTitle>

        <AppPanelDescription class="text-xs">
            Provide values for the following parameters to proceed with the request
        </AppPanelDescription>

        <ul class="gap-tight flex list-inside list-decimal flex-col text-xs">
            <li v-for="item in parameters" :key="item">
                <span class="font-mono" v-text="'{' + item + '}'"></span>
                <span
                    v-if="describeRouteParameter(route, item)"
                    class="text-muted"
                    v-text="' ' + describeRouteParameter(route, item)"
                ></span>
            </li>
        </ul>
    </AppPanel>
</template>
