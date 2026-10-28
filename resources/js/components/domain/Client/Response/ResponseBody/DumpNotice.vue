<script setup lang="ts">
/**
 * @component DumpNotice
 * @description A bordered card with a labeled status indicator and a body slot, used for dump empty and error states.
 */
import AppRoundIndicator from '@/components/base/round-indicator/AppRoundIndicator.vue';

/*
 * Types & Interfaces.
 */

export interface DumpNoticeProps {
    label: string;
    indicatorClass: string;
}

/*
 * Component Setup.
 */

defineProps<DumpNoticeProps>();
</script>

<template>
    <div class="border-muted bg-default rounded-md border text-sm">
        <div class="px-panel gap-base py-tight flex items-center border-b">
            <AppRoundIndicator :class="indicatorClass" />
            {{ label }}
        </div>
        <div class="p-panel">
            <slot />
        </div>
    </div>
</template>
