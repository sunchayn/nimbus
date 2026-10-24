<script setup lang="ts">
/**
 * @component ShareableLinkDialog
 * @description A dialog component for displaying and copying shareable links.
 */
import { AppAlert } from '@/components/base/alert';
import { AppButton } from '@/components/base/button';
import { AppDialog } from '@/components/base/dialog';
import { AppIcon } from '@/components/base/icons';
import { useClipboard } from '@vueuse/core';

/*
 * Types & Interfaces.
 */

interface ShareableLinkDialogProps {
    open: boolean;
    link: string;
}

/*
 * Component Setup.
 */

const props = defineProps<ShareableLinkDialogProps>();
const emits = defineEmits<{
    'update:open': [value: boolean];
}>();

/*
 * State.
 */

const { copy, copied } = useClipboard();

/*
 * Actions.
 */

const copyLink = () => {
    copy(props.link);
};

const closeDialog = () => {
    emits('update:open', false);
};
</script>

<template>
    <AppDialog
        :open="open"
        description="Share this link with your teammates to restore the exact request state and response."
        :ui="{ content: 'max-w-dialog-lg' }"
        @update:open="emits('update:open', $event)"
    >
        <template #title>
            <AppIcon name="i-hugeicons-link" class="size-icon-md" />
            Shareable Link
        </template>

        <template #body>
            <div class="gap-loose flex flex-1 flex-col overflow-hidden">
                <AppAlert
                    color="info"
                    variant="subtle"
                    icon="i-hugeicons-information-circle"
                    description="This link contains the full request configuration and the latest response. Anyone with this link can restore the exact state in their Nimbus instance."
                />

                <div class="gap-base flex min-h-0 flex-1 flex-col">
                    <div class="flex items-center justify-between">
                        <h4 class="text-default text-xs font-medium">Link</h4>
                        <AppButton
                            size="sm"
                            variant="outline"
                            :icon="copied ? 'i-hugeicons-tick-02' : 'i-hugeicons-copy-01'"
                            :label="copied ? 'Copied!' : 'Copy'"
                            @click="copyLink"
                        />
                    </div>

                    <pre
                        class="bg-muted text-default border-default p-base flex-1 overflow-auto rounded-md border font-mono text-xs leading-relaxed break-all whitespace-break-spaces"
                        data-testid="shareable-link-content"
                        >{{ link }}</pre
                    >
                </div>
            </div>
        </template>

        <template #footer>
            <AppButton size="sm" variant="outline" @click="closeDialog">Close</AppButton>
        </template>
    </AppDialog>
</template>
