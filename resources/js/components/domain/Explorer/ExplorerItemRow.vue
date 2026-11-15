<script setup lang="ts">
/**
 * @component ExplorerItemRow
 * @description The row of one request in an explorer tree, which is its verb followed by its label.
 * The default slot is the label, and `#trailing` adds content after it, such as badges.
 */
import HttpVerbLabel from '@/components/domain/HttpVerbLabel/HttpVerbLabel.vue';
import ExplorerRow from './ExplorerRow.vue';

/*
 * Types & Interfaces.
 */

export interface ExplorerItemRowProps {
    method: string;
    isActive?: boolean;
}

/*
 * Component Setup.
 */

defineProps<ExplorerItemRowProps>();
</script>

<template>
    <ExplorerRow :is-active="isActive">
        <HttpVerbLabel class="-me-tight min-w-7" :method="method" />
        <span class="flex-1 whitespace-nowrap">
            <slot />
        </span>
        <slot name="trailing" />
    </ExplorerRow>
</template>
