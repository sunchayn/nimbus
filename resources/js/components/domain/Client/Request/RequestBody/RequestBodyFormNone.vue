<script setup lang="ts">
import { AppIcon } from '@/components/base/icons';
/**
 * @component RequestBodyFormNone
 * @description Panel indicating an empty or 'none' request body.
 */
import {
    AppPanel,
    AppPanelDescription,
    AppPanelHeader,
    AppPanelTitle,
} from '@/components/base/panel';
import { onMounted } from 'vue';

/*
 * Types & Interfaces.
 */

export interface RequestBodyFormNoneProps {}

/*
 * Component Setup.
 */

defineProps<RequestBodyFormNoneProps>();
const model = defineModel<null>();

/*
 * Lifecycle.
 */

onMounted(() => (model.value = null));
</script>

<template>
    <AppPanel class="border-b shadow-none">
        <AppPanelHeader class="items-start">
            <AppIcon name="i-hugeicons-unavailable" class="size-icon-lg shrink-0" />
            <div class="flex flex-col">
                <AppPanelTitle>No Body</AppPanelTitle>
                <AppPanelDescription>
                    You're performing the request without a Payload.
                </AppPanelDescription>
            </div>
        </AppPanelHeader>
    </AppPanel>
</template>
