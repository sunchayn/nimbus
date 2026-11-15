<script setup lang="ts">
/**
 * @component WorkspaceSaveOverlay
 * @description The end of a request row that has unsaved changes.
 * It shows a dot, which a save button replaces while the row is hovered or focused.
 * The row must have the `group` class.
 */
import { AppButton } from '@/components/base/button';
import type { WorkspaceSummary } from '@/interfaces/workspace';

/*
 * Types & Interfaces.
 */

export interface WorkspaceSaveOverlayProps {
    request: WorkspaceSummary;
}

/*
 * Component Setup.
 */

defineProps<WorkspaceSaveOverlayProps>();

defineEmits<{
    save: [request: WorkspaceSummary];
}>();
</script>

<template>
    <div class="pointer-events-none absolute inset-y-0 inset-e-0 flex items-stretch">
        <span
            class="to-elevated w-8 bg-linear-to-r from-transparent opacity-0 transition-opacity group-hover:opacity-100 pointer-coarse:opacity-100"
            aria-hidden="true"
        />
        <span
            class="group-focus-within:bg-elevated group-hover:bg-elevated pe-tight pointer-coarse:bg-elevated flex items-center"
        >
            <span class="size-item-sm relative">
                <span
                    class="absolute inset-0 flex items-center justify-center transition group-focus-within:scale-50 group-focus-within:opacity-0 group-hover:scale-50 group-hover:opacity-0 pointer-coarse:scale-50 pointer-coarse:opacity-0"
                    title="Unsaved changes"
                    data-testid="workspace-item-dirty"
                >
                    <span class="bg-warning size-dot-xs rounded-full" />
                </span>
                <AppButton
                    icon="i-hugeicons-floppy-disk"
                    color="neutral"
                    variant="ghost"
                    size="xs"
                    square
                    class="absolute inset-0 scale-50 opacity-0 transition group-focus-within:pointer-events-auto group-hover:pointer-events-auto group-hover:scale-100 group-hover:opacity-100 pointer-coarse:pointer-events-auto pointer-coarse:scale-100 pointer-coarse:opacity-100"
                    :aria-label="`Save changes to ${request.name}`"
                    title="Save changes"
                    data-testid="workspace-save"
                    @click="$emit('save', request)"
                />
            </span>
        </span>
    </div>
</template>
