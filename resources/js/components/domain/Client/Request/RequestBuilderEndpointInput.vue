<script setup lang="ts">
/**
 * @component RequestBuilderEndpointInput
 * @description The endpoint input field and send button for the request builder.
 */
import { AppButton } from '@/components/base/button';
import { AppInput } from '@/components/base/input';
import { useRouteSegmentSelection } from '@/composables/request/useRouteSegmentSelection';
import { useRequestStore } from '@/stores';
import { CornerDownLeftIcon } from 'lucide-vue-next';
import { computed } from 'vue';

/*
 * Stores.
 */

const requestStore = useRequestStore();

/*
 * Computed & Methods.
 */

const pendingRequestData = computed(() => requestStore.pendingRequestData);

const endpoint = computed({
    get: () => pendingRequestData.value?.endpoint ?? '',
    set: (value: string) => requestStore.updateRequestEndpoint(value),
});

const { handleClick: autoSelectRouteVariableSegmentWhenApplicable } =
    useRouteSegmentSelection({ endpoint });

const executeCurrentRequest = async function () {
    if (!requestStore.pendingRequestData) {
        return;
    }

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
    <div class="flex flex-1 items-center">
        <AppInput
            v-model="endpoint"
            variant="toolbar"
            class="h-full flex-1 text-xs"
            placeholder="<endpoint>"
            data-testid="endpoint-input"
            @click="autoSelectRouteVariableSegmentWhenApplicable"
            @keydown="executeCurrentRequestWhenEnterIsPressed"
        />
        <div class="flex gap-2 pr-2">
            <AppButton
                size="xs"
                :disabled="!pendingRequestData || pendingRequestData?.isProcessing"
                class="gap-0"
                @click="executeCurrentRequest"
            >
                Send (
                <CornerDownLeftIcon class="size-3 px-0" />
                )
            </AppButton>
            <slot name="options-menu" />
        </div>
    </div>
</template>
