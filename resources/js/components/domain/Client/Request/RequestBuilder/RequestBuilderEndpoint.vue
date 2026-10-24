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

export interface RequestBuilderEndpointProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<RequestBuilderEndpointProps>();
</script>

<template>
    <div
        :class="
            cn(
                'pane-surface focus-within:border-accented flex shrink-0 items-center items-stretch',
                props.class,
            )
        "
        data-testid="request-builder-endpoint"
    >
        <RequestBuilderMethodSelector />

        <RequestBuilderEndpointInput>
            <template #options-menu>
                <RequestBuilderOptionsMenu />
            </template>
        </RequestBuilderEndpointInput>
    </div>
</template>
