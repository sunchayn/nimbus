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

export interface AppResponseViewerErrorStateProps {
    error: ErrorPlainResponse;
}

/*
 * Component Setup.
 */

const props = defineProps<AppResponseViewerErrorStateProps>();

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
        class="via-background from-destructive/10 dark:from-destructive/15 relative max-h-full flex-1 overflow-hidden bg-gradient-to-br from-10% p-4"
    >
        <div class="relative z-10 flex max-h-full flex-col p-2">
            <div>
                <AppBadge variant="outline" class="p-0 px-1 text-xs">
                    Internal Error
                </AppBadge>
                <h2 class="text-destructive text-lg font-medium">
                    {{ props.error.message }}
                </h2>
            </div>
            <p class="text-sm">
                It wasn't possible to relay the request via the internal proxy.
                <span v-if="props.error.message">
                    The below information might help, otherwise, check the console for
                    more details.
                </span>
                <span v-else>Check the console for more details.</span>
            </p>

            <div
                v-if="props.error.body"
                class="bg-background mt-2 ml-0.5 overflow-scroll rounded-sm p-2 text-sm shadow-[0_0_0_5px_rgba(0,0,0,0.2)]"
            >
                <CodeEditor v-model="errorBody" language="json" :readonly="true" />
            </div>
        </div>
        <AppPanelRipple />
    </div>
</template>
