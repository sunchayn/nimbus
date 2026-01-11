<script setup lang="ts">
import CopyButton from '@/components/common/CopyButton.vue';
import KeyValueParametersBuilder from '@/components/common/KeyValueParameters/KeyValueParameters.vue';
import PanelSubHeader from '@/components/layout/PanelSubHeader/PanelSubHeader.vue';
import { ParametersExternalContract } from '@/interfaces/ui';
import { useRequestStore } from '@/stores';
import { useClipboard, watchDebounced } from '@vueuse/core';
import { computed, ref, watch } from 'vue';

/*
 * Stores & dependencies.
 */

const requestStore = useRequestStore();
const { copy, copied: previewCopied } = useClipboard();

/*
 * State.
 */

const parameters = ref<ParametersExternalContract[]>([]);

const preview = ref<string>('');

/*
 * Computed.
 */

const pendingRequestData = computed(() => requestStore.pendingRequestData);

/*
 * Actions.
 */

const copyPreview = () => copy(preview.value);

/*
 * Watchers.
 */

watchDebounced(
    parameters,
    () => {
        if (pendingRequestData.value === null) {
            return;
        }

        requestStore.updateQueryParameters(parameters.value);

        preview.value = requestStore.getRequestUrl(pendingRequestData.value);
    },
    { deep: true, debounce: 200 },
);

watch(
    () => pendingRequestData.value?.endpoint,
    (newEndpoint, oldEndpoint) => {
        if (newEndpoint === oldEndpoint) {
            return;
        }

        if (!pendingRequestData.value) {
            return;
        }

        parameters.value = pendingRequestData.value.queryParameters.map(
            (parameter: ParametersExternalContract): ParametersExternalContract => ({
                key: parameter.key,
                value: parameter.value,
            }),
        );
    },
    { immediate: true },
);
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
        v-model="parameters"
        class="flex-1"
        persistence-key="pending-request-parameters"
    />
</template>
