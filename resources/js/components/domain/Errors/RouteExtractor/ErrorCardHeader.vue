<script setup lang="ts">
/**
 * @component ErrorCardHeader
 * @description Header for the error card displaying the error message.
 */
import AppRoundIndicator from '@/components/base/round-indicator/AppRoundIndicator.vue';

/*
 * Types & Interfaces.
 */

export interface ErrorCardHeaderProps {
    message: string;
}

/*
 * Component Setup.
 */

const props = defineProps<ErrorCardHeaderProps>();
</script>

<template>
    <div class="gap-base px-card py-base flex items-start border-b">
        <div class="h-item-sm flex shrink-0 items-center">
            <AppRoundIndicator class="text-error" />
        </div>
        <div class="min-w-0">
            <h1 class="text-highlighted h-item-sm flex items-center text-sm font-medium">
                Route extraction failed
            </h1>
            <p class="text-muted pb-tight text-xs break-words">{{ props.message }}</p>
        </div>
    </div>
</template>
