<script setup lang="ts">
/**
 * @component ResponseStatusCode
 * @description Displays the numeric status code and status text badge.
 */
import { AppBadge } from '@/components/base/badge';
import StatusIndicator from '@/components/domain/Client/Response/ResponseStatus/StatusIndicator.vue';
import { type Response, STATUS } from '@/interfaces/http';

/*
 * Types & Interfaces.
 */

export interface AppResponseStatusCodeProps {
    status: STATUS;
    response: Response | undefined;
}

/*
 * Component Setup.
 */

const props = defineProps<AppResponseStatusCodeProps>();
</script>

<template>
    <div class="flex items-center space-x-2">
        <StatusIndicator :status="props.status" data-testid="response-status-indicator" />
        <span class="text-xs text-nowrap" data-testid="response-status-text">
            {{ props.status }}
        </span>
        <AppBadge
            v-if="props.response && props.status !== STATUS.DUMP_AND_DIE"
            variant="outline"
            class="text-nowrap"
            data-testid="response-status-badge"
        >
            {{ props.response.statusCode }} -
            {{ props.response.statusText }}
        </AppBadge>
        <AppBadge
            v-else-if="props.status === STATUS.PENDING"
            variant="outline"
            class="text-nowrap"
        >
            -
        </AppBadge>
    </div>
</template>
