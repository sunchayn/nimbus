<script setup lang="ts">
import { AppIcon } from '@/components/base/icons';
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
</script>

<template>
    <div
        class="px-panel from-error/5 flex items-center justify-between border-b bg-gradient-to-tr to-transparent to-50% py-1"
    >
        <div class="flex items-center gap-2 text-xs">
            <div class="px-1 py-2">
                <AppIcon name="i-hugeicons-alert-02" class="text-error size-icon-md" />
            </div>
            <div class="leading-3.5">
                <span class="flex items-center gap-1 font-bold">Extraction Error</span>
                <p class="text-muted mb-0">
                    It wasn't possible to fully extract the shape of this route, there was
                    an error.
                </p>
            </div>
        </div>
        <SchemaExtractionErrorButton :error-html="props.extractionError" />
    </div>
</template>
