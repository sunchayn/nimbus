<script setup lang="ts">
/**
 * @component RequestBody
 * @description The container for the request body configuration, supporting multiple payload types.
 */
import {
    RequestBodyAutoFillButton,
    RequestBodySelector,
} from '@/components/domain/Client/Request';
import PanelSubHeader from '@/components/layout/PanelSubHeader/PanelSubHeader.vue';
import { useRequestBody } from '@/composables/request/useRequestBody';
import { useRequestStore, useRoutesStore } from '@/stores';
import { computed } from 'vue';
import ImplementationMissingWarning from './Hints/ImplementationMissingWarning.vue';
import RouteAbsentInPrimarySourceInfo from './Hints/MissingRouteInPrimarySourceInfo.vue';
import RouteExtractionError from './Hints/RouteExtractionError.vue';
import RequestBodyContent from './RequestBodyContent.vue';

/*
 * Types & Interfaces.
 */

export interface AppRequestBodyProps {}

/*
 * Component Setup.
 */

defineProps<AppRequestBodyProps>();

/*
 * Stores & dependencies.
 */

const requestStore = useRequestStore();
const routesStore = useRoutesStore();

/*
 * Composables.
 */

const { payloadType, payload, pendingRequestData, supportsAutoFill, autofill, types } =
    useRequestBody();

/*
 * Computed & Methods.
 */

const currentRoute = computed(() => requestStore.pendingRequestData?.routeDefinition);

const isImplementationMissing = computed(() => {
    if (!currentRoute.value) {
        return false;
    }

    return routesStore.isMissingImplementation(currentRoute.value);
});

const showPrimarySourceRouteImplementationMissingWarning = computed(() => {
    return isImplementationMissing.value;
});

const showRouteAbsentInPrimaryProcessorInfo = computed(function () {
    const route = requestStore.pendingRequestData?.routeDefinition;

    if (!route) {
        return false;
    }

    return routesStore.isUndocumented(route);
});
</script>

<template>
    <div class="flex min-h-0 flex-1 flex-col">
        <PanelSubHeader class="border-b">
            <RequestBodySelector v-model="payloadType" :types="types" />
            <template #toolbox>
                <RequestBodyAutoFillButton
                    :disabled="!supportsAutoFill"
                    @click="autofill"
                />
            </template>
        </PanelSubHeader>

        <ImplementationMissingWarning
            v-if="showPrimarySourceRouteImplementationMissingWarning"
        />

        <RouteAbsentInPrimarySourceInfo v-if="showRouteAbsentInPrimaryProcessorInfo" />

        <RouteExtractionError
            v-if="pendingRequestData?.schema?.extractionErrors != null"
            :extraction-error="pendingRequestData?.schema?.extractionErrors"
        />

        <RequestBodyContent
            :payload-type="payloadType"
            :payload="payload"
            :schema="pendingRequestData?.schema?.shape"
            @update:payload="payload = $event"
        />
    </div>
</template>
