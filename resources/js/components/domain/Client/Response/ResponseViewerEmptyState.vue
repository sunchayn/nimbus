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

export interface ResponseViewerEmptyStateProps {}

/*
 * Component Setup.
 */

defineProps<ResponseViewerEmptyStateProps>();
</script>

<template>
    <AppPanelStateContainer>
        <h2 class="text-base font-medium">No requests yet</h2>
        <p class="mb-base text-sm">
            Send a request and the response appears here. Check the user guide for more
            information.
        </p>
        <DocumentationLinkButton />
    </AppPanelStateContainer>
</template>
