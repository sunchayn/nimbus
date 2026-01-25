<script setup lang="ts">
/**
 * @component RequestAuthorizationCurrentUser
 * @description Info panel displaying the current user's authentication status.
 */
import {
    AppPanel,
    AppPanelDescription,
    AppPanelHeader,
    AppPanelTitle,
} from '@/components/base/panel';
import { useConfigStore } from '@/stores';
import { AlertCircleIcon, User2Icon } from 'lucide-vue-next';

/*
 * Types & Interfaces.
 */

export interface AppRequestAuthorizationCurrentUserProps {}

/*
 * Component Setup.
 */

defineProps<AppRequestAuthorizationCurrentUserProps>();

const configStore = useConfigStore();
</script>

<template>
    <AppPanel class="border-b shadow-none">
        <AppPanelHeader class="items-start">
            <User2Icon v-if="configStore.isLoggedIn" class="size-5 min-w-5" />
            <AlertCircleIcon v-else class="text-destructive size-5 min-w-5" />
            <div class="flex flex-col">
                <AppPanelTitle v-if="configStore.isLoggedIn">
                    You're logged in!
                </AppPanelTitle>
                <AppPanelTitle v-else>Please log in first</AppPanelTitle>
                <AppPanelDescription v-if="configStore.isLoggedIn">
                    You're performing the request acting as the currently logged-in user
                    <span class="whitespace-nowrap">(ID: {{ configStore.userId }})</span>
                    .
                </AppPanelDescription>
                <AppPanelDescription v-else>
                    You need to be logged in to make requests as the currently logged-in
                    user.
                </AppPanelDescription>
            </div>
        </AppPanelHeader>
    </AppPanel>
</template>
