<script setup lang="ts">
/**
 * @component ShareableLinkDialog
 * @description A dialog component for displaying and copying shareable links.
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
import { Check, Copy, Link2 } from 'lucide-vue-next';

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
    <AppDialog :open="open" @update:open="emits('update:open', $event)">
        <AppDialogContent
            class="flex max-h-[90vh] max-w-2xl flex-col overflow-hidden sm:max-w-2xl"
        >
            <AppDialogHeader>
                <AppDialogTitle class="flex items-center gap-2">
                    <Link2 class="size-5" />
                    Shareable Link
                </AppDialogTitle>
                <AppDialogDescription>
                    Share this link with your teammates to restore the exact request state
                    and response.
                </AppDialogDescription>
            </AppDialogHeader>

            <div class="flex flex-1 flex-col space-y-4 overflow-hidden">
                <div class="rounded-md bg-indigo-50 p-3 dark:bg-indigo-900/20">
                    <p class="text-sm text-indigo-800 dark:text-indigo-200">
                        This link contains the full request configuration and the latest
                        response. Anyone with this link can restore the exact state in
                        their Nimbus instance.
                    </p>
                </div>

                <div class="flex min-h-0 flex-1 flex-col space-y-3">
                    <div class="mb-2 flex items-center justify-between">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Link
                        </h4>
                        <AppButton
                            variant="outline"
                            size="sm"
                            class="flex items-center gap-2"
                            @click="copyLink"
                        >
                            <Check v-if="copied" class="h-4 w-4" />
                            <Copy v-else class="h-4 w-4" />
                            {{ copied ? 'Copied!' : 'Copy' }}
                        </AppButton>
                    </div>

                    <pre
                        class="bg-subtle-background text-foreground flex-1 overflow-auto rounded-md border p-4 font-mono text-sm leading-relaxed break-all whitespace-break-spaces"
                        data-testid="shareable-link-content"
                        >{{ link }}</pre
                    >
                </div>
            </div>

            <div class="flex justify-end border-t pt-4 dark:border-gray-700">
                <AppButton variant="outline" @click="closeDialog">Close</AppButton>
            </div>
        </AppDialogContent>
    </AppDialog>
</template>
