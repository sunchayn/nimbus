<script setup lang="ts">
/**
 * @component SuggestedSolutionCallout
 * @description A callout box displaying a suggested solution for the error.
 */
import { LightbulbIcon } from 'lucide-vue-next';

/*
 * Types & Interfaces.
 */

export interface AppSuggestedSolutionCalloutProps {
    solution: string;
}

/*
 * Component Setup.
 */

defineProps<AppSuggestedSolutionCalloutProps>();
</script>

<template>
    <div class="bg-subtle-background rounded-lg border p-2">
        <div class="flex items-start gap-3">
            <LightbulbIcon class="mt-1 size-5 flex-shrink-0" />
            <div class="flex-1">
                <h3 class="mb-1 font-semibold">Suggested Solution</h3>
                <!-- eslint-disable-next-line vue/no-v-html -->
                <p class="text-sm" v-html="solution" />
            </div>
        </div>
    </div>
</template>
