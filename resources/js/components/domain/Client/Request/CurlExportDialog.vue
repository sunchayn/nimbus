<script setup lang="ts">
/**
 * @component CurlExportDialog
 * @description A dialog that displays the generated cURL command for a request.
 */
import { AppButton } from '@/components/base/button';
import {
    AppDialog,
    AppDialogContent,
    AppDialogDescription,
    AppDialogHeader,
    AppDialogTitle,
} from '@/components/base/dialog';
import { useClipboard } from '@vueuse/core';
import { Check, Copy } from 'lucide-vue-next';

/*
 * Types & Interfaces.
 */

export interface AppCurlExportDialogProps {
    open: boolean;
    command: string;
    hasSpecialAuth: boolean;
}

export interface AppCurlExportDialogEmits {
    (e: 'update:open', value: boolean): void;
}

/*
 * Component Setup.
 */

const props = defineProps<AppCurlExportDialogProps>();
const emits = defineEmits<AppCurlExportDialogEmits>();

const { copy, copied } = useClipboard();

/*
 * Methods.
 */

const copyCommand = () => {
    copy(props.command);
};

const closeDialog = () => {
    emits('update:open', false);
};
</script>

<template>
    <AppDialog :open="open" @update:open="emits('update:open', $event)">
        <AppDialogContent
            class="flex max-h-[90vh] max-w-2xl flex-col overflow-hidden sm:max-w-2xl"
        >
            <AppDialogHeader>
                <AppDialogTitle>cURL Command</AppDialogTitle>
                <AppDialogDescription>
                    Copy the generated cURL command to use in your terminal or scripts.
                </AppDialogDescription>
            </AppDialogHeader>

            <div class="flex min-h-0 flex-1 flex-col space-y-2.5">
                <div
                    v-if="hasSpecialAuth"
                    class="bg-warning/10 dark:bg-warning/20 rounded-md p-2.5"
                >
                    <p class="text-warning text-xs">
                        Authorization has been dropped as special authorization types
                        (Current User, Impersonate) are not supported in cURL commands.
                    </p>
                </div>

                <div class="flex min-h-0 flex-1 flex-col space-y-2.5">
                    <div class="flex items-center justify-end">
                        <AppButton
                            variant="outline"
                            size="sm"
                            class="flex items-center gap-2"
                            @click="copyCommand"
                        >
                            <Check v-if="copied" class="h-4 w-4" />
                            <Copy v-else class="h-4 w-4" />
                            {{ copied ? 'Copied!' : 'Copy' }}
                        </AppButton>
                    </div>

                    <pre
                        class="bg-subtle text-foreground h-full min-h-0 flex-1 overflow-auto rounded-md border p-2.5 font-mono text-xs leading-relaxed break-all whitespace-break-spaces"
                        >{{ command }}</pre
                    >
                </div>
            </div>

            <div class="flex justify-end border-t pt-2.5">
                <AppButton variant="outline" @click="closeDialog">Close</AppButton>
            </div>
        </AppDialogContent>
    </AppDialog>
</template>
