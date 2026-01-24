<script setup lang="ts">
/**
 * @component RequestBuilderEndpoint
 * @description The endpoint input and method selector for the request builder.
 */
import { AppButton } from '@/components/base/button';
import { AppInput } from '@/components/base/input';
import {
    AppSelect,
    AppSelectContent,
    AppSelectGroup,
    AppSelectItem,
    AppSelectLabel,
    AppSelectTrigger,
    AppSelectValue,
} from '@/components/base/select';
import AppTooltipWrapper from '@/components/base/tooltip/AppTooltipWrapper.vue';
import { useRouteSegmentSelection } from '@/composables/request/useRouteSegmentSelection';
import { type RouteDefinition } from '@/interfaces/routes/routes';
import { useConfigStore, useRequestStore } from '@/stores';
import { generateCurlCommand } from '@/utils/request';
import { cn } from '@/utils/ui';
import { CodeXml, CornerDownLeftIcon } from 'lucide-vue-next';
import { computed, type HTMLAttributes, ref } from 'vue';
import CurlExportDialog from './CurlExportDialog.vue';

/*
 * Types & Interfaces.
 */

export interface AppRequestBuilderEndpointProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppRequestBuilderEndpointProps>();

/*
 * Stores.
 */

const requestStore = useRequestStore();
const configStore = useConfigStore();

/*
 * State.
 */

const showCurlDialog = ref(false);
const curlCommand = ref('');
const hasSpecialAuth = ref(false);
const availableMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

/*
 * Computed & Methods.
 */

const pendingRequestData = computed(() => requestStore.pendingRequestData);

const endpoint = computed({
    get: () => pendingRequestData.value?.endpoint ?? '',
    set: (value: string) => requestStore.updateRequestEndpoint(value),
});

const method = computed({
    get: () => pendingRequestData.value?.method?.toUpperCase() ?? 'GET',
    set: (value: string) => requestStore.updateRequestMethod(value.toUpperCase()),
});

const currentRouteSupportedMethods = computed(() => {
    const supportedRoutes = requestStore.pendingRequestData?.supportedRoutes;
    const supportedMethods =
        supportedRoutes?.map((route: RouteDefinition) => route.method) ?? [];

    return availableMethods.filter((method: string) => supportedMethods.includes(method));
});

const currentRouteUnsupportedMethods = computed(() => {
    return availableMethods.filter(
        (method: string) => !currentRouteSupportedMethods.value.includes(method),
    );
});

/*
 * Route segment selection.
 */

const { handleClick: autoSelectRouteVariableSegmentWhenApplicable } =
    useRouteSegmentSelection({ endpoint });

/*
 * Actions.
 */

const executeCurrentRequest = async function () {
    if (!requestStore.pendingRequestData) {
        return;
    }

    await requestStore.executeCurrentRequest();
};

/**
 * Executes request of Enter key is pressed.
 */
const executeCurrentRequestWhenEnterIsPressed = (event: KeyboardEvent) => {
    if (event.key !== 'Enter') {
        return;
    }

    event.preventDefault();
    executeCurrentRequest();
};

/**
 * Generates and displays cURL command for the current request.
 */
const populateCurlCommandExporterDialog = () => {
    if (!requestStore.pendingRequestData) {
        return;
    }

    const result = generateCurlCommand(
        requestStore.pendingRequestData,
        configStore.apiUrl,
    );

    curlCommand.value = result.command;
    hasSpecialAuth.value = result.hasSpecialAuth;
    showCurlDialog.value = true;
};
</script>

<template>
    <div :class="cn('flex', props.class)">
        <AppSelect v-model="method">
            <AppSelectTrigger
                class="h-full w-[95px] rounded-none border-0 border-r pr-1.5 pl-5 text-xs shadow-none focus:ring-0 focus-visible:ring-0"
            >
                <AppSelectValue :placeholder="method ? '' : 'Select a Method'">
                    {{ method || 'Select a Method' }}
                </AppSelectValue>
            </AppSelectTrigger>
            <AppSelectContent>
                <AppSelectGroup>
                    <AppSelectLabel>Supported</AppSelectLabel>
                    <AppSelectItem
                        v-for="supportedMethod in currentRouteSupportedMethods"
                        :key="supportedMethod"
                        :value="supportedMethod"
                    >
                        {{ supportedMethod }}
                    </AppSelectItem>
                </AppSelectGroup>
                <AppSelectGroup v-if="currentRouteUnsupportedMethods.length !== 0">
                    <AppSelectLabel>Other</AppSelectLabel>
                    <AppSelectItem
                        v-for="unsupportedMethod in currentRouteUnsupportedMethods"
                        :key="unsupportedMethod"
                        :value="unsupportedMethod"
                    >
                        {{ unsupportedMethod }}
                    </AppSelectItem>
                </AppSelectGroup>
            </AppSelectContent>
        </AppSelect>
        <div class="flex flex-1 items-center">
            <AppInput
                ref="inputRef"
                v-model="endpoint"
                class="h-full flex-1 rounded-none border-0 text-xs shadow-none focus:ring-0 focus-visible:ring-0"
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
                <AppTooltipWrapper value="Export cURL">
                    <AppButton
                        variant="outline"
                        size="xs"
                        :disabled="!pendingRequestData"
                        @click="populateCurlCommandExporterDialog"
                    >
                        <CodeXml />
                    </AppButton>
                </AppTooltipWrapper>
            </div>
        </div>
    </div>

    <CurlExportDialog
        v-model:open="showCurlDialog"
        :command="curlCommand"
        :has-special-auth="hasSpecialAuth"
    />
</template>
