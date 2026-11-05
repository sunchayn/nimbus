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
        class="hover:bg-elevated data-[active=true]:bg-elevated data-[active=true]:text-highlighted h-item-md gap-base px-base flex w-full items-center rounded-sm text-start text-sm data-[active=true]:font-medium [&>span:last-child]:truncate"
    >
        <slot />
    </button>
</template>
