<script setup lang="ts">
/**
 * @component RouteExtractionError
 * @description Displays a banner alerting the user about a missing route in the primary source.
 */
import { AppButton } from '@/components/base/button';
import {
    AppDialog,
    AppDialogClose,
    AppDialogContent,
    AppDialogFooter,
    AppDialogHeader,
    AppDialogTitle,
    AppDialogTrigger,
} from '@/components/base/dialog';
import DocumentationLinkButton from '@/components/domain/DocumentationLinkButton.vue';
import { useConfigStore } from '@/stores';
import { ChevronsLeftRightIcon, InfoIcon } from 'lucide-vue-next';

const configStore = useConfigStore();
</script>

<template>
    <div
        class="px-panel flex items-center justify-between border-b bg-gradient-to-tr from-blue-500/5 to-transparent to-50% py-0.5 dark:from-blue-700/30"
    >
        <div class="flex items-center gap-2 text-xs">
            <div class="px-1 py-2">
                <InfoIcon class="size-4 text-blue-900 dark:text-blue-100" />
            </div>

            <div class="leading-3.5">
                <span class="mb-0 flex items-center gap-1 font-bold">
                    Route is auto-detected
                </span>
                <p class="text-subtle-foreground mb-0">
                    This route is not defined in {{ configStore.primaryProcessorName }},
                    but it was auto-detected and added.
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
                        <AppDialogTitle>Route is auto-detected</AppDialogTitle>
                    </AppDialogHeader>

                    <p class="flex flex-col gap-3.5 text-sm wrap-anywhere">
                        This application is configured to use
                        {{ configStore.primaryProcessorName }}. However, this route is not
                        defined there. It was auto-detected from your local routes and
                        added accordingly.
                        <br />
                        <small>
                            This will help you test out routes without having to define
                            them yet in the {{ configStore.primaryProcessorName }}.
                        </small>
                    </p>

                    <div>
                        <DocumentationLinkButton variant="outline" />
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
