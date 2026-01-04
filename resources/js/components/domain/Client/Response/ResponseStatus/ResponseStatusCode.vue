<script setup lang="ts">
import { AppBadge } from '@/components/base/badge';
import StatusIndicator from '@/components/domain/Client/Response/ResponseStatus/StatusIndicator.vue';
import { Response, STATUS } from '@/interfaces/http';

interface ResponseStatusCodeProps {
    status: STATUS;
    response: Response | undefined;
}

const props = defineProps<ResponseStatusCodeProps>();
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
