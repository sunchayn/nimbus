<script setup lang="ts">
/**
 * @component SuggestedSolutionCallout
 * @description A callout box displaying a suggested solution for the error.
 */
import { AppIcon } from '@/components/base/icons';
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface SuggestedSolutionCalloutProps {
    solution: string;
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<SuggestedSolutionCalloutProps>();
</script>

<template>
    <div :class="cn('bg-muted p-base rounded-md border', props.class)">
        <div class="gap-base flex items-start">
            <AppIcon name="i-hugeicons-bulb" class="size-icon-sm mt-tight shrink-0" />
            <div class="flex-1">
                <h3 class="text-highlighted text-xs font-medium">Suggested Solution</h3>
                <!-- eslint-disable-next-line vue/no-v-html -->
                <p class="text-muted text-xs" v-html="solution" />
            </div>
        </div>
    </div>
</template>
