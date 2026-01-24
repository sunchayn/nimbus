<script setup lang="ts">
/**
 * @component RequestParameters
 * @description Manages URL query parameters for the current request.
 */
import CopyButton from '@/components/common/CopyButton.vue';
import KeyValueParametersBuilder from '@/components/common/KeyValueParameters/KeyValueParameters.vue';
import PanelSubHeader from '@/components/layout/PanelSubHeader/PanelSubHeader.vue';
import { type ParameterContract } from '@/interfaces/ui';
import { useRequestStore } from '@/stores';
import { useClipboard } from '@vueuse/core';
import { computed } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppRequestParametersProps {}

/*
 * Component Setup.
 */

defineProps<AppRequestParametersProps>();

/*
 * Stores & Dependencies.
 */

const requestStore = useRequestStore();
const { copy, copied: previewCopied } = useClipboard();

/*
 * Computed & Methods.
 */

const pendingRequestData = computed(() => requestStore.pendingRequestData);

const currentRequestQueryParameters = computed<ParameterContract[]>(
    () => pendingRequestData.value?.queryParameters ?? [],
);

const preview = computed(() =>
    pendingRequestData.value ? requestStore.getRequestUrl(pendingRequestData.value) : '',
);

const handleQueryParametersUpdate = (parameters: ParameterContract[]) => {
    requestStore.updateQueryParameters(parameters);
};

const copyPreview = () => copy(preview.value);
</script>

<template>
    <PanelSubHeader class="border-b">Query Parameters</PanelSubHeader>
    <div class="px-panel bg-subtle-background flex border-b py-2 text-xs">
        <div class="flex-1">
            <small class="font-medium">URL Preview</small>
            <p v-if="preview.length">{{ preview }}</p>
            <p v-else class="text-subtle-foreground">Pick an endpoint to start</p>
        </div>
        <div>
            <CopyButton :on-click="copyPreview" :copied="previewCopied" />
        </div>
    </div>
    <KeyValueParametersBuilder
        :model-value="currentRequestQueryParameters"
        class="flex-1"
        @update:parameters="handleQueryParametersUpdate"
    />
</template>
