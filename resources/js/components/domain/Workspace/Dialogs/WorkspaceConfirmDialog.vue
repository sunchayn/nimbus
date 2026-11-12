<script setup lang="ts">
/**
 * @component WorkspaceConfirmDialog
 * @description A confirmation dialog of the workspace, with a cancel button and a confirm button.
 */
import { AppButton } from '@/components/base/button';
import { AppDialog } from '@/components/base/dialog';

/*
 * Types & Interfaces.
 */

export interface WorkspaceConfirmDialogProps {
    title: string;
    description?: string;
    confirmLabel: string;
    /** The destructive style is the default. */
    confirmColor?: 'error' | 'primary';
    testId?: string;
}

/*
 * Component Setup.
 */

withDefaults(defineProps<WorkspaceConfirmDialogProps>(), {
    description: undefined,
    confirmColor: 'error',
    testId: undefined,
});

const open = defineModel<boolean>('open', { default: false });

const emit = defineEmits<{
    confirm: [];
}>();
</script>

<template>
    <AppDialog v-model:open="open" :title="title" :description="description">
        <template v-if="$slots.description" #description>
            <slot name="description" />
        </template>
        <template #footer>
            <AppButton size="sm" variant="outline" color="neutral" @click="open = false">
                Cancel
            </AppButton>
            <AppButton
                size="sm"
                :color="confirmColor"
                :label="confirmLabel"
                :data-testid="testId"
                @click="emit('confirm')"
            />
        </template>
    </AppDialog>
</template>
