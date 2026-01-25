<script setup lang="ts">
/**
 * @component RequestBuilderEndpoint
 * @description The endpoint input and method selector for the request builder.
 */
import { cn } from '@/utils/ui';
import { type HTMLAttributes } from 'vue';
import RequestBuilderEndpointInput from './RequestBuilderEndpointInput.vue';
import RequestBuilderMethodSelector from './RequestBuilderMethodSelector.vue';
import RequestBuilderOptionsMenu from './RequestBuilderOptionsMenu.vue';

/*
 * Types & Interfaces.
 */

export interface AppRequestBuilderEndpointProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppRequestBuilderEndpointProps>();
</script>

<template>
    <div :class="cn('flex', props.class)" data-testid="request-builder-endpoint">
        <RequestBuilderMethodSelector />

        <RequestBuilderEndpointInput>
            <template #options-menu>
                <RequestBuilderOptionsMenu />
            </template>
        </RequestBuilderEndpointInput>
    </div>
</template>
