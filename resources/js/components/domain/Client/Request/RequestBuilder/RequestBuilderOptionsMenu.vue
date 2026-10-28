<script setup lang="ts">
/**
 * @component RequestBuilderOptionsMenu
 * @description Dropdown menu for request options like Transaction Mode, cURL export, and shareable links.
 */
import { AppButton } from '@/components/base/button';
import type { AppDropdownMenuItem } from '@/components/base/dropdown-menu';
import { AppDropdownMenu } from '@/components/base/dropdown-menu';
import { AppIcon } from '@/components/base/icons';
import { AppPopover } from '@/components/base/popover';
import { AppSwitch } from '@/components/base/switch';
import { useAppToast } from '@/components/base/toast';
import {
    useConfigStore,
    useEnvironmentVariablesStore,
    useRequestsHistoryStore,
    useRequestStore,
} from '@/stores';
import { generateCurlCommand } from '@/utils/request';
import { buildShareableUrl, encodeShareablePayload } from '@/utils/shareableLinks';
import { computed, shallowRef } from 'vue';
import CurlExportDialog from './CurlExportDialog.vue';
import ShareableLinkDialog from './ShareableLinkDialog.vue';

/*
 * Stores.
 */

const toast = useAppToast();
const requestStore = useRequestStore();
const configStore = useConfigStore();
const historyStore = useRequestsHistoryStore();
const environmentVariablesStore = useEnvironmentVariablesStore();

/*
 * State.
 */

const showCurlDialog = shallowRef(false);
const curlCommand = shallowRef('');
const hasSpecialAuth = shallowRef(false);
const showShareableLinkDialog = shallowRef(false);
const shareableLink = shallowRef('');

/*
 * Computed & Methods.
 */

const pendingRequestData = computed(() => requestStore.pendingRequestData);

const transactionMode = computed({
    get: () => pendingRequestData.value?.transactionMode ?? false,
    set: (value: boolean) => requestStore.updateTransactionMode(value),
});

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
        environmentVariablesStore.resolve,
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
            environmentVariablesStore.resolve,
            response,
            lastLog ?? undefined,
            applicationKey,
        );

        shareableLink.value = buildShareableUrl(configStore.appBasePath, encodedPayload);

        showShareableLinkDialog.value = true;
    } catch {
        toast.error('Failed to generate shareable link', {
            description: 'An unexpected error occurred.',
        });
    }
};

const items = computed<AppDropdownMenuItem[][]>(() => [
    [
        { label: 'Options', type: 'label' },
        {
            slot: 'transaction',
            label: 'Transaction mode',
            onSelect: (event: Event) => event.preventDefault(),
        },
    ],
    [
        { label: 'Export', type: 'label' },
        {
            label: 'Export to cURL',
            icon: 'i-hugeicons-code',
            testId: 'export-curl-option',
            onSelect: populateCurlCommandExporterDialog,
        },
        {
            label: 'Copy shareable link',
            icon: 'i-hugeicons-link',
            testId: 'copy-shareable-link-option',
            onSelect: openShareableLinkDialog,
        },
    ],
]);
</script>

<template>
    <AppDropdownMenu :items="items" :content="{ align: 'end' }">
        <AppButton
            variant="outline"
            color="neutral"
            size="sm"
            :disabled="!pendingRequestData"
            data-testid="request-options-button"
            aria-label="Request options"
            icon="i-hugeicons-command"
            square
        />

        <template #item-label="{ item }">
            <span :data-testid="item.testId">{{ item.label }}</span>
        </template>

        <template #transaction="{ item }">
            <div
                class="gap-loose flex w-full items-center justify-between"
                :data-testid="'transaction-mode-option'"
            >
                <div class="gap-tight flex items-center">
                    <span>{{ item.label }}</span>
                    <AppPopover :content="{ side: 'right', sideOffset: 5 }">
                        <button
                            type="button"
                            aria-label="About transaction mode"
                            class="-m-1 rounded-sm p-1"
                            @click.stop.prevent
                        >
                            <AppIcon
                                name="i-hugeicons-help-circle"
                                class="text-muted hover:text-highlighted size-icon-sm cursor-help"
                            />
                        </button>

                        <template #content>
                            <div class="gap-base flex flex-col">
                                <h4 class="text-sm leading-none font-medium">
                                    Transaction mode
                                </h4>
                                <p class="text-muted text-xs">
                                    Executes the request within a database transaction
                                    that is automatically rolled back after completion.
                                    This allows you to test operations without affecting
                                    your persistent data.
                                </p>
                            </div>
                        </template>
                    </AppPopover>
                </div>
                <AppSwitch
                    v-model="transactionMode"
                    size="sm"
                    aria-label="Transaction mode"
                    @click.stop
                />
            </div>
        </template>
    </AppDropdownMenu>

    <CurlExportDialog
        v-model:open="showCurlDialog"
        :command="curlCommand"
        :has-special-auth="hasSpecialAuth"
    />

    <ShareableLinkDialog v-model:open="showShareableLinkDialog" :link="shareableLink" />
</template>
