<script setup lang="ts">
/**
 * @component TechnicalDetailsSection
 * @description Displays technical details of the exception (backtrace, file, line) with copy functionality.
 */
import CopyButton from '@/components/common/CopyButton.vue';
import { type ExceptionPrevious } from '@/interfaces/routes';
import { useClipboard } from '@vueuse/core';

/*
 * Types & Interfaces.
 */

export interface AppTechnicalDetailsSectionProps {
    previousError: ExceptionPrevious;
}

/*
 * Component Setup.
 */

const props = defineProps<AppTechnicalDetailsSectionProps>();

const { copy, copied } = useClipboard();

/*
 * Methods.
 */

const copyValue = () => {
    let value = props.previousError.message;

    if (props.previousError.file) {
        value += `\n${props.previousError.file}::${props.previousError.line}`;
    }

    if (props.previousError.trace) {
        value += `\n${props.previousError.trace}`;
    }

    copy(String(value));
};
</script>

<template>
    <div>
        <h3 class="text-foreground mb-2 font-semibold">
            Technical Details
            <CopyButton :on-click="copyValue" :copied="copied" />
        </h3>
        <div class="bg-destructive/10 dark:bg-destructive/30 rounded-lg p-4">
            <p class="text-destructive font-mono text-sm">
                {{ previousError.message }}
            </p>
            <div
                v-if="previousError.file"
                class="text-destructive mt-2 text-sm wrap-break-word"
            >
                <p>{{ previousError.file }}:{{ previousError.line }}</p>
                <div v-if="previousError.trace" class="mt-2">
                    Trace:
                    <br />
                    <!-- eslint-disable vue/no-v-html -->
                    <span
                        class="text-subtle-foreground block"
                        v-html="previousError.trace"
                    ></span>
                    <!-- eslint-enable vue/no-v-html -->
                </div>
            </div>
        </div>
    </div>
</template>
