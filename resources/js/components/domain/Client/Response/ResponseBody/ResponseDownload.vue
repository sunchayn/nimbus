<script setup lang="ts">
/**
 * @component ResponseDownload
 * @description Explains that the response was a file download forwarded to the browser, with the file metadata.
 */
import ResponseNotice from '@/components/domain/Client/Response/ResponseBody/ResponseNotice.vue';
import type { ResponseDownload } from '@/interfaces/http';
import prettyBytes from 'pretty-bytes';
import { computed } from 'vue';

/*
 * Types & Interfaces.
 */

export interface ResponseDownloadProps {
    download: ResponseDownload;
}

/*
 * Component Setup.
 */

const props = defineProps<ResponseDownloadProps>();

/*
 * Computed & Methods.
 */

const size = computed(() => prettyBytes(props.download.sizeInBytes, { space: false }));

const details = computed(() => [
    { label: 'File', value: props.download.filename, testId: 'download-filename' },
    { label: 'Type', value: props.download.mimeType, testId: 'download-mime-type' },
    { label: 'Size', value: size.value, testId: 'download-size' },
]);
</script>

<template>
    <div class="p-panel bg-muted h-full min-h-0 flex-1 overflow-y-auto">
        <ResponseNotice label="Download forwarded" indicator-class="text-success">
            <div class="gap-base flex flex-col">
                <p>
                    This response was a file download that was forwarded to the browser.
                </p>
                <dl class="gap-tight grid grid-cols-[auto_1fr] text-xs">
                    <template v-for="detail in details" :key="detail.label">
                        <dt class="text-muted">{{ detail.label }}</dt>
                        <dd class="break-all" :data-testid="detail.testId">
                            {{ detail.value }}
                        </dd>
                    </template>
                </dl>
            </div>
        </ResponseNotice>
    </div>
</template>
