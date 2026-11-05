<script setup lang="ts">
/**
 * @component RouteExtractionError
 * @description Displays a banner alerting the user about schema extraction errors.
 */
import SchemaExtractionErrorButton from '@/components/domain/Client/Request/RequestBody/Hints/SchemaExtractionErrorButton.vue';

/*
 * Types & Interfaces.
 */

export interface RouteExtractionErrorProps {
    extractionError: string;
}

/*
 * Component Setup.
 */

const props = defineProps<RouteExtractionErrorProps>();

const message = "The shape of this route couldn't be fully extracted.";
</script>

<template>
    <div
        class="px-panel from-error/5 py-tight gap-base flex items-center border-b bg-gradient-to-tr to-transparent to-50%"
    >
        <p class="min-w-0 flex-1 truncate text-xs" :title="message">
            <span class="text-error block font-medium">Extraction error</span>
            <span class="text-muted">{{ message }}</span>
        </p>
        <SchemaExtractionErrorButton :error-html="props.extractionError" />
    </div>
</template>
