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
import { useRequestStore } from '@/stores';
import { computed, shallowRef } from 'vue';
import RequestBuilderEndpointParameterWarningContent from './RequestBuilderEndpointParameterWarningContent.vue';

/*
 * Stores.
 */

const requestStore = useRequestStore();

/*
 * State.
 */

const showParameterWarning = shallowRef(false);

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

    if (hasParameters.value) {
        showParameterWarning.value = true;

        return;
    }

    showParameterWarning.value = false;

    await requestStore.executeCurrentRequest();
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
        />

        <div class="gap-tight pe-tight flex items-center">
            <AppPopover v-model:open="showParameterWarning" :content="{ align: 'start' }">
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
                    <div class="p-tight w-80">
                        <RequestBuilderEndpointParameterWarningContent
                            :parameters="parameters"
                        />
                    </div>
                </template>
            </AppPopover>

            <slot name="options-menu" />
        </div>
    </div>
</template>
