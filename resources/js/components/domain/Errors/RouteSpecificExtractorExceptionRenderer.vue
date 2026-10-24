<script setup lang="ts">
/**
 * @component RouteSpecificExtractorExceptionRenderer
 * @description A specialized error renderer for specific route extraction exceptions.
 */
import { AppButton } from '@/components/base/button';
import { type RouteExtractorException } from '@/interfaces/routes';
import { shallowRef } from 'vue';
import ErrorCardHeader from './RouteExtractor/ErrorCardHeader.vue';
import RouteInformationSection from './RouteExtractor/RouteInformationSection.vue';
import SuggestedSolutionCallout from './RouteExtractor/SuggestedSolutionCallout.vue';
import TechnicalDetailsSection from './RouteExtractor/TechnicalDetailsSection.vue';

/*
 * Types & Interfaces.
 */

export interface RouteSpecificExtractorExceptionRendererProps {
    error: RouteExtractorException;
}

/*
 * Component Setup.
 */

const props = defineProps<RouteSpecificExtractorExceptionRendererProps>();

/*
 * State.
 */

const isIgnoring = shallowRef(false);

/*
 * Event Handlers.
 */

const handleRetry = () => {
    window.location.reload();
};

const handleIgnoreEndpoint = () => {
    if (isIgnoring.value) {
        return;
    }

    if (!props.error.ignoreData) {
        return;
    }

    isIgnoring.value = true;
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('ignore', props.error.ignoreData);
    window.location.href = currentUrl.toString();
};
</script>

<template>
    <section class="pane-surface flex w-full flex-col">
        <ErrorCardHeader :message="error.exception.message" />

        <div class="gap-base p-card flex flex-col">
            <SuggestedSolutionCallout
                v-if="error.suggestedSolution"
                :solution="error.suggestedSolution"
            />
            <RouteInformationSection :route-context="error.routeContext" />
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
                :disabled="isIgnoring"
                @click="handleRetry"
            />
            <AppButton
                :disabled="isIgnoring"
                :loading="isIgnoring"
                size="xs"
                icon="i-hugeicons-next"
                :label="isIgnoring ? 'Ignoring...' : 'Ignore this Endpoint'"
                @click="handleIgnoreEndpoint"
            />
        </div>
    </section>
</template>
