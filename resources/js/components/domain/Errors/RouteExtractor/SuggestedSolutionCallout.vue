<script setup lang="ts">
/**
 * @component SuggestedSolutionCallout
 * @description A callout box displaying a suggested solution for the error.
 */
import { cn } from '@/utils/ui';
import { LightbulbIcon } from 'lucide-vue-next';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppSuggestedSolutionCalloutProps {
    solution: string;
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppSuggestedSolutionCalloutProps>();
</script>

<template>
    <div :class="cn('bg-subtle rounded-lg border p-2', props.class)">
        <div class="flex items-start gap-2.5">
            <LightbulbIcon class="mt-1 size-5 flex-shrink-0" />
            <div class="flex-1">
                <h3 class="font-semibold">Suggested Solution</h3>
                <!-- eslint-disable-next-line vue/no-v-html -->
                <p class="text-sm" v-html="solution" />
            </div>
        </div>
    </div>
</template>
