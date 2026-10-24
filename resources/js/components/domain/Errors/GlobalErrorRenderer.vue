<script setup lang="ts">
/**
 * @component GlobalErrorRenderer
 * @description A generic error renderer for global exceptions.
 */
import { AppButton } from '@/components/base/button';
import { type GlobalException } from '@/interfaces/routes';
import ErrorCardHeader from './RouteExtractor/ErrorCardHeader.vue';
import SuggestedSolutionCallout from './RouteExtractor/SuggestedSolutionCallout.vue';
import TechnicalDetailsSection from './RouteExtractor/TechnicalDetailsSection.vue';

/*
 * Types & Interfaces.
 */

export interface GlobalErrorRendererProps {
    error: GlobalException;
}

/*
 * Component Setup.
 */

defineProps<GlobalErrorRendererProps>();

/*
 * Event Handlers.
 */

const handleRetry = () => {
    window.location.reload();
};
</script>

<template>
    <section class="pane-surface flex w-full flex-col">
        <ErrorCardHeader :message="error.exception.message" />

        <div
            v-if="error.suggestedSolution || error.exception.previous"
            class="gap-base p-card flex flex-col"
        >
            <SuggestedSolutionCallout
                v-if="error.suggestedSolution"
                :solution="error.suggestedSolution"
            />
            <TechnicalDetailsSection
                v-if="error.exception.previous"
                :previous-error="error.exception.previous"
            />
        </div>

        <div class="gap-base px-card py-base flex border-t">
            <AppButton
                variant="outline"
                size="xs"
                icon="i-hugeicons-reload"
                label="Retry"
                @click="handleRetry"
            />
        </div>
    </section>
</template>
