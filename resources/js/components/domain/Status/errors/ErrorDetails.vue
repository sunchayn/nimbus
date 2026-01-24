<script setup lang="ts">
/**
 * @component ErrorDetails
 * @description Displays detailed information about a specific route extraction error.
 */
import AppPanelStateContainer from '@/components/base/AppPanelStateContainer.vue';
import { AppBadge } from '@/components/base/badge';
import { AppScrollArea } from '@/components/base/scroll-area';
import HttpVerbLabel from '@/components/domain/HttpVerbLabel/HttpVerbLabel.vue';
import type { JSONSchema7 } from 'json-schema';

/*
 * Types & Interfaces.
 */

export interface RouteWithError {
    endpoint: string;
    method: string;
    resource: string;
    version: string;
    schema: {
        shape: JSONSchema7;
        extractionErrors: string;
    };
}

export interface AppErrorDetailsProps {
    selectedRoute: RouteWithError | null;
}

/*
 * Component Setup.
 */

const props = defineProps<AppErrorDetailsProps>();
</script>

<template>
    <div class="flex h-full flex-col">
        <!-- Error Header -->
        <div
            class="h-toolbar px-panel bg-subtle-background flex flex-shrink-0 items-center justify-between border-b"
        >
            <span class="text-foreground text-sm font-semibold">Error Details</span>
        </div>

        <!-- Error Content -->
        <AppScrollArea class="min-h-0 flex-1">
            <div v-if="props.selectedRoute !== null" class="p-4">
                <!-- Route Info -->
                <div class="bg-subtle-background mb-4 rounded-lg p-2">
                    <div class="mb-2 flex items-center space-x-2">
                        <HttpVerbLabel :method="props.selectedRoute.method" size="sm" />
                        <span class="text-foreground font-mono text-sm">
                            {{ props.selectedRoute.endpoint }}
                        </span>
                    </div>
                    <div class="text-muted-foreground text-xs">
                        /{{ props.selectedRoute.resource }}
                        <span v-if="props.selectedRoute.version !== 'n/a'">
                            • v{{ props.selectedRoute.version }}
                        </span>
                    </div>
                </div>

                <!-- Error Display -->
                <div
                    class="via-background from-destructive/10 dark:from-destructive/30 relative max-h-full overflow-hidden rounded-lg bg-gradient-to-br from-10% p-2"
                >
                    <div class="relative z-10 flex max-h-full flex-col space-y-4">
                        <div>
                            <AppBadge variant="outline" class="mb-2 p-0 px-1 text-xs">
                                Extraction Error
                            </AppBadge>
                            <p class="text-subtle-foreground text-sm">
                                The schema extraction process encountered an error while
                                processing this route. The error details below may help
                                identify the issue.
                            </p>
                        </div>
                        <!-- Render HTML content safely to preserve error formatting -->
                        <div class="bg-subtle-background rounded-sm p-2 text-sm">
                            <!-- eslint-disable vue/no-v-html -->
                            <div
                                class="prose prose-sm max-w-none"
                                v-html="props.selectedRoute.schema.extractionErrors"
                            />
                            <!-- eslint-enable vue/no-v-html -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="flex h-full items-center justify-center">
                <AppPanelStateContainer>
                    <h2 class="text-lg font-medium">
                        Select a route to view its error details
                    </h2>
                    <p class="mb-2 text-sm">
                        Click on any route from the list on the left to see the extraction
                        error details.
                    </p>
                </AppPanelStateContainer>
            </div>
        </AppScrollArea>
    </div>
</template>
