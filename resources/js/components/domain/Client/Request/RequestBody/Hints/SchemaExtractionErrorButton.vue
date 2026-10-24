<script setup lang="ts">
/**
 * @component SchemaExtractionErrorButton
 * @description A button that opens a dialog displaying details about schema extraction errors.
 */
import { AppAlert } from '@/components/base/alert';
import { AppButton } from '@/components/base/button';
import { AppDialog } from '@/components/base/dialog';

/*
 * Types & Interfaces.
 */

export interface SchemaExtractionErrorButtonProps {
    errorHtml: string;
}

/*
 * Component Setup.
 */

defineProps<SchemaExtractionErrorButtonProps>();
</script>

<template>
    <AppDialog
        title="Schema extraction error"
        description="The following error was thrown when trying to call the `rules()` method from the request."
        :ui="{ content: 'max-w-dialog-lg' }"
    >
        <AppButton
            size="xs"
            variant="ghost"
            icon="i-hugeicons-arrow-left-right"
            label="Error Info"
        />

        <template #body>
            <div class="gap-loose flex flex-col">
                <AppAlert color="error" variant="subtle">
                    <template #description>
                        <!-- eslint-disable-next-line vue/no-v-html -->
                        <p class="overflow-auto wrap-anywhere" v-html="errorHtml" />
                    </template>
                </AppAlert>
                <p class="text-xs italic">
                    Note: An attempt to understand the schema statically (from the code as
                    is) was made. The schema might not be complete.
                </p>
            </div>
        </template>

        <template #footer="{ close }">
            <AppButton size="sm" variant="outline" @click="close">Dismiss</AppButton>
        </template>
    </AppDialog>
</template>
