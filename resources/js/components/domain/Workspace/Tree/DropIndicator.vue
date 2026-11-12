<script setup lang="ts">
/**
 * @component DropIndicator
 * @description A line on one edge of a row that shows where a dragged item would land.
 */
export interface DropIndicatorProps {
    edge: 'before' | 'after';
}

defineProps<DropIndicatorProps>();
</script>

<template>
    <span
        class="bg-info h-hair pointer-events-none absolute inset-x-0 z-10 rounded-full"
        :class="edge === 'before' ? 'top-0' : 'bottom-0'"
        aria-hidden="true"
        data-testid="workspace-drop-indicator"
    />
</template>
