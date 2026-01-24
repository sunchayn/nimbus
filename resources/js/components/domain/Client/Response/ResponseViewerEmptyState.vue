<script setup lang="ts">
/**
 * @component ResponseViewerEmptyState
 * @description A placeholder displayed when no request has been performed yet.
 */
import AppPanelStateContainer from '@/components/base/AppPanelStateContainer.vue';
import DocumentationLinkButton from '@/components/domain/DocumentationLinkButton.vue';

/*
 * Types & Interfaces.
 */

export interface AppResponseViewerEmptyStateProps {}

/*
 * Component Setup.
 */

defineProps<AppResponseViewerEmptyStateProps>();
</script>

<template>
    <AppPanelStateContainer>
        <h2 class="text-lg font-medium">You didn't perform any requests yet</h2>
        <p class="mb-2 text-sm">
            Make request and the result will appear here. Make sure to check the Wiki in
            case of doubts.
        </p>
        <DocumentationLinkButton />
    </AppPanelStateContainer>
</template>
