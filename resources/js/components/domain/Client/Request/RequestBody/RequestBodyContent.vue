<script setup lang="ts">
/**
 * @component RequestBodyContent
 * @description Dynamic content renderer for the request body based on the selected payload type.
 */
import { RequestBodyTypeEnum } from '@/interfaces/http';
import type { JSONSchema7 } from 'json-schema';
import { AppScrollArea } from '@/components/base/scroll-area';
import RequestBodyFormData from './RequestBodyFormData.vue';
import RequestBodyFormNone from './RequestBodyFormNone.vue';
import RequestBodyJson from './RequestBodyJson.vue';
import RequestBodyPlainText from './RequestBodyPlainText.vue';

/*
 * Types & Interfaces.
 */

export interface AppRequestBodyContentProps {
    payloadType: RequestBodyTypeEnum;
    payload: FormData | string | null;
    schema?: JSONSchema7;
}

export interface AppRequestBodyContentEmits {
    (e: 'update:payload', value: FormData | string | null): void;
}

/*
 * Component Setup.
 */

defineProps<AppRequestBodyContentProps>();

const emit = defineEmits<AppRequestBodyContentEmits>();

/*
 * Event Handlers.
 */

const updatePayload = (value: FormData | string | null) => {
    emit('update:payload', value);
};
</script>

<template>
    <AppScrollArea class="min-h-0 w-full flex-1">
        <RequestBodyJson
            v-if="payloadType === RequestBodyTypeEnum.JSON"
            :model-value="payload as string"
            :schema="schema"
            @update:model-value="updatePayload"
        />
        <RequestBodyFormData
            v-else-if="payloadType === RequestBodyTypeEnum.FORM_DATA"
            :model-value="payload as FormData"
            @update:model-value="updatePayload"
        />
        <RequestBodyPlainText
            v-else-if="payloadType === RequestBodyTypeEnum.PLAIN_TEXT"
            :model-value="payload as string"
            @update:model-value="updatePayload"
        />
        <RequestBodyFormNone v-else @update:model-value="updatePayload" />
    </AppScrollArea>
</template>
