<script setup lang="ts">
/**
 * @component WorkspaceEmptyState
 * @description What the workspace tab shows while nothing is saved yet.
 */
import DocumentationLinkButton from '@/components/domain/DocumentationLinkButton.vue';

defineProps<{
    isLoading?: boolean;
}>();
</script>

<template>
    <div data-testid="workspace-empty" class="px-hair">
        <p v-if="isLoading" class="text-muted pt-tight text-xs">Loading...</p>
        <template v-else>
            <strong class="text-sm font-medium">No saved requests yet</strong>
            <p class="mb-base text-xs">
                Save a request from its options menu to keep it as a file in your
                workspace.
            </p>
            <DocumentationLinkButton
                section="workspace"
                variant="outline"
                label="Learn more"
            />
        </template>
    </div>
</template>
