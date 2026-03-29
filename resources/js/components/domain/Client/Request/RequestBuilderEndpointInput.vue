<script setup lang="ts">
/**
 * @component RequestBuilderEndpointInput
 * @description The endpoint input field and send button for the request builder.
 */
import { AppButton } from '@/components/base/button';
import {
    AppPopover,
    AppPopoverAnchor,
    AppPopoverContent,
} from '@/components/base/popover';
import EnvironmentAwareInput from '@/components/common/EnvironmentAwareInput.vue';

import { useRouteParameterParsing } from '@/composables/request/useRouteParameterParsing';
import { useRouteSegmentSelection } from '@/composables/request/useRouteSegmentSelection';
import { useRequestStore } from '@/stores';
import { CornerDownLeftIcon } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import RequestBuilderEndpointParameterWarningContent from './RequestBuilderEndpointParameterWarningContent.vue';

/*
 * Stores.
 */

const requestStore = useRequestStore();

/*
 * State.
 */

const showParameterWarning = ref(false);

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
    <div class="flex min-w-0 flex-1 items-center">
        <!-- Endpoint Input with Rich Env Variables Highlighting -->
        <EnvironmentAwareInput
            v-model="endpoint"
            variant="toolbar"
            class="h-full flex-1 text-xs"
            placeholder="<endpoint>"
            data-testid="endpoint-input"
            @click="autoSelectRouteVariableSegmentWhenApplicable"
            @keydown="executeCurrentRequestWhenEnterIsPressed"
        />

        <div class="flex gap-2 pr-2">
            <AppPopover v-model:open="showParameterWarning">
                <AppPopoverAnchor as-child>
                    <AppButton
                        size="xs"
                        :disabled="
                            !pendingRequestData || pendingRequestData?.isProcessing
                        "
                        class="gap-0"
                        @click="executeCurrentRequest"
                    >
                        Send (
                        <CornerDownLeftIcon class="size-3 px-0" />
                        )
                    </AppButton>
                </AppPopoverAnchor>

                <AppPopoverContent align="start" class="w-80 p-1">
                    <RequestBuilderEndpointParameterWarningContent
                        :parameters="parameters"
                    />
                </AppPopoverContent>
            </AppPopover>

            <slot name="options-menu" />
        </div>
    </div>
</template>
