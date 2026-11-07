<script setup lang="ts">
/**
 * @component RateLimitMeter
 * @description A small meter of the remaining attempts of a rate limited route, with a limiter reset action.
 */
import { AppButton } from '@/components/base/button';
import { AppPopover } from '@/components/base/popover';
import RateLimitEphemeralWarningContent from '@/components/domain/Client/Response/ResponseStatus/RateLimitEphemeralWarningContent.vue';
import { useConfigStore } from '@/stores';
import type { RateLimit } from '@/utils/response/rateLimit';
import { computed } from 'vue';

/*
 * Types & Interfaces.
 */

export interface RateLimitMeterProps {
    rateLimit: RateLimit;
}

/*
 * Component Setup.
 */

const props = defineProps<RateLimitMeterProps>();

const emit = defineEmits<{ reset: [] }>();

const configStore = useConfigStore();

/*
 * Computed & Methods.
 */

const percentage = computed(() =>
    props.rateLimit.limit > 0
        ? Math.max(
              0,
              Math.min(100, (props.rateLimit.remaining / props.rateLimit.limit) * 100),
          )
        : 0,
);

const isLow = computed(() => percentage.value <= 20);
</script>

<template>
    <AppPopover :content="{ align: 'end', sideOffset: 8, collisionPadding: 8 }">
        <AppButton
            v-if="configStore.isRateLimiterEphemeral"
            size="xs"
            variant="outline"
            color="warning"
            icon="i-hugeicons-alert-02"
            label="No rate limit"
            aria-label="Rate limit"
            data-testid="rate-limit-meter"
        />
        <button
            v-else
            type="button"
            class="gap-base hover:bg-elevated px-base flex cursor-pointer items-center rounded-sm text-xs"
            data-testid="rate-limit-meter"
            aria-label="Rate limit"
        >
            <span
                class="bg-accented h-1 w-8 overflow-hidden rounded-full"
                aria-hidden="true"
            >
                <span
                    class="block h-full rounded-full"
                    :class="isLow ? 'bg-error' : 'bg-success'"
                    :style="{ width: `${percentage}%` }"
                />
            </span>
            <span class="tabular-nums" data-testid="rate-limit-count">
                {{ rateLimit.remaining }}/{{ rateLimit.limit }}
            </span>
        </button>
        <template #content>
            <RateLimitEphemeralWarningContent v-if="configStore.isRateLimiterEphemeral" />
            <div v-else class="gap-base flex flex-col text-xs">
                <p>
                    {{ rateLimit.remaining }} of {{ rateLimit.limit }} attempts left.
                    <span class="block text-muted" v-if="rateLimit.retryAfter !== null">
                        Try again in {{ rateLimit.retryAfter }} seconds.
                    </span>
                </p>
                <AppButton
                    size="xs"
                    variant="outline"
                    icon="i-hugeicons-refresh"
                    label="Reset limiter and resend"
                    data-testid="rate-limit-reset"
                    @click="emit('reset')"
                />
            </div>
        </template>
    </AppPopover>
</template>
