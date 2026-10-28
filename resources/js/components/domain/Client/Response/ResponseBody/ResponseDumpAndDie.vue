<script setup lang="ts">
/**
 * @component ResponseDumpAndDie
 * @description Renders "Dump and Die" (dd) debug output, with navigation and management of dump snapshots.
 */
import { AppButton } from '@/components/base/button';
import AppSwapIcon from '@/components/base/icons/AppSwapIcon.vue';
import AppRoundIndicator from '@/components/base/round-indicator/AppRoundIndicator.vue';
import { useAppToast } from '@/components/base/toast';
import DumpNotice from '@/components/domain/Client/Response/ResponseBody/DumpNotice.vue';
import {
    type DumpValue,
    SingleDumpRenderer,
} from '@/components/domain/Client/Response/ResponseBody/DumpRenderer';
import PanelSubHeader from '@/components/layout/PanelSubHeader/PanelSubHeader.vue';
import { useConfirmationAction } from '@/composables/ui/useConfirmationAction';
import { withOccurrenceKeys } from '@/utils/ui';
import { computed, type Ref, ref, shallowRef, watch } from 'vue';

/*
 * Types & Interfaces.
 */

export interface DumpSnapshot {
    id: string;
    timestamp: string;
    source: string;
    dumps: DumpValue[];
}

export interface ResponseDumpAndDieProps {
    rawContent: string;
}

export interface ResponseDumpAndDieEmits {
    (e: 'update:dumps', dumps: DumpSnapshot[]): void;
}

/*
 * Constants.
 */

/*
 * Component Setup.
 */

const props = defineProps<ResponseDumpAndDieProps>();
const toast = useAppToast();
const emits = defineEmits<ResponseDumpAndDieEmits>();

/*
 * State.
 */

const dumpSnapshots: Ref<DumpSnapshot[]> = ref([]);
const selectedDumpIndex = shallowRef<number>(0);

/*
 * Computed & Methods.
 */

const selectedDumpsLog = computed(() => {
    if (dumpSnapshots.value.length === 0) {
        return null;
    }

    return dumpSnapshots.value[selectedDumpIndex.value] ?? null;
});

const hasMultipleDumps = computed(() => {
    return dumpSnapshots.value.length > 1;
});

const canGoPrevious = computed(() => {
    return selectedDumpIndex.value > 0;
});

const canGoNext = computed(() => {
    return selectedDumpIndex.value < dumpSnapshots.value.length - 1;
});

const keyedDumps = computed(() =>
    withOccurrenceKeys(selectedDumpsLog.value?.dumps ?? [], dump => JSON.stringify(dump)),
);

const { isConfirming: isDumpMarkedForDeletion, trigger: triggerDumpDeletion } =
    useConfirmationAction({ duration: 1200 });

const handleDeleteDump = (): void => {
    const currentDump = selectedDumpsLog.value;

    if (!currentDump) {
        return;
    }

    triggerDumpDeletion(() => deleteDump(), currentDump.id);
};

const deleteDump = (): void => {
    const currentIndex = selectedDumpIndex.value;
    const dumpsArray = [...dumpSnapshots.value];

    dumpsArray.splice(currentIndex, 1);

    dumpSnapshots.value = dumpsArray;

    emits('update:dumps', dumpsArray);

    if (dumpsArray.length > 0) {
        selectedDumpIndex.value = Math.min(currentIndex, dumpsArray.length - 1);
    } else {
        selectedDumpIndex.value = 0;
    }
};

const goToPrevious = () => {
    if (canGoPrevious.value) {
        selectedDumpIndex.value--;
    }
};

const goToNext = () => {
    if (canGoNext.value) {
        selectedDumpIndex.value++;
    }
};

/*
 * Watchers.
 */

watch(
    () => props.rawContent,
    newValue => {
        if (!newValue) {
            return;
        }

        try {
            const newDump = JSON.parse(String(newValue)) as DumpSnapshot;

            const existingIndex = dumpSnapshots.value.findIndex(
                dump => dump.id === newDump.id,
            );

            // If it exists, we just select it (likely a history rewind)
            if (existingIndex !== -1) {
                selectedDumpIndex.value = existingIndex;

                return;
            }

            dumpSnapshots.value = [newDump, ...dumpSnapshots.value];

            selectedDumpIndex.value = 0;
        } catch (error) {
            toast.error('Failed to read the dump snapshot', {
                description: error instanceof Error ? error.message : undefined,
            });
        }
    },
    { immediate: true },
);
</script>

<template>
    <PanelSubHeader class="border-b">
        <div class="flex flex-col">
            <span class="text-default text-xs">
                {{ selectedDumpsLog?.source ?? 'Unknown Source' }}
            </span>
        </div>
        <template #toolbox>
            <div class="gap-base flex items-center">
                <div v-if="hasMultipleDumps" class="gap-tight flex items-center">
                    <AppButton
                        variant="ghost"
                        size="xs"
                        icon="i-hugeicons-arrow-left-01"
                        aria-label="Previous dump"
                        square
                        :disabled="!canGoPrevious"
                        data-testid="previous-dump-button"
                        @click="goToPrevious"
                    />
                    <span class="text-xxs text-muted select-none">
                        {{ selectedDumpIndex + 1 }} /
                        {{ dumpSnapshots.length }}
                    </span>
                    <AppButton
                        variant="ghost"
                        size="xs"
                        icon="i-hugeicons-arrow-right-01"
                        aria-label="Next dump"
                        square
                        :disabled="!canGoNext"
                        data-testid="next-dump-button"
                        @click="goToNext"
                    />
                    <AppButton
                        v-if="selectedDumpsLog"
                        variant="outline"
                        size="xs"
                        :aria-label="
                            isDumpMarkedForDeletion(selectedDumpsLog.id)
                                ? 'Confirm delete dump'
                                : 'Delete dump'
                        "
                        square
                        :color="
                            isDumpMarkedForDeletion(selectedDumpsLog.id)
                                ? 'error'
                                : undefined
                        "
                        data-testid="delete-dump-button"
                        @click="handleDeleteDump"
                    >
                        <template #leading>
                            <AppSwapIcon
                                :name="
                                    isDumpMarkedForDeletion(selectedDumpsLog.id)
                                        ? 'i-hugeicons-tick-02'
                                        : 'i-hugeicons-delete-02'
                                "
                                class="size-icon-sm"
                            />
                        </template>
                    </AppButton>
                </div>
            </div>
        </template>
    </PanelSubHeader>
    <div
        class="p-panel bg-muted gap-base flex h-full min-h-0 flex-1 flex-col overflow-y-auto"
    >
        <div v-if="selectedDumpsLog?.timestamp" class="text-xxs text-muted">
            Dumped At:
            {{ selectedDumpsLog?.timestamp ?? '' }}
        </div>

        <DumpNotice
            v-if="selectedDumpsLog === undefined || selectedDumpsLog === null"
            label="Info"
            indicator-class="text-muted"
        >
            Please make sure one dump snapshot is selected.
        </DumpNotice>

        <DumpNotice
            v-else-if="selectedDumpsLog.dumps.length === 0"
            label="Error"
            indicator-class="text-error"
        >
            <p>Something Went Wrong! Die and Dump is detected but there is no dumps.</p>
            <small>
                If you think this is a bug, please open a
                <a
                    class="underline"
                    href="https://github.com/sunchayn/nimbus/issues/new/choose"
                >
                    Bug
                </a>
                card.
            </small>
        </DumpNotice>

        <div v-else :key="selectedDumpsLog.id" class="gap-base flex flex-col">
            <div
                v-for="({ item: dump, key }, index) in keyedDumps"
                :key="key"
                class="border-muted bg-default rounded-md border"
                :data-testid="`dump-value-${index}`"
            >
                <div class="px-panel z-sticky gap-base py-tight relative flex border-b">
                    <AppRoundIndicator class="text-muted pt-tight" />
                    <span class="inline-flex flex-col" data-testid="dump-value-title">
                        <span class="text-xs">Dump #{{ index + 1 }}</span>
                    </span>
                </div>
                <div
                    class="p-panel w-full overflow-x-auto whitespace-nowrap"
                    data-testid="dump-value-content"
                >
                    <SingleDumpRenderer
                        v-if="selectedDumpsLog"
                        class="w-full max-w-full"
                        :dump="dump"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
