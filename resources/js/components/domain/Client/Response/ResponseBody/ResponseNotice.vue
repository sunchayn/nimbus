<script setup lang="ts">
/**
 * @component ResponseNotice
 * @description A bordered card with a labeled status indicator and a body slot, used for response info, empty and error states.
 */
import AppRoundIndicator from '@/components/base/round-indicator/AppRoundIndicator.vue';

/*
 * Types & Interfaces.
 */

export interface ResponseNoticeProps {
    label: string;
    indicatorClass: string;
}

/*
 * Component Setup.
 */

defineProps<ResponseNoticeProps>();
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
