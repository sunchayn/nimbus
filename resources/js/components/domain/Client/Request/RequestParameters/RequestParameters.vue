<script setup lang="ts">
import CopyButton from '@/components/common/CopyButton.vue';
import KeyValueParametersBuilder from '@/components/common/KeyValueParameters/KeyValueParameters.vue';
import PanelSubHeader from '@/components/layout/PanelSubHeader/PanelSubHeader.vue';
import { ParameterContract } from '@/interfaces/ui';
import { useRequestStore } from '@/stores';
import { useClipboard } from '@vueuse/core';
import { computed } from 'vue';

/*
 * Stores & dependencies.
 */

const requestStore = useRequestStore();
const { copy, copied: previewCopied } = useClipboard();

/*
 * Computed.
 */

const pendingRequestData = computed(() => requestStore.pendingRequestData);

const currentRequestQueryParameters = computed<ParameterContract[]>(
    () => pendingRequestData.value?.queryParameters ?? [],
);

const handleQueryParametersUpdate = (parameters: ParameterContract[]) => {
    requestStore.updateQueryParameters(parameters);
};

const preview = computed(() =>
    pendingRequestData.value ? requestStore.getRequestUrl(pendingRequestData.value) : '',
);

/*
 * Actions.
 */

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
