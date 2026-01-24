<script setup lang="ts">
/**
 * @component RequestAuthorizationCurrentUser
 * @description Info card displaying the current user's authentication status.
 */
import {
    AppCard,
    AppCardDescription,
    AppCardHeader,
    AppCardTitle,
} from '@/components/base/card';
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
    <AppCard
        class="px-panel bg-background relative flex items-start gap-2 rounded-none border-0 border-b py-2.5 shadow-none"
    >
        <User2Icon v-if="configStore.isLoggedIn" />
        <AlertCircleIcon v-else class="text-destructive" />
        <AppCardHeader class="p-0">
            <AppCardTitle v-if="configStore.isLoggedIn">You're logged in!</AppCardTitle>
            <AppCardTitle v-else>Please log in first</AppCardTitle>
            <AppCardDescription v-if="configStore.isLoggedIn">
                You're performing the request acting as the currently logged-in user
                <span class="whitespace-nowrap">(ID: {{ configStore.userId }})</span>
                .
            </AppCardDescription>
            <AppCardDescription v-else>
                You need to be logged in to make requests as the currently logged-in user.
            </AppCardDescription>
        </AppCardHeader>
    </AppCard>
</template>
