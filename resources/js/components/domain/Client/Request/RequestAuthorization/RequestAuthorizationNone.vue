<script setup lang="ts">
/**
 * @component RequestAuthorizationNone
 * @description Info card indicating no authentication is being used.
 */
import {
    AppCard,
    AppCardDescription,
    AppCardHeader,
    AppCardTitle,
} from '@/components/base/card';
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
    <AppCard
        class="px-panel bg-background relative flex items-start gap-2 rounded-none border-0 border-b py-4 shadow-none"
    >
        <LockKeyholeOpenIcon />
        <AppCardHeader class="p-0">
            <AppCardTitle>No Authorization</AppCardTitle>
            <AppCardDescription>
                You're performing the request as a guest.
            </AppCardDescription>
        </AppCardHeader>
    </AppCard>
</template>
