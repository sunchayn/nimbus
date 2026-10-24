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

export interface ResponseStatusCodeProps {
    status: STATUS;
    response: Response | undefined;
}

/*
 * Component Setup.
 */

const props = defineProps<ResponseStatusCodeProps>();
</script>

<template>
    <div class="gap-base flex items-center">
        <StatusIndicator :status="props.status" data-testid="response-status-indicator" />
        <span class="text-xs text-nowrap" data-testid="response-status-text">
            {{ props.status }}
        </span>
        <AppBadge
            v-if="props.response && props.status !== STATUS.DUMP_AND_DIE"
            variant="outline"
            color="neutral"
            class="text-nowrap"
            data-testid="response-status-badge"
        >
            {{ props.response.statusCode }} -
            {{ props.response.statusText }}
        </AppBadge>
        <AppBadge
            v-else-if="props.status === STATUS.PENDING"
            variant="outline"
            color="neutral"
            class="text-nowrap"
        >
            -
        </AppBadge>
    </div>
</template>
