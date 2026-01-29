<script setup lang="ts">
/**
 * @component GlobalErrorRenderer
 * @description A generic error renderer for global exceptions.
 */
import AppPanelRipple from '@/components/base/AppPanelRipple.vue';
import { AppBadge } from '@/components/base/badge';
import { AppButton } from '@/components/base/button';
import { type GlobalException } from '@/interfaces/routes';
import { RefreshCcwIcon } from 'lucide-vue-next';
import ErrorCardHeader from './RouteExtractor/ErrorCardHeader.vue';
import SuggestedSolutionCallout from './RouteExtractor/SuggestedSolutionCallout.vue';
import TechnicalDetailsSection from './RouteExtractor/TechnicalDetailsSection.vue';

/*
 * Types & Interfaces.
 */

export interface AppGlobalErrorRendererProps {
    error: GlobalException;
}

/*
 * Component Setup.
 */

defineProps<AppGlobalErrorRendererProps>();

/*
 * Event Handlers.
 */

const handleRetry = () => {
    window.location.reload();
};
</script>

<template>
    <div
        class="from-destructive/5 dark:from-destructive/15 relative h-full max-h-full w-full bg-gradient-to-br to-transparent p-4"
    >
        <div
            class="relative z-10 flex h-full w-full items-center justify-center overflow-auto py-2"
        >
            <div class="flex h-full flex-col space-y-4">
                <div class="space-y-2">
                    <AppBadge variant="outline" class="p-0 px-1 text-xs">
                        Internal Error
                    </AppBadge>
                    <h1 class="text-destructive text-xl font-medium">
                        An error occurred while processing your application routes
                    </h1>
                </div>

                <div
                    class="bg-subtle/10 relative flex flex-col overflow-hidden rounded-md border-1 break-words backdrop-blur-md"
                >
                    <ErrorCardHeader :message="error.exception.message" />

                    <SuggestedSolutionCallout
                        v-if="error.suggestedSolution"
                        :solution="error.suggestedSolution"
                        class="rounded-none border-0"
                    />

                    <div
                        v-if="error.exception.previous"
                        class="relative z-10 flex flex-1 flex-col space-y-4 overflow-auto p-4"
                    >
                        <TechnicalDetailsSection
                            class="flex-1"
                            :previous-error="error.exception.previous"
                        />
                    </div>
                </div>

                <div class="flex gap-4">
                    <AppButton variant="outline" size="xs" @click="handleRetry">
                        <RefreshCcwIcon />
                        Retry
                    </AppButton>
                </div>
            </div>
        </div>
        <AppPanelRipple />
    </div>
</template>
