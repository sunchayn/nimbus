<script setup lang="ts">
/**
 * @component RouteExtractionError
 * @description Displays a banner alerting the user about a missing route in the primary source.
 */
import { AppButton } from '@/components/base/button';
import { AppDialog } from '@/components/base/dialog';
import { AppIcon } from '@/components/base/icons';
import DocumentationLinkButton from '@/components/domain/DocumentationLinkButton.vue';
import { useConfigStore } from '@/stores';

const configStore = useConfigStore();
</script>

<template>
    <div
        class="px-panel from-info/5 py-tight flex items-center justify-between border-b bg-gradient-to-tr to-transparent to-50%"
    >
        <div class="gap-base flex items-center text-xs">
            <div class="px-tight py-base">
                <AppIcon
                    name="i-hugeicons-information-circle"
                    class="text-info size-icon-md"
                />
            </div>

            <div class="leading-compact">
                <span class="gap-tight mb-0 flex items-center font-bold">
                    Route is auto-detected
                </span>
                <p class="text-muted mb-0">
                    This route is not defined in
                    {{ configStore.primaryProcessorName }}, but it was auto-detected and
                    added.
                </p>
            </div>
        </div>

        <AppDialog title="Route is auto-detected">
            <AppButton
                size="xs"
                variant="ghost"
                icon="i-hugeicons-arrow-left-right"
                label="Info"
            />

            <template #body>
                <div class="gap-loose flex flex-col">
                    <p class="gap-base flex flex-col text-sm wrap-anywhere">
                        This application is configured to use
                        {{ configStore.primaryProcessorName }}. However, this route is not
                        defined there. It was auto-detected from your local routes and
                        added accordingly.
                        <br />
                        <small>
                            This will help you test out routes without having to define
                            them yet in the
                            {{ configStore.primaryProcessorName }}.
                        </small>
                    </p>

                    <div>
                        <DocumentationLinkButton variant="outline" />
                    </div>
                </div>
            </template>

            <template #footer="{ close }">
                <AppButton size="sm" @click="close">Dismiss</AppButton>
            </template>
        </AppDialog>
    </div>
</template>
