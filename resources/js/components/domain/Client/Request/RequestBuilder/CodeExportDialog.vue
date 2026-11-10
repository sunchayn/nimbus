<script setup lang="ts">
/**
 * @component CodeExportDialog
 * @description A dialog that displays the request as code in the language the user picks.
 */
import { AppButton } from '@/components/base/button';
import { AppDialog } from '@/components/base/dialog';
import { AppSelect } from '@/components/base/select';
import { useClipboard } from '@vueuse/core';
import { computed, shallowRef } from 'vue';

/*
 * Types & Interfaces.
 */

export interface CodeExportDialogProps {
    open: boolean;
    // Code samples by language value.
    samples: Record<string, string>;
    languages: { value: string; label: string }[];
}

export interface CodeExportDialogEmits {
    (e: 'update:open', value: boolean): void;
}

/*
 * Component Setup.
 */

const props = defineProps<CodeExportDialogProps>();
const emits = defineEmits<CodeExportDialogEmits>();

const { copy, copied } = useClipboard();

const language = shallowRef<string | null>(null);

const selectedLanguage = computed(() => language.value ?? props.languages[0]?.value);

const content = computed(() => props.samples[selectedLanguage.value ?? ''] ?? '');

/*
 * Methods.
 */

const copyCode = () => {
    copy(content.value);
};

const closeDialog = () => {
    emits('update:open', false);
};
</script>

<template>
    <AppDialog
        :open="open"
        title="Export as code"
        description="Copy the request as code. Secrets are replaced with placeholders."
        :ui="{ content: 'max-w-dialog-lg' }"
        @update:open="emits('update:open', $event)"
    >
        <template #body>
            <div class="gap-base flex min-h-0 flex-1 flex-col">
                <div class="gap-base flex items-center justify-between">
                    <AppSelect
                        :model-value="selectedLanguage"
                        :items="languages"
                        size="sm"
                        class="min-w-48"
                        aria-label="Language"
                        data-testid="export-language"
                        @update:model-value="language = $event as string"
                    />
                    <AppButton
                        size="sm"
                        variant="outline"
                        :icon="copied ? 'i-hugeicons-tick-02' : 'i-hugeicons-copy-01'"
                        :label="copied ? 'Copied!' : 'Copy'"
                        data-testid="copy-code"
                        @click="copyCode"
                    />
                </div>

                <pre
                    class="bg-muted text-default border-default p-base h-full min-h-0 flex-1 overflow-auto rounded-md border font-mono text-xs leading-relaxed whitespace-pre"
                    data-testid="code-sample"
                    >{{ content }}</pre
                >
            </div>
        </template>

        <template #footer>
            <AppButton size="sm" variant="outline" @click="closeDialog">Close</AppButton>
        </template>
    </AppDialog>
</template>
