<script setup lang="ts">
/**
 * @component ResponseViewerErrorState
 * @description Displays internal errors that occurred during the request (e.g., proxy failures).
 */
import AppPanelRipple from '@/components/base/AppPanelRipple.vue';
import { AppBadge } from '@/components/base/badge';
import CodeEditor from '@/components/domain/CodeEditor/CodeEditor.vue';
import { type ErrorPlainResponse } from '@/interfaces/http';
import { computed } from 'vue';

/*
 * Types & Interfaces.
 */

export interface ResponseViewerErrorStateProps {
    error: ErrorPlainResponse;
}

/*
 * Component Setup.
 */

const props = defineProps<ResponseViewerErrorStateProps>();

/*
 * Computed & Methods.
 */

const errorBody = computed({
    get: () => props.error.body,
    set: () => {
        // Read-only, no mutation allowed
    },
});
</script>

<template>
    <div
        class="via-default from-error/10 p-panel relative max-h-full flex-1 overflow-hidden bg-gradient-to-br from-10%"
    >
        <div class="z-sticky relative flex max-h-full flex-col p-2">
            <div>
                <AppBadge variant="outline" color="neutral" size="xs">
                    Internal Error
                </AppBadge>
                <h2 class="text-error text-lg font-medium">
                    {{ props.error.message }}
                </h2>
            </div>
            <p class="text-sm">
                Unable to relay the request through the internal proxy.
                <span v-if="props.error.message">
                    Check the details below, then send the request again.
                </span>
                <span v-else>
                    Check the console for details, then send the request again.
                </span>
            </p>

            <div
                v-if="props.error.body"
                class="bg-default mt-2 overflow-scroll rounded-md p-2 text-sm"
            >
                <CodeEditor v-model="errorBody" language="json" :readonly="true" />
            </div>
        </div>
        <AppPanelRipple />
    </div>
</template>
