<script setup lang="ts">
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
import { LockKeyholeOpenIcon } from 'lucide-vue-next';

/*
 * Types & Interfaces.
 */

export interface AppRequestAuthorizationNoneProps {}

/*
 * Component Setup.
 */

defineProps<AppRequestAuthorizationNoneProps>();
</script>

<template>
    <AppPanel class="border-b shadow-none">
        <AppPanelHeader class="items-start">
            <LockKeyholeOpenIcon class="size-5 min-w-5" />
            <div class="flex flex-col">
                <AppPanelTitle>No Authorization</AppPanelTitle>
                <AppPanelDescription>
                    You're performing the request as a guest.
                </AppPanelDescription>
            </div>
        </AppPanelHeader>
    </AppPanel>
</template>
