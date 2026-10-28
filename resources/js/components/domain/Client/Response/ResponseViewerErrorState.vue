<script setup lang="ts">
/**
 * @component ResponseViewerErrorState
 * @description Displays internal errors that occurred during the request (e.g., proxy failures).
 */
import AppPanelRipple from '@/components/base/AppPanelRipple.vue';
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
        class="bg-muted gap-base relative flex h-full min-h-0 flex-1 flex-col overflow-hidden p-2"
    >
        <div class="flex shrink-0 flex-col gap-px">
            <h2 class="text-base font-medium">
                {{ props.error.message }}
            </h2>
            <p class="text-muted text-sm">
                Unable to relay the request.
                <span v-if="props.error.body">
                    Check the details below, then send the request again.
                </span>
                <span v-else>
                    Check the console for details, then send the request again.
                </span>
            </p>
        </div>

        <div
            v-if="props.error.body"
            class="border-muted bg-default z-sticky relative min-h-0 flex-1 overflow-hidden rounded-md border text-sm"
        >
            <CodeEditor
                v-model="errorBody"
                language="json"
                :readonly="true"
                :auto-height="false"
            />
        </div>
        <AppPanelRipple />
    </div>
</template>
