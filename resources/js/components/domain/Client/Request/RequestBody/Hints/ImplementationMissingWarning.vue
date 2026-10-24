<script setup lang="ts">
/**
 * @component RouteExtractionError
 * @description Displays a banner alerting the user about a missing implementation for a defined route.
 */
import { AppButton } from '@/components/base/button';
import { AppDialog } from '@/components/base/dialog';
import { AppIcon } from '@/components/base/icons';
import CopyButton from '@/components/common/CopyButton.vue';
import { useConfigStore } from '@/stores';
import { useClipboard } from '@vueuse/core';

const configStore = useConfigStore();
const { copy, copied } = useClipboard();
</script>

<template>
    <div
        class="px-panel from-warning/5 flex items-center justify-between border-b bg-gradient-to-tr to-transparent to-50% py-1"
    >
        <div class="flex items-center gap-2 text-xs">
            <div class="px-1 py-2">
                <AppIcon name="i-hugeicons-alert-02" class="text-warning size-icon-md" />
            </div>
            <div class="leading-3.5">
                <span class="mb-0 flex items-center gap-1 font-bold">
                    Route not found
                </span>
                <p class="text-muted mb-0">
                    This route is defined in the
                    {{ configStore.primaryProcessorName }}, but it is not found in your
                    application
                </p>
            </div>
        </div>

        <AppDialog
            title="Route not found"
            :description="`This route is defined in ${configStore.primaryProcessorName} but it cannot be found in the currently selected application's laravel routes.`"
        >
            <AppButton
                size="xs"
                variant="ghost"
                icon="i-hugeicons-arrow-left-right"
                label="Info"
            />

            <template #body>
                <div class="gap-loose flex flex-col text-sm wrap-anywhere">
                    <span>Possible Solutions:</span>

                    <div class="pb-loose gap-base flex flex-col items-start border-b">
                        <h3 class="text-base font-bold">Clear routes cache</h3>
                        <p>
                            It might be that you switched to a new branch but your routes
                            are cached.
                        </p>
                        <div
                            class="px-panel bg-default gap-base inline-flex items-center rounded-sm border font-mono text-xs"
                        >
                            <code class="flex-1">php artisan route:clear</code>

                            <CopyButton
                                :on-click="() => copy('php artisan route:clear')"
                                :copied="copied"
                            />
                        </div>
                    </div>

                    <div>
                        <h3 class="text-base font-bold">Sync your branch</h3>
                        <p class="text-sm">
                            Your schema files might be out of sync with the current branch
                            state, make sure to rebase with the main branch.
                        </p>
                    </div>
                </div>
            </template>

            <template #footer="{ close }">
                <AppButton size="sm" @click="close">Dismiss</AppButton>
            </template>
        </AppDialog>
    </div>
</template>
