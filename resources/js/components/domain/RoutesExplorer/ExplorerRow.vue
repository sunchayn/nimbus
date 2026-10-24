<script setup lang="ts">
/**
 * @component ExplorerRow
 * @description A clickable row used by the routes explorer lists.
 */

/*
 * Types & Interfaces.
 */

export interface ExplorerRowProps {
    isActive?: boolean;
}

/*
 * Component Setup.
 */

defineProps<ExplorerRowProps>();
</script>

<template>
    <button
        type="button"
        :data-active="isActive"
        class="hover:bg-elevated focus-visible:ring-primary data-[active=true]:bg-elevated data-[active=true]:text-highlighted h-item-md flex w-full items-center gap-2 rounded-sm px-2 text-start text-sm outline-none focus-visible:ring-2 focus-visible:ring-inset data-[active=true]:font-medium [&>span:last-child]:truncate"
    >
        <slot />
    </button>
</template>
