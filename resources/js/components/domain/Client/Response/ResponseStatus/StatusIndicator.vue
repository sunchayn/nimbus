<script setup lang="ts">
/**
 * @component StatusIndicator
 * @description A colored indicator dot representing the response status (success, error, redirect, etc.).
 */
import Spinner from '@/components/base/icons/AppSpinner.vue';
import AppRoundIndicator from '@/components/base/round-indicator/AppRoundIndicator.vue';
import { STATUS } from '@/interfaces/http';
import { computed } from 'vue';

/*
 * Types & Interfaces.
 */

export interface StatusIndicatorProps {
    status: STATUS;
}

/*
 * Component Setup.
 */

const props = defineProps<StatusIndicatorProps>();

/*
 * Constants.
 */

const variants = {
    [STATUS.INFORMATION]: 'text-muted',
    [STATUS.SUCCESS]: 'text-success',
    [STATUS.REDIRECT]: 'text-info',
    [STATUS.CLIENT_ERROR]: 'text-warning',
    [STATUS.SERVER_ERROR]: 'text-error',
    [STATUS.OTHER]: 'text-muted',
    [STATUS.EMPTY]: 'text-highlighted',
    [STATUS.DUMP_AND_DIE]: 'text-secondary',
    [STATUS.PENDING]: '',
};

/*
 * Computed & Methods.
 */

const indicatorColor = computed<string>(() => {
    return variants[props.status];
});
</script>

<template>
    <Spinner
        v-if="props.status === STATUS.PENDING"
        data-testid="pending-request-spinner"
        class="text-default size-icon-md animate-spin"
    />
    <AppRoundIndicator
        v-else
        :class="[indicatorColor, 'duration-base transition-colors']"
    />
</template>
