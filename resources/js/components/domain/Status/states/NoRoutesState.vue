<script setup lang="ts">
/**
 * @component NoRoutesState
 * @description State displayed when no routes could be found/extracted.
 */
import DocumentationLinkButton from '@/components/domain/DocumentationLinkButton.vue';
</script>

<template>
    <div>
        <h2 class="text-lg font-medium">No Routes Found</h2>
        <p class="mb-2 text-sm">
            No routes have been extracted yet. Check your configuration.
        </p>
        <DocumentationLinkButton />
    </div>
</template>
