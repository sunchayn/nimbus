<script setup lang="ts">
import KeyValueParametersBuilder from '@/components/common/KeyValueParameters/KeyValueParameters.vue';
import PanelSubHeader from '@/components/layout/PanelSubHeader/PanelSubHeader.vue';
import { GeneratorType, SourceGlobalHeaders } from '@/interfaces/http';
import { ParameterContract } from '@/interfaces/ui';
import { ParameterType } from '@/interfaces/ui/key-value-parameters';
import { useConfigStore, useRequestStore, useValueGeneratorStore } from '@/stores';
import { computed, onBeforeMount, Ref, ref } from 'vue';

const requestStore = useRequestStore();
const configStore = useConfigStore();
const valueGeneratorStore = useValueGeneratorStore();

const pendingRequestData = computed(() => requestStore.pendingRequestData);

const globalHeaders: Ref<ParameterContract[]> = ref([]);

const generateValue = (value: GeneratorType): string => {
    switch (value) {
        case GeneratorType.Uuid:
            return valueGeneratorStore.generateValue('uuid') as string;
        case GeneratorType.Email:
            return valueGeneratorStore.generateValue('email') as string;
        case GeneratorType.String:
            return valueGeneratorStore.generateValue('word') as string;
        default:
            return valueGeneratorStore.generateValue('word') as string;
    }
};

const syncHeadersWithPendingRequest = (headers: ParameterContract[]) => {
    requestStore.updateRequestHeaders(headers);
};

const currentRequestHeaders = computed<ParameterContract[]>(
    () => pendingRequestData.value?.headers ?? [],
);

const effectiveHeaders = computed<ParameterContract[]>(() => {
    const currentHeaders = currentRequestHeaders.value;

    if (currentHeaders.length === 0) {
        return globalHeaders.value;
    }

    // Don't mess up with the current headers if the pending request have them already.
    // They can be coming from history re-wind or persisted state.
    return currentHeaders;
});

const handleHeadersUpdate = (parameters: ParameterContract[]) => {
    syncHeadersWithPendingRequest(parameters);
};

/*
 * Lifecycle.
 */

onBeforeMount(() => {
    globalHeaders.value = configStore.headers.map(
        (globalHeader: SourceGlobalHeaders): ParameterContract => ({
            type: ParameterType.Text,
            key: globalHeader.header,
            value:
                globalHeader.type === 'generator'
                    ? generateValue(globalHeader.value as GeneratorType)
                    : String(globalHeader.value),
            enabled: true,
        }),
    );
});
</script>

<template>
    <PanelSubHeader class="border-b">Request Headers</PanelSubHeader>
    <KeyValueParametersBuilder
        ref="parametersBuilder"
        :model-value="effectiveHeaders"
        @update:parameters="handleHeadersUpdate"
    />
</template>
