<script setup lang="ts">
/**
 * @component RouteExtractionError
 * @description Displays a banner alerting the user about schema extraction errors.
 */
import SchemaExtractionErrorButton from '@/components/domain/Client/Request/RequestBody/Hints/SchemaExtractionErrorButton.vue';
import { TriangleAlertIcon } from 'lucide-vue-next';

/*
 * Types & Interfaces.
 */

export interface AppRequestBodyErrorDisplayProps {
    extractionError: string;
}

/*
 * Component Setup.
 */

const props = defineProps<AppRequestBodyErrorDisplayProps>();
</script>

<template>
    <div
        class="px-panel from-destructive/5 dark:from-destructive/15 flex items-center justify-between border-b bg-gradient-to-tr to-transparent to-50% py-0.5"
    >
        <div class="flex items-center gap-2 text-xs">
            <div class="px-1 py-2">
                <TriangleAlertIcon class="text-destructive size-4" />
            </div>
            <div class="leading-3.5">
                <span class="flex items-center gap-1 font-bold">Extraction Error</span>
                <p class="text-subtle-foreground mb-0">
                    It wasn't possible to fully extract the shape of this route, there was
                    an error.
                </p>
            </div>
        </div>
        <SchemaExtractionErrorButton :error-html="props.extractionError" />
    </div>
</template>
