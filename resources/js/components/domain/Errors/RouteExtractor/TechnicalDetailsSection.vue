<script setup lang="ts">
/**
 * @component TechnicalDetailsSection
 * @description Displays technical details of the exception (backtrace, file, line) with copy functionality.
 */
import { AppAlert } from '@/components/base/alert';
import CopyButton from '@/components/common/CopyButton.vue';
import { type ExceptionPrevious } from '@/interfaces/routes';
import { useClipboard } from '@vueuse/core';

/*
 * Types & Interfaces.
 */

export interface TechnicalDetailsSectionProps {
    previousError: ExceptionPrevious;
}

/*
 * Component Setup.
 */

const props = defineProps<TechnicalDetailsSectionProps>();

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
    <div class="gap-base flex flex-col">
        <h3 class="text-highlighted flex items-center text-xs font-medium">
            Technical Details
            <CopyButton :on-click="copyValue" :copied="copied" />
        </h3>
        <AppAlert color="error" variant="subtle">
            <template #description>
                <p class="font-mono text-xs">
                    {{ previousError.message }}
                </p>
                <div
                    v-if="previousError.file"
                    class="mt-base gap-base flex flex-col text-xs wrap-break-word"
                >
                    <p>{{ previousError.file }}:{{ previousError.line }}</p>
                    <div v-if="previousError.trace">
                        Trace:
                        <br />
                        <!-- eslint-disable vue/no-v-html -->
                        <span
                            class="text-muted block"
                            v-html="previousError.trace"
                        ></span>
                        <!-- eslint-enable vue/no-v-html -->
                    </div>
                </div>
            </template>
        </AppAlert>
    </div>
</template>
