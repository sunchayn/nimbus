<script setup lang="ts">
/**
 * @component SchemaExtractionErrorButton
 * @description A button that opens a dialog displaying details about schema extraction errors.
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
import { ChevronsLeftRightIcon } from 'lucide-vue-next';

/*
 * Types & Interfaces.
 */

export interface AppSchemaExtractionErrorButtonProps {
    errorHtml: string;
}

/*
 * Component Setup.
 */

defineProps<AppSchemaExtractionErrorButtonProps>();
</script>

<template>
    <AppDialog>
        <AppDialogTrigger>
            <AppButton size="xs" variant="ghost">
                <ChevronsLeftRightIcon />
                Error Info
            </AppButton>
        </AppDialogTrigger>
        <AppDialogContent
            class="to-background from-destructive/10 dark:from-destructive/15 flex max-h-[90dvh] flex-col bg-gradient-to-bl to-50% sm:max-w-[600px]"
        >
            <AppDialogHeader>
                <AppDialogTitle>Schema Extraction Error</AppDialogTitle>
                <AppDialogDescription>
                    The following error was thrown when trying to call the `rules()`
                    method from the request.
                </AppDialogDescription>
            </AppDialogHeader>
            <div
                class="dark:bg-subtle-background bg-destructive/10 max-w-full flex-1 overflow-auto p-2 leading-tight wrap-anywhere"
            >
                <!-- eslint-disable-next-line vue/no-v-html -->
                <p v-html="errorHtml" />
            </div>
            <p class="text-xs italic">
                Note: An attempt to understand the schema statically (from the code as is)
                was made. The schema might not be complete.
            </p>
            <AppDialogFooter class="sm:justify-start">
                <AppDialogClose as-child>
                    <AppButton variant="secondary">Dismiss</AppButton>
                </AppDialogClose>
            </AppDialogFooter>
        </AppDialogContent>
    </AppDialog>
</template>
