<script setup lang="ts">
/**
 * @component ResponsePlainView
 * @description Shows the raw request as sent and the raw response received.
 */
import { AppScrollArea } from '@/components/base/scroll-area';
import PanelSubHeader from '@/components/layout/PanelSubHeader/PanelSubHeader.vue';
import type { Response } from '@/interfaces/http';
import { formatResponsePlainView } from '@/utils/request/formatResponsePlainView';
import prettyBytes from 'pretty-bytes';
import { computed } from 'vue';

/*
 * Types & Interfaces.
 */

export interface ResponsePlainViewProps {
    response: Response;
    durationInMs: number;
}

/*
 * Component Setup.
 */

const props = defineProps<ResponsePlainViewProps>();

/*
 * Computed & Methods.
 */

const summary = computed(
    () =>
        `${props.durationInMs} ms, ${prettyBytes(props.response.sizeInBytes, { space: false })}`,
);

const text = computed(() => formatResponsePlainView(props.response));
</script>

<template>
    <PanelSubHeader class="border-b" data-testid="response-plain-view-summary">
        {{ summary }}
    </PanelSubHeader>
    <AppScrollArea class="min-h-0 flex-1">
        <pre
            class="p-panel font-mono text-xs break-all whitespace-pre-wrap"
            data-testid="response-plain-view-text"
            >{{ text }}</pre
        >
    </AppScrollArea>
</template>
