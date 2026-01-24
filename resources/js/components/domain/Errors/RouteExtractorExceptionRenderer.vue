<script setup lang="ts">
/**
 * @component RouteExtractorExceptionRenderer
 * @description A specialized error renderer for route extraction exceptions.
 */
import AppPanelRipple from '@/components/base/AppPanelRipple.vue';
import { AppBadge } from '@/components/base/badge';
import { AppButton } from '@/components/base/button';
import { type RouteExtractorException } from '@/interfaces/routes';
import { Loader2Icon, RefreshCcwIcon, SkipForwardIcon } from 'lucide-vue-next';
import { ref } from 'vue';
import ErrorCardHeader from './RouteExtractor/ErrorCardHeader.vue';
import RouteInformationSection from './RouteExtractor/RouteInformationSection.vue';
import SuggestedSolutionCallout from './RouteExtractor/SuggestedSolutionCallout.vue';
import TechnicalDetailsSection from './RouteExtractor/TechnicalDetailsSection.vue';

/*
 * Types & Interfaces.
 */

export interface AppRouteExtractorExceptionRendererProps {
    error: RouteExtractorException;
}

/*
 * Component Setup.
 */

const props = defineProps<AppRouteExtractorExceptionRendererProps>();

/*
 * State.
 */

const isIgnoring = ref(false);

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
    <div
        class="from-destructive/10 dark:from-destructive/15 relative h-full max-h-full w-full bg-gradient-to-br to-transparent p-4"
    >
        <div class="relative z-10 flex h-full w-full justify-center overflow-auto py-2">
            <div class="flex h-full flex-col space-y-4">
                <div class="space-y-2">
                    <AppBadge variant="outline" class="p-0 px-1 text-xs">
                        Internal Error
                    </AppBadge>
                    <h1 class="text-destructive text-xl font-medium">
                        An error occurred while processing your application routes
                    </h1>
                </div>

                <SuggestedSolutionCallout
                    v-if="error.suggestedSolution"
                    :solution="error.suggestedSolution"
                />

                <div
                    class="bg-muted/10 relative flex flex-1 flex-col overflow-hidden rounded-xl border-1 break-words backdrop-blur-md"
                >
                    <ErrorCardHeader :message="error.exception.message" />
                    <div
                        class="relative z-10 flex flex-1 flex-col space-y-4 overflow-auto p-4"
                    >
                        <RouteInformationSection :route-context="error.routeContext" />
                        <TechnicalDetailsSection
                            v-if="error.exception.previous"
                            class="flex-1"
                            :previous-error="error.exception.previous"
                        />
                    </div>

                    <div class="flex gap-4 px-4 py-2">
                        <AppButton
                            variant="outline"
                            :disabled="isIgnoring"
                            @click="handleRetry"
                        >
                            <RefreshCcwIcon />
                            Retry
                        </AppButton>
                        <AppButton
                            variant="default"
                            :disabled="isIgnoring"
                            @click="handleIgnoreEndpoint"
                        >
                            <Loader2Icon v-if="isIgnoring" class="animate-spin" />
                            <SkipForwardIcon v-else />
                            {{ isIgnoring ? 'Ignoring...' : 'Ignore this Endpoint' }}
                        </AppButton>
                    </div>
                </div>
            </div>
        </div>
        <AppPanelRipple />
    </div>
</template>
