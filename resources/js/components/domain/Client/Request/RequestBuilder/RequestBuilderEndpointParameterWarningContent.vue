<script setup lang="ts">
/**
 * @component RequestBuilderEndpointParameterWarningContent
 * @description Renders the content for the route parameter warning panel.
 */
import { AppPanel, AppPanelDescription, AppPanelTitle } from '@/components/base/panel';

/*
 * Types & Interfaces.
 */

interface Props {
    parameters: string[];
}

/*
 * Component Setup.
 */

defineProps<Props>();
</script>

<template>
    <AppPanel class="gap-base px-base py-tight flex flex-col shadow-none">
        <AppPanelTitle class="px-0">
            Please resolve the missing route parameters
        </AppPanelTitle>

        <AppPanelDescription class="text-xs">
            Provide values for the following parameters to proceed with the request
        </AppPanelDescription>

        <ul class="gap-tight flex list-inside list-decimal flex-col text-xs">
            <li
                v-for="item in parameters"
                :key="item"
                class="font-mono"
                v-text="'{' + item + '}'"
            ></li>
        </ul>
    </AppPanel>
</template>
