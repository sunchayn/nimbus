<script setup lang="ts">
/**
 * @component RequestBuilderEndpoint
 * @description The endpoint input and method selector for the request builder.
 */
import { AppButton } from '@/components/base/button';
import {
    AppDropdownMenu,
    AppDropdownMenuContent,
    AppDropdownMenuGroup,
    AppDropdownMenuItem,
    AppDropdownMenuLabel,
    AppDropdownMenuTrigger,
} from '@/components/base/dropdown-menu';
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
import { useRouteSegmentSelection } from '@/composables/request/useRouteSegmentSelection';
import { type RouteDefinition } from '@/interfaces/routes/routes';
import { useConfigStore, useRequestsHistoryStore, useRequestStore } from '@/stores';
import { generateCurlCommand } from '@/utils/request';
import { buildShareableUrl, encodeShareablePayload } from '@/utils/shareableLinks';
import { cn } from '@/utils/ui';
import { CodeXml, CornerDownLeftIcon, Link2, SparklesIcon } from 'lucide-vue-next';
import { computed, type HTMLAttributes, ref } from 'vue';
import { toast } from 'vue-sonner';
import CurlExportDialog from './CurlExportDialog.vue';
import ShareableLinkDialog from './ShareableLinkDialog.vue';

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
const historyStore = useRequestsHistoryStore();

/*
 * State.
 */

const showCurlDialog = ref(false);
const curlCommand = ref('');
const hasSpecialAuth = ref(false);
const availableMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
const showShareableLinkDialog = ref(false);
const shareableLink = ref('');

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

/**
 * Generates and shows shareable link dialog for the current request state.
 */
const openShareableLinkDialog = () => {
    if (!requestStore.pendingRequestData) {
        return;
    }

    try {
        const lastLog = historyStore.lastLog;
        const response = lastLog?.response;
        const applicationKey = configStore.activeApplication ?? undefined;

        const encodedPayload = encodeShareablePayload(
            requestStore.pendingRequestData,
            response,
            lastLog ?? undefined,
            applicationKey,
        );

        shareableLink.value = buildShareableUrl(configStore.appBasePath, encodedPayload);

        showShareableLinkDialog.value = true;
    } catch (error) {
        console.error('Failed to generate shareable link:', error);

        toast.error('Failed to generate shareable link', {
            description: 'An unexpected error occurred.',
        });
    }
};
</script>

<template>
    <div :class="cn('flex', props.class)">
        <AppSelect v-model="method">
            <AppSelectTrigger
                variant="toolbar"
                class="h-full w-[95px] border-r pr-1.5 pl-5 text-xs"
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
                <AppDropdownMenu>
                    <AppDropdownMenuTrigger as-child>
                        <AppButton
                            variant="outline"
                            size="xs"
                            :disabled="!pendingRequestData"
                            data-testid="request-options-button"
                            title="Request Options"
                        >
                            <SparklesIcon class="size-4" />
                        </AppButton>
                    </AppDropdownMenuTrigger>
                    <AppDropdownMenuContent align="end" class="w-48">
                        <AppDropdownMenuLabel>Export</AppDropdownMenuLabel>
                        <AppDropdownMenuGroup>
                            <AppDropdownMenuItem
                                class="cursor-pointer text-xs"
                                data-testid="export-curl-option"
                                @select="populateCurlCommandExporterDialog"
                            >
                                <CodeXml class="mr-2 size-2" />
                                <span>Export to cURL</span>
                            </AppDropdownMenuItem>
                            <AppDropdownMenuItem
                                class="cursor-pointer text-xs"
                                data-testid="copy-shareable-link-option"
                                @select="openShareableLinkDialog"
                            >
                                <Link2 class="mr-2 size-2" />
                                <span>Copy Shareable Link</span>
                            </AppDropdownMenuItem>
                        </AppDropdownMenuGroup>
                    </AppDropdownMenuContent>
                </AppDropdownMenu>
            </div>
        </div>
    </div>

    <CurlExportDialog
        v-model:open="showCurlDialog"
        :command="curlCommand"
        :has-special-auth="hasSpecialAuth"
    />

    <ShareableLinkDialog v-model:open="showShareableLinkDialog" :link="shareableLink" />
</template>
