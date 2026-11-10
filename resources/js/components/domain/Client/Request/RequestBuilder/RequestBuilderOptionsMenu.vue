<script setup lang="ts">
/**
 * @component RequestBuilderOptionsMenu
 * @description Dropdown menu for request options like Transaction Mode, cURL export, code export, and shareable links.
 */
import { AppButton } from '@/components/base/button';
import type { AppDropdownMenuItem } from '@/components/base/dropdown-menu';
import { AppDropdownMenu } from '@/components/base/dropdown-menu';
import { AppIcon } from '@/components/base/icons';
import { AppInput } from '@/components/base/input';
import { AppPopover } from '@/components/base/popover';
import { AppSwitch } from '@/components/base/switch';
import { useShareableLink } from '@/composables/request/useShareableLink';
import { useEnvironmentVariablesStore, useRequestStore, useTabsStore } from '@/stores';
import { isProductionEnvironment } from '@/utils/environment';
import { generateCurlCommand } from '@/utils/request';
import { CODE_LANGUAGES, generateCode } from '@/utils/request/codeGenerators';
import { isSignedRoute } from '@/utils/routes';
import { computed, shallowRef } from 'vue';
import CodeExportDialog from './CodeExportDialog.vue';
import CurlExportDialog from './CurlExportDialog.vue';
import ShareableLinkDialog from './ShareableLinkDialog.vue';

/*
 * Stores.
 */

const { showShareableLinkDialog, shareableLink, openShareableLinkDialog } =
    useShareableLink();
const requestStore = useRequestStore();
const environmentVariablesStore = useEnvironmentVariablesStore();
const tabsStore = useTabsStore();

/*
 * State.
 */

const showCurlDialog = shallowRef(false);
const curlCommand = shallowRef('');
const hasSpecialAuth = shallowRef(false);
const showCodeDialog = shallowRef(false);
const codeSamples = shallowRef<Record<string, string>>({});

/*
 * Computed & Methods.
 */

const pendingRequestData = computed(() => requestStore.pendingRequestData);

const transactionMode = computed({
    get: () => pendingRequestData.value?.transactionMode ?? false,
    set: (value: boolean) => requestStore.updateTransactionMode(value),
});

const DEFAULT_TIMEOUT = 30;

const defaultVerifySsl = computed(() =>
    isProductionEnvironment(environmentVariablesStore.activeEnvironment),
);

const followRedirects = computed({
    get: () => pendingRequestData.value?.options?.followRedirects ?? true,
    set: (value: boolean) => requestStore.updateOptions({ followRedirects: value }),
});

const verifySsl = computed({
    get: () => pendingRequestData.value?.options?.verifySsl ?? defaultVerifySsl.value,
    set: (value: boolean) => requestStore.updateOptions({ verifySsl: value }),
});

const timeout = computed({
    get: () => pendingRequestData.value?.options?.timeout ?? DEFAULT_TIMEOUT,
    set: (value: string | number) => {
        const seconds = Math.trunc(Number(value));

        if (seconds >= 1 && seconds <= 300) {
            requestStore.updateOptions({ timeout: seconds });
        }
    },
});

const canSignUrl = computed(() =>
    isSignedRoute(pendingRequestData.value?.routeDefinition),
);

const signUrl = computed({
    get: () => pendingRequestData.value?.signUrl ?? false,
    set: (value: boolean) => requestStore.updateSignUrl(value),
});

/**
 * Generates the cURL command for the current request.
 */
const populateCurlCommandExporterDialog = () => {
    if (!requestStore.pendingRequestData) {
        return;
    }

    const result = generateCurlCommand(
        requestStore.pendingRequestData,
        environmentVariablesStore.baseUrl,
        environmentVariablesStore.resolve,
    );

    curlCommand.value = result.command;
    hasSpecialAuth.value = result.hasSpecialAuth;
    showCurlDialog.value = true;
};

/**
 * Generates the code samples for the current request.
 */
const populateCodeExporterDialog = () => {
    const request = requestStore.pendingRequestData;

    if (!request) {
        return;
    }

    const expectedStatus = tabsStore.activeResponse?.response?.statusCode || 200;

    codeSamples.value = Object.fromEntries(
        CODE_LANGUAGES.map(({ value }) => [
            value,
            generateCode(
                value,
                request,
                environmentVariablesStore.baseUrl,
                environmentVariablesStore.resolve,
                expectedStatus,
            ),
        ]),
    );
    showCodeDialog.value = true;
};

const items = computed<AppDropdownMenuItem[][]>(() => [
    [
        { label: 'Options', type: 'label' },
        {
            slot: 'transaction',
            label: 'Transaction mode',
            onSelect: (event: Event) => event.preventDefault(),
        },
        ...(canSignUrl.value
            ? [
                  {
                      slot: 'sign',
                      label: 'Sign URL',
                      onSelect: (event: Event) => event.preventDefault(),
                  },
              ]
            : []),
        {
            slot: 'redirects',
            label: 'Follow redirects',
            onSelect: (event: Event) => event.preventDefault(),
        },
        {
            slot: 'ssl',
            label: 'Verify SSL',
            onSelect: (event: Event) => event.preventDefault(),
        },
        {
            slot: 'timeout',
            label: 'Timeout (seconds)',
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
            label: 'Export as code',
            icon: 'i-hugeicons-source-code',
            testId: 'export-code-option',
            onSelect: populateCodeExporterDialog,
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
                            class="-m-tight p-tight rounded-sm"
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

        <template #sign="{ item }">
            <div
                class="gap-loose flex w-full items-center justify-between"
                data-testid="sign-url-option"
            >
                <div class="gap-tight flex items-center">
                    <span>{{ item.label }}</span>
                    <AppPopover :content="{ side: 'right', sideOffset: 5 }">
                        <button
                            type="button"
                            aria-label="About signing the URL"
                            class="-m-tight p-tight rounded-sm"
                            @click.stop.prevent
                        >
                            <AppIcon
                                name="i-hugeicons-help-circle"
                                class="text-muted hover:text-highlighted size-icon-sm cursor-help"
                            />
                        </button>

                        <template #content>
                            <div class="gap-base flex flex-col">
                                <h4 class="text-sm leading-none font-medium">Sign URL</h4>
                                <p class="text-muted text-xs">
                                    Signs the URL of this route right before the request
                                    is sent, so it passes the signed middleware.
                                </p>
                            </div>
                        </template>
                    </AppPopover>
                </div>
                <AppSwitch
                    v-model="signUrl"
                    size="sm"
                    aria-label="Sign URL"
                    @click.stop
                />
            </div>
        </template>

        <template #redirects="{ item }">
            <div
                class="gap-loose flex w-full items-center justify-between"
                data-testid="follow-redirects-option"
            >
                <span>{{ item.label }}</span>
                <AppSwitch
                    v-model="followRedirects"
                    size="sm"
                    aria-label="Follow redirects"
                    @click.stop
                />
            </div>
        </template>

        <template #ssl="{ item }">
            <div
                class="gap-loose flex w-full items-center justify-between"
                data-testid="verify-ssl-option"
            >
                <span>{{ item.label }}</span>
                <AppSwitch
                    v-model="verifySsl"
                    size="sm"
                    aria-label="Verify SSL"
                    @click.stop
                />
            </div>
        </template>

        <template #timeout="{ item }">
            <div
                class="gap-loose flex w-full items-center justify-between"
                data-testid="timeout-option"
            >
                <span>{{ item.label }}</span>
                <AppInput
                    v-model="timeout"
                    type="number"
                    size="xs"
                    class="w-20"
                    :min="1"
                    :max="300"
                    aria-label="Timeout in seconds"
                    @click.stop
                    @keydown.stop
                />
            </div>
        </template>
    </AppDropdownMenu>

    <CurlExportDialog
        v-model:open="showCurlDialog"
        :command="curlCommand"
        :has-special-auth="hasSpecialAuth"
    />

    <CodeExportDialog
        v-model:open="showCodeDialog"
        :samples="codeSamples"
        :languages="CODE_LANGUAGES"
    />

    <ShareableLinkDialog v-model:open="showShareableLinkDialog" :link="shareableLink" />
</template>
