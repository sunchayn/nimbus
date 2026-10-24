<script setup lang="ts">
/**
 * @component CurlExportDialog
 * @description A dialog that displays the generated cURL command for a request.
 */
import { AppAlert } from '@/components/base/alert';
import { AppButton } from '@/components/base/button';
import { AppDialog } from '@/components/base/dialog';
import { useClipboard } from '@vueuse/core';

/*
 * Types & Interfaces.
 */

export interface CurlExportDialogProps {
    open: boolean;
    command: string;
    hasSpecialAuth: boolean;
}

export interface CurlExportDialogEmits {
    (e: 'update:open', value: boolean): void;
}

/*
 * Component Setup.
 */

const props = defineProps<CurlExportDialogProps>();
const emits = defineEmits<CurlExportDialogEmits>();

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
    <AppDialog
        :open="open"
        title="cURL command"
        description="Copy the generated cURL command to use in your terminal or scripts."
        :ui="{ content: 'max-w-dialog-lg' }"
        @update:open="emits('update:open', $event)"
    >
        <template #body>
            <div class="gap-loose flex min-h-0 flex-1 flex-col">
                <AppAlert
                    v-if="hasSpecialAuth"
                    color="warning"
                    variant="subtle"
                    icon="i-hugeicons-alert-02"
                    description="Authorization has been dropped as special authorization types (Current User, Impersonate) are not supported in cURL commands."
                />

                <div class="gap-base flex min-h-0 flex-1 flex-col">
                    <div class="flex items-center justify-end">
                        <AppButton
                            size="sm"
                            variant="outline"
                            :icon="copied ? 'i-hugeicons-tick-02' : 'i-hugeicons-copy-01'"
                            :label="copied ? 'Copied!' : 'Copy'"
                            @click="copyCommand"
                        />
                    </div>

                    <pre
                        class="bg-muted text-default border-default p-base h-full min-h-0 flex-1 overflow-auto rounded-md border font-mono text-xs leading-relaxed break-all whitespace-break-spaces"
                        >{{ command }}</pre
                    >
                </div>
            </div>
        </template>

        <template #footer>
            <AppButton size="sm" variant="outline" @click="closeDialog">Close</AppButton>
        </template>
    </AppDialog>
</template>
