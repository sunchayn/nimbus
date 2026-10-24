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
    <AppPanel class="flex flex-col gap-2 px-2 py-1 shadow-none">
        <AppPanelTitle class="px-0">
            Please resolve the missing route parameters
        </AppPanelTitle>

        <AppPanelDescription class="text-xs">
            Provide values for the following parameters to proceed with the request
        </AppPanelDescription>

        <ul class="flex list-inside list-decimal flex-col gap-1 text-xs">
            <li
                v-for="item in parameters"
                :key="item"
                class="font-mono"
                v-text="'{' + item + '}'"
            ></li>
        </ul>
    </AppPanel>
</template>
