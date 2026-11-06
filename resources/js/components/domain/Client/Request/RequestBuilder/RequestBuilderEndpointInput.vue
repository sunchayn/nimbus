<script setup lang="ts">
/**
 * @component RequestBuilderEndpointInput
 * @description The endpoint input field and send button for the request builder.
 */
import { AppButton } from '@/components/base/button';
import { AppPopover } from '@/components/base/popover';
import { AppTooltip } from '@/components/base/tooltip';
import EnvironmentAwareInput from '@/components/common/EnvironmentAwareInput.vue';

import { useRouteParameterParsing } from '@/composables/request/useRouteParameterParsing';
import { useRouteSegmentSelection } from '@/composables/request/useRouteSegmentSelection';
import { useEnvironmentVariablesStore, useRequestStore } from '@/stores';
import { computed, shallowRef } from 'vue';
import RequestBuilderEndpointParameterWarningContent from './RequestBuilderEndpointParameterWarningContent.vue';
import RequestBuilderEndpointUrlWarningContent from './RequestBuilderEndpointUrlWarningContent.vue';

/*
 * Stores.
 */

const requestStore = useRequestStore();
const environmentVariablesStore = useEnvironmentVariablesStore();

/*
 * State.
 */

const showParameterWarning = shallowRef(false);
const showUrlWarning = shallowRef(false);

const warningOpen = computed({
    get: () => showParameterWarning.value || showUrlWarning.value,
    set: (open: boolean) => {
        if (!open) {
            showParameterWarning.value = false;
            showUrlWarning.value = false;
        }
    },
});

/*
 * Computed.
 */

const pendingRequestData = computed(() => requestStore.pendingRequestData);

const endpoint = computed({
    get: () => pendingRequestData.value?.endpoint ?? '',
    set: (value: string) => requestStore.updateRequestEndpoint(value),
});

/*
 * Composables.
 */

const { parameters, hasParameters } = useRouteParameterParsing(endpoint);

/*
 * Actions.
 */

const { handleClick: autoSelectRouteVariableSegmentWhenApplicable } =
    useRouteSegmentSelection({ endpoint });

const executeCurrentRequest = async function () {
    if (!requestStore.pendingRequestData) {
        return;
    }

    showUrlWarning.value = false;

    if (hasParameters.value) {
        showParameterWarning.value = true;

        return;
    }

    showParameterWarning.value = false;

    await requestStore.executeCurrentRequest();
};

// A pasted full URL keeps only its path when it targets the active base URL.
const handlePaste = (event: ClipboardEvent) => {
    const pasted = event.clipboardData?.getData('text').trim() ?? '';

    if (!/^https?:\/\//i.test(pasted)) {
        return;
    }

    event.preventDefault();

    const baseUrl = environmentVariablesStore.baseUrl;

    if (pasted.toLowerCase().startsWith(`${baseUrl.toLowerCase()}/`)) {
        document.execCommand('insertText', false, pasted.slice(baseUrl.length + 1));

        return;
    }

    showParameterWarning.value = false;
    showUrlWarning.value = true;
};

const executeCurrentRequestWhenEnterIsPressed = (event: KeyboardEvent) => {
    if (event.key !== 'Enter') {
        return;
    }

    event.preventDefault();
    executeCurrentRequest();
};
</script>

<template>
    <div class="h-toolbar flex min-w-0 flex-1 items-center">
        <!-- Endpoint Input with Rich Env Variables Highlighting -->
        <EnvironmentAwareInput
            v-model="endpoint"
            variant="none"
            size="sm"
            class="h-full flex-1"
            placeholder="<endpoint>"
            :disabled="!pendingRequestData"
            data-testid="endpoint-input"
            @click="autoSelectRouteVariableSegmentWhenApplicable"
            @keydown="executeCurrentRequestWhenEnterIsPressed"
            @paste="handlePaste"
        />

        <div class="gap-tight pe-tight flex items-center">
            <AppPopover v-model:open="warningOpen" :content="{ align: 'start' }">
                <template #anchor>
                    <AppTooltip text="Send (Enter)">
                        <AppButton
                            size="sm"
                            label="Send"
                            :trailing-icon="
                                pendingRequestData?.isProcessing
                                    ? 'i-hugeicons-loading-03'
                                    : 'i-hugeicons-arrow-turn-backward'
                            "
                            :ui="{
                                trailingIcon: pendingRequestData?.isProcessing
                                    ? 'animate-spin'
                                    : '',
                            }"
                            :disabled="
                                !pendingRequestData || pendingRequestData?.isProcessing
                            "
                            @click="executeCurrentRequest"
                        />
                    </AppTooltip>
                </template>

                <template #content>
                    <div class="w-80">
                        <RequestBuilderEndpointUrlWarningContent
                            v-if="showUrlWarning"
                            :base-url="environmentVariablesStore.baseUrl"
                        />
                        <RequestBuilderEndpointParameterWarningContent
                            v-else
                            :parameters="parameters"
                        />
                    </div>
                </template>
            </AppPopover>

            <slot name="options-menu" />
        </div>
    </div>
</template>
