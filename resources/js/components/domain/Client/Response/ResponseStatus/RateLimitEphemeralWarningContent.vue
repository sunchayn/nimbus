<script setup lang="ts">
/**
 * @component RateLimitEphemeralWarningContent
 * @description Renders the content of the panel that explains why a route is not rate limited.
 */
import { AppPanel, AppPanelDescription, AppPanelTitle } from '@/components/base/panel';
</script>

<template>
    <AppPanel
        class="gap-base flex w-sm flex-col shadow-none"
        data-testid="rate-limit-ephemeral-message"
    >
        <AppPanelTitle>This request will not be rate limited</AppPanelTitle>

        <AppPanelDescription class="text-xs">
            The app is configured to use an ephemeral cache driver, such as the array
            store. Attempts are forgotten after each request.
        </AppPanelDescription>
    </AppPanel>
</template>
