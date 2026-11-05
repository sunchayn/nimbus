<script setup lang="ts">
/**
 * @component RequestBuilderEndpointUrlWarningContent
 * @description Renders the warning panel shown when a pasted URL points to another host.
 */
import { AppPanel, AppPanelDescription, AppPanelTitle } from '@/components/base/panel';

/*
 * Types & Interfaces.
 */

interface Props {
    baseUrl: string;
}

/*
 * Component Setup.
 */

defineProps<Props>();
</script>

<template>
    <AppPanel class="gap-base flex flex-col shadow-none">
        <AppPanelTitle>This URL does not match the environment</AppPanelTitle>

        <AppPanelDescription class="text-xs">
            The endpoint only accepts a path. Paste a URL that starts with
            <span class="font-mono" v-text="baseUrl"></span>
            or change the base URL of the environment.
        </AppPanelDescription>
    </AppPanel>
</template>
