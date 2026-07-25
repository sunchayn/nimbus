<script setup lang="ts">
/**
 * @component ResponseDownloadDropdown
 * @description Provides a dropdown menu with options to download the raw response or its schema shape.
 */
import { AppButton } from '@/components/base/button';
import {
    AppDropdownMenu,
    AppDropdownMenuContent,
    AppDropdownMenuItem,
    AppDropdownMenuLabel,
    AppDropdownMenuSeparator,
    AppDropdownMenuTrigger,
} from '@/components/base/dropdown-menu';
import useResponseDownload from '@/composables/response/useResponseDownload';
import type { RequestLog } from '@/interfaces/history/logs';
import { DownloadIcon, Loader2Icon } from 'lucide-vue-next';
import { computed } from 'vue';

/*
 * Types & Interfaces.
 */

export interface ResponseDownloadDropdownProps {
    response?: RequestLog | null;
}

/*
 * Component Setup.
 */

const props = defineProps<ResponseDownloadDropdownProps>();

const responseRef = computed(() => props.response);

const { isResolvingShape, isJsonResponse, downloadRawResponse, downloadResponseShape } =
    useResponseDownload(responseRef);
</script>

<template>
    <AppDropdownMenu @update:open="open => open">
        <AppDropdownMenuTrigger as-child>
            <AppButton
                variant="outline"
                size="xs"
                data-testid="download-dropdown-trigger"
            >
                <DownloadIcon class="size-3" />
                <span class="text-xs">Download</span>
            </AppButton>
        </AppDropdownMenuTrigger>
        <AppDropdownMenuContent align="end" class="w-44">
            <AppDropdownMenuLabel class="text-xs">Download Options</AppDropdownMenuLabel>
            <AppDropdownMenuSeparator />
            <AppDropdownMenuItem
                class="cursor-pointer gap-1.5 text-xs"
                data-testid="download-raw-option"
                @click="downloadRawResponse"
            >
                <span>Raw Response</span>
            </AppDropdownMenuItem>
            <AppDropdownMenuItem
                class="cursor-pointer justify-between gap-1.5 text-xs"
                data-testid="download-shape-option"
                :disabled="isResolvingShape || !isJsonResponse"
                @click="downloadResponseShape"
            >
                <span>JSON Shape</span>
                <Loader2Icon
                    v-if="isResolvingShape"
                    class="size-3 animate-spin text-zinc-400"
                />
            </AppDropdownMenuItem>
        </AppDropdownMenuContent>
    </AppDropdownMenu>
</template>
