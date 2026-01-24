<script setup lang="ts">
/**
 * @component StatusIndicator
 * @description A colored indicator dot representing the response status (success, error, redirect, etc.).
 */
import Spinner from "@/components/base/icons/AppSpinner.vue";
import AppRoundIndicator from "@/components/base/round-indicator/AppRoundIndicator.vue";
import { STATUS } from "@/interfaces/http";
import { computed } from "vue";

/*
 * Types & Interfaces.
 */

export interface AppStatusIndicatorProps {
    status: STATUS;
}

/*
 * Component Setup.
 */

const props = defineProps<AppStatusIndicatorProps>();

/*
 * Constants.
 */

const variants = {
    [STATUS.INFORMATION]: 'text-zinc-500',
    [STATUS.SUCCESS]: 'text-emerald-600',
    [STATUS.REDIRECT]: 'text-blue-500',
    [STATUS.CLIENT_ERROR]: 'text-amber-500',
    [STATUS.SERVER_ERROR]: 'text-rose-500',
    [STATUS.OTHER]: 'text-zinc-500',
    [STATUS.EMPTY]: 'text-zinc-900',
    [STATUS.DUMP_AND_DIE]: 'text-violet-600',
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
        class="text-accent-foreground size-4 animate-spin"
    />
    <AppRoundIndicator v-else :class="indicatorColor" />
</template>
