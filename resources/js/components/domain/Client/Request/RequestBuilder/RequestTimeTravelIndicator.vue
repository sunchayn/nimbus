<script setup lang="ts">
/**
 * @component RequestTimeTravelIndicator
 * @description An icon next to the endpoint that shows the application clock is fixed.
 * Its popover lets the user change the time or go back to the real one.
 */
import { AppButton } from '@/components/base/button';
import { AppPopover } from '@/components/base/popover';
import { useRequestStore } from '@/stores';
import { computed, shallowRef } from 'vue';
import TimeTravelDialog from './TimeTravelDialog.vue';

/*
 * Stores.
 */

const requestStore = useRequestStore();

/*
 * State.
 */

const showPopover = shallowRef(false);
const showDialog = shallowRef(false);

/*
 * Computed & Methods.
 */

const timeTravel = computed(() => requestStore.pendingRequestData?.timeTravel?.trim());

const clear = () => {
    requestStore.updateTimeTravel('');
    showPopover.value = false;
};

const change = () => {
    showPopover.value = false;
    showDialog.value = true;
};
</script>

<template>
    <div
        v-if="timeTravel"
        class="pane-surface size-pane-header flex shrink-0 items-center justify-center"
    >
        <AppPopover
            :open="showPopover"
            :content="{ align: 'end', sideOffset: 12, alignOffset: -6 }"
            @update:open="showPopover = $event"
        >
            <AppButton
                variant="ghost"
                color="warning"
                size="sm"
                icon="i-hugeicons-clock-01"
                :title="`The application clock is set to ${timeTravel}`"
                aria-label="The application clock is fixed"
                data-testid="time-travel-indicator"
            />

            <template #content>
                <div class="gap-base flex flex-col" data-testid="time-travel-popover">
                    <p class="text-muted text-xs">
                        The application clock is set to
                        <span class="text-default font-medium">{{ timeTravel }}</span>
                    </p>
                    <div class="gap-base flex">
                        <AppButton
                            size="xs"
                            variant="outline"
                            color="neutral"
                            label="Clear"
                            data-testid="time-travel-indicator-clear"
                            @click="clear"
                        />
                        <AppButton
                            size="xs"
                            variant="outline"
                            color="neutral"
                            label="Change"
                            data-testid="time-travel-indicator-change"
                            @click="change"
                        />
                    </div>
                </div>
            </template>
        </AppPopover>
    </div>

    <TimeTravelDialog
        v-model:open="showDialog"
        :value="timeTravel ?? ''"
        @save="requestStore.updateTimeTravel"
    />
</template>
