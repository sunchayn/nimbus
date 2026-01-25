<script setup lang="ts">
/**
 * @component RequestBuilderOptionsMenu
 * @description Dropdown menu for request options like Transaction Mode, cURL export, and shareable links.
 */
import { AppButton } from '@/components/base/button';
import {
    AppDropdownMenu,
    AppDropdownMenuContent,
    AppDropdownMenuGroup,
    AppDropdownMenuItem,
    AppDropdownMenuLabel,
    AppDropdownMenuSeparator,
    AppDropdownMenuTrigger,
} from '@/components/base/dropdown-menu';
import {
    AppPopover,
    AppPopoverContent,
    AppPopoverTrigger,
} from '@/components/base/popover';
import { AppSwitch } from '@/components/base/switch';
import { useConfigStore, useRequestsHistoryStore, useRequestStore } from '@/stores';
import { generateCurlCommand } from '@/utils/request';
import { buildShareableUrl, encodeShareablePayload } from '@/utils/shareableLinks';
import { CircleHelp, CodeXml, Link2, SparklesIcon } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import CurlExportDialog from './CurlExportDialog.vue';
import ShareableLinkDialog from './ShareableLinkDialog.vue';

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
const showShareableLinkDialog = ref(false);
const shareableLink = ref('');

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
            <AppDropdownMenuLabel>Options</AppDropdownMenuLabel>
            <AppDropdownMenuGroup>
                <AppDropdownMenuItem
                    class="cursor-pointer text-xs"
                    data-testid="transaction-mode-option"
                    @select.prevent
                >
                    <div class="flex w-full items-center justify-between">
                        <div class="flex items-center gap-1.5 font-normal">
                            <span>Transaction Mode</span>
                            <AppPopover>
                                <AppPopoverTrigger
                                    ref="transaction-mode-option"
                                    as-child
                                    @click.stop.prevent
                                >
                                    <CircleHelp
                                        class="h-3.5 w-3.5 cursor-help text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200"
                                    />
                                </AppPopoverTrigger>
                                <AppPopoverContent
                                    class="w-80"
                                    side="right"
                                    :side-offset="5"
                                >
                                    <div class="space-y-2">
                                        <h4 class="text-sm leading-none font-medium">
                                            Transaction Mode
                                        </h4>
                                        <p
                                            class="text-muted-foreground text-xs leading-relaxed"
                                        >
                                            Executes the request within a database
                                            transaction that is automatically rolled back
                                            after completion. This allows you to test
                                            operations without affecting your persistent
                                            data.
                                        </p>
                                    </div>
                                </AppPopoverContent>
                            </AppPopover>
                        </div>
                        <AppSwitch
                            v-model="transactionMode"
                            :variant="{ type: 'compact', default: 'default' }"
                            class="ml-2"
                            @click.stop
                        />
                    </div>
                </AppDropdownMenuItem>
            </AppDropdownMenuGroup>
            <AppDropdownMenuSeparator />
            <AppDropdownMenuLabel>Export</AppDropdownMenuLabel>
            <AppDropdownMenuGroup>
                <AppDropdownMenuItem
                    class="cursor-pointer text-xs"
                    data-testid="export-curl-option"
                    @select="populateCurlCommandExporterDialog"
                >
                    <CodeXml class="mr-2 size-4" />
                    <span>Export to cURL</span>
                </AppDropdownMenuItem>
                <AppDropdownMenuItem
                    class="cursor-pointer text-xs"
                    data-testid="copy-shareable-link-option"
                    @select="openShareableLinkDialog"
                >
                    <Link2 class="mr-2 size-4" />
                    <span>Copy Shareable Link</span>
                </AppDropdownMenuItem>
            </AppDropdownMenuGroup>
        </AppDropdownMenuContent>
    </AppDropdownMenu>

    <CurlExportDialog
        v-model:open="showCurlDialog"
        :command="curlCommand"
        :has-special-auth="hasSpecialAuth"
    />

    <ShareableLinkDialog v-model:open="showShareableLinkDialog" :link="shareableLink" />
</template>
