<script setup lang="ts">
import KeyValueParametersBuilder from '@/components/common/KeyValueParameters/KeyValueParameters.vue';
import PanelSubHeader from '@/components/layout/PanelSubHeader/PanelSubHeader.vue';
import { GeneratorType, RequestHeader, SourceGlobalHeaders } from '@/interfaces/http';
import { ParametersExternalContract } from '@/interfaces/ui';
import { useConfigStore, useRequestStore, useValueGeneratorStore } from '@/stores';
import { computed, onBeforeMount, ref, watch } from 'vue';

const requestStore = useRequestStore();
const configStore = useConfigStore();
const valueGeneratorStore = useValueGeneratorStore();

const headers = ref<RequestHeader[]>([]);

const pendingRequestData = computed(() => requestStore.pendingRequestData);

const globalHeaders = computed(() => {
    return configStore.headers.map(
        (globalHeader: SourceGlobalHeaders): RequestHeader => ({
            key: globalHeader.header,
            value:
                globalHeader.type === 'generator'
                    ? generateValue(globalHeader.value as GeneratorType)
                    : globalHeader.value,
        }),
    );
});

/**
 * Converts RequestHeader[] to ParameterContractShape[] for the KeyValueParameters component.
 */
const headersAsParameters = computed({
    get: (): ParametersExternalContract[] => {
        return headers.value.map((header: RequestHeader) => ({
            type: 'text',
            key: header.key,
            value: String(header.value),
        }));
    },
    set: (parameters: ParametersExternalContract[]) => {
        headers.value = parameters.map(
            (parameter: ParametersExternalContract): RequestHeader => ({
                key: parameter.key,
                value: parameter.value as string | number | boolean | null,
            }),
        );
    },
});

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

const syncHeadersWithPendingRequest = () => {
    if (pendingRequestData.value === null) {
        return;
    }

    requestStore.updateRequestHeaders(
        headers.value.filter((header: RequestHeader) => header.value !== null),
    );
};

const initializeHeaders = () => {
    // TODO [Enhancement] De-dupe the list.
    headers.value = [
        ...globalHeaders.value,
        ...(pendingRequestData.value?.headers ?? []),
    ];
};

/*
 * Watchers.
 */

watch(
    pendingRequestData,
    (newValue, oldValue) => {
        // Only reinitialize if endpoint actually changed
        if (
            newValue?.endpoint === oldValue?.endpoint &&
            newValue?.method === oldValue?.method
        ) {
            return;
        }

        initializeHeaders();

        syncHeadersWithPendingRequest();
    },
    { deep: true },
);

/*
 * Lifecycle.
 */

onBeforeMount(() => {
    initializeHeaders();
});
</script>

<template>
    <PanelSubHeader class="border-b">Request Headers</PanelSubHeader>
    <KeyValueParametersBuilder ref="parametersBuilder" v-model="headersAsParameters" />
</template>
