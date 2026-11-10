<script setup lang="ts">
/**
 * @component ToolResult
 * @description Shows the result or the error of a toolbox tool.
 */
import { AppAlert } from '@/components/base/alert';

/*
 * Types & Interfaces.
 */

export interface ToolResultProps {
    result: unknown;
    error: string | null;
}

/*
 * Component Setup.
 */

defineProps<ToolResultProps>();
</script>

<template>
    <AppAlert
        v-if="error"
        color="error"
        variant="subtle"
        :description="error"
        data-testid="tool-error"
    />
    <pre
        v-else-if="result !== null"
        class="bg-muted border-default p-base overflow-x-auto rounded-md border font-mono text-xs whitespace-pre"
        data-testid="tool-result"
        >{{ JSON.stringify(result, null, 2) }}</pre
    >
</template>
