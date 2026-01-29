<script setup lang="ts">
/**
 * @component RouteExtractionError
 * @description Displays a banner alerting the user about a missing implementation for a defined route.
 */
import { AppButton } from '@/components/base/button';
import {
    AppDialog,
    AppDialogClose,
    AppDialogContent,
    AppDialogDescription,
    AppDialogFooter,
    AppDialogHeader,
    AppDialogTitle,
    AppDialogTrigger,
} from '@/components/base/dialog';
import CopyButton from '@/components/common/CopyButton.vue';
import { useConfigStore } from '@/stores';
import { useClipboard } from '@vueuse/core';
import { ChevronsLeftRightIcon, TriangleAlertIcon } from 'lucide-vue-next';

const configStore = useConfigStore();
const { copy, copied } = useClipboard();
</script>

<template>
    <div
        class="px-panel from-warning/5 dark:from-warning/15 flex items-center justify-between border-b bg-gradient-to-tr to-transparent to-50% py-0.5"
    >
        <div class="flex items-center gap-2 text-xs">
            <div class="px-1 py-2">
                <TriangleAlertIcon class="text-warning size-4" />
            </div>
            <div class="leading-3.5">
                <span class="mb-0 flex items-center gap-1 font-bold">
                    Route not found
                </span>
                <p class="text-subtle-foreground mb-0">
                    This route is defined in the {{ configStore.primaryProcessorName }},
                    but it is not found in your application
                </p>
            </div>
        </div>

        <AppDialog>
            <AppDialogTrigger>
                <AppButton size="xs" variant="ghost">
                    <ChevronsLeftRightIcon />
                    Info
                </AppButton>
            </AppDialogTrigger>

            <AppDialogContent
                class="bg-background max-h-[90dvh] flex-col p-0 sm:max-w-[600px]"
            >
                <div
                    class="to-background from-warning/10 dark:from-warning/15 flex h-full w-full flex-col gap-4 bg-gradient-to-bl to-40% p-3.5"
                >
                    <AppDialogHeader>
                        <AppDialogTitle>Route Not Found</AppDialogTitle>
                        <AppDialogDescription>
                            This route is defined in
                            {{ configStore.primaryProcessorName }} but it cannot be found
                            in the currently selected application's laravel routes.
                        </AppDialogDescription>
                    </AppDialogHeader>
                    <div class="flex flex-col gap-3.5 text-sm wrap-anywhere">
                        <span>Possible Solutions:</span>

                        <div class="border-b pb-3.5">
                            <h3 class="text-base font-bold">Clear routes cache</h3>
                            <p class="mb-2">
                                It might be that you switched to a new branch but your
                                routes are cached.
                            </p>
                            <div
                                class="px-panel inline-flex items-center space-x-2 rounded border bg-white font-mono text-xs"
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
                                Your schema files might be out of sync with the current
                                branch state, make sure to rebase with the main branch.
                            </p>
                        </div>
                    </div>
                    <AppDialogFooter class="border-t pt-3.5 sm:justify-start">
                        <AppDialogClose as-child>
                            <AppButton variant="default" size="xs">Dismiss</AppButton>
                        </AppDialogClose>
                    </AppDialogFooter>
                </div>
            </AppDialogContent>
        </AppDialog>
    </div>
</template>
