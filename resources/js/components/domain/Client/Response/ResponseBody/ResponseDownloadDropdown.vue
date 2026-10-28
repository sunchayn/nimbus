<script setup lang="ts">
/**
 * @component ResponseDownloadDropdown
 * @description Provides a dropdown menu with options to download the raw response or its schema shape.
 */
import { AppButton } from '@/components/base/button';
import type { AppDropdownMenuItem } from '@/components/base/dropdown-menu';
import { AppDropdownMenu } from '@/components/base/dropdown-menu';
import useResponseDownload from '@/composables/response/useResponseDownload';
import type { RequestLog } from '@/interfaces/history/logs';
import { computed } from 'vue';

/*
 * Types & Interfaces.
 */

export interface ResponseDownloadDropdownProps {
    response?: RequestLog | null;
}

/*
 * Component Setup.
 */

const props = defineProps<ResponseDownloadDropdownProps>();

const responseRef = computed(() => props.response);

const { isResolvingShape, isJsonResponse, downloadRawResponse, downloadResponseShape } =
    useResponseDownload(responseRef);

const items = computed<AppDropdownMenuItem[][]>(() => [
    [
        { label: 'Options', type: 'label' },
        {
            label: 'Raw response',
            testId: 'download-raw-option',
            onSelect: downloadRawResponse,
        },
        {
            label: 'JSON shape',
            testId: 'download-shape-option',
            loading: isResolvingShape.value,
            disabled: isResolvingShape.value || !isJsonResponse.value,
            onSelect: downloadResponseShape,
        },
    ],
]);
</script>

<template>
    <AppDropdownMenu :items="items" :content="{ align: 'end' }">
        <AppButton
            variant="ghost"
            color="neutral"
            size="sm"
            icon="i-hugeicons-download-04"
            aria-label="Download"
            data-testid="download-dropdown-trigger"
        />

        <template #item-label="{ item }">
            <span :data-testid="item.testId">{{ item.label }}</span>
        </template>
    </AppDropdownMenu>
</template>
