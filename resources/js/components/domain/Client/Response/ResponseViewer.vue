<script setup lang="ts">
/**
 * @component ResponseViewer
 * @description The main container for displaying the response of an API request.
 */
import ResponseStatus from '@/components/domain/Client/Response/ResponseStatus/ResponseStatus.vue';
import ResponseViewerEmptyState from '@/components/domain/Client/Response/ResponseViewerEmptyState.vue';
import ResponseViewerInternalError from '@/components/domain/Client/Response/ResponseViewerErrorState.vue';
import ResponseViewerResponse from '@/components/domain/Client/Response/ResponseViewerResponse.vue';
import { useTabsStore } from '@/stores';
import { cn } from '@/utils/ui';
import { type PrimitiveProps } from 'reka-ui';
import { computed, type HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppResponseViewerProps extends PrimitiveProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppResponseViewerProps>();

/*
 * Stores.
 */

const tabsStore = useTabsStore();

/*
 * Computed & Methods.
 */

const lastLog = computed(() => tabsStore.activeResponse);
</script>

<template>
    <div
        :class="cn('bg-background flex h-full flex-col', props.class)"
        data-testid="response-content"
    >
        <ResponseStatus :response="lastLog" class="border-b" />
        <ResponseViewerEmptyState v-if="!lastLog" data-testid="response-empty" />
        <ResponseViewerInternalError
            v-else-if="lastLog.error"
            data-testid="response-error"
            :error="lastLog.error"
        />
        <ResponseViewerResponse v-else :response="lastLog" />
    </div>
</template>
