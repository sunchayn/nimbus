<script setup lang="ts">
import { AppIcon } from '@/components/base/icons';
/**
 * @component RequestAuthorizationNone
 * @description Info panel indicating no authentication is being used.
 */
import {
    AppPanel,
    AppPanelDescription,
    AppPanelHeader,
    AppPanelTitle,
} from '@/components/base/panel';

/*
 * Types & Interfaces.
 */

export interface RequestAuthorizationNoneProps {}

/*
 * Component Setup.
 */

defineProps<RequestAuthorizationNoneProps>();
</script>

<template>
    <AppPanel class="border-b shadow-none">
        <AppPanelHeader class="items-start">
            <AppIcon name="i-hugeicons-square-unlock-02" class="size-icon-lg shrink-0" />
            <div class="flex flex-col">
                <AppPanelTitle>No Authorization</AppPanelTitle>
                <AppPanelDescription>
                    You're performing the request as a guest.
                </AppPanelDescription>
            </div>
        </AppPanelHeader>
    </AppPanel>
</template>
