<script setup lang="ts">
/**
 * @component RequestBuilderEndpointPlaceholderWarningContent
 * @description Renders the content for the route placeholder warning panel.
 */
import { AppPanel, AppPanelDescription, AppPanelTitle } from '@/components/base/panel';

/*
 * Types & Interfaces.
 */

interface Props {
    placeholders: string[];
}

/*
 * Component Setup.
 */

defineProps<Props>();
</script>

<template>
    <AppPanel class="flex flex-col gap-1.5 px-2 py-1.5 shadow-none">
        <AppPanelTitle class="px-0">
            Please add the missing route placeholders
        </AppPanelTitle>

        <AppPanelDescription class="text-xs">
            Fill the following placeholders to proceed with the request
        </AppPanelDescription>

        <ul class="flex list-inside list-decimal flex-col gap-1 text-xs">
            <li
                v-for="item in placeholders"
                :key="item"
                class="font-mono"
                v-text="'{' + item + '}'"
            ></li>
        </ul>
    </AppPanel>
</template>
