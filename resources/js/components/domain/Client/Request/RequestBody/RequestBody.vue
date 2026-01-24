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
import RequestBodyContent from './RequestBodyContent.vue';
import RequestBodyErrorDisplay from './RequestBodyErrorDisplay.vue';

/*
 * Types & Interfaces.
 */

export interface AppRequestBodyProps {}

/*
 * Component Setup.
 */

defineProps<AppRequestBodyProps>();

/*
 * Composables.
 */

const { payloadType, payload, pendingRequestData, supportsAutoFill, autofill, types } =
    useRequestBody();
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

        <RequestBodyErrorDisplay
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
