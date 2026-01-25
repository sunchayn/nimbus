<script setup lang="ts">
/**
 * @component ResponseDumpAndDie
 * @description Renders "Dump and Die" (dd) debug output, with navigation and management of dump snapshots.
 */
import { AppButton } from '@/components/base/button';
import AppRoundIndicator from '@/components/base/round-indicator/AppRoundIndicator.vue';
import {
    type DumpValue,
    SingleDumpRenderer,
} from '@/components/domain/Client/Response/ResponseBody/DumpRenderer';
import PanelSubHeader from '@/components/layout/PanelSubHeader/PanelSubHeader.vue';
import { ChevronLeft, ChevronRight, Trash2Icon } from 'lucide-vue-next';
import { computed, reactive, type Ref, ref, watch } from 'vue';

/*
 * Types & Interfaces.
 */

export interface DumpSnapshot {
    id: string;
    timestamp: string;
    source: string;
    dumps: DumpValue[];
}

export interface AppResponseDieAndDumpProps {
    rawContent: string;
}

export interface AppResponseDieAndDumpEmits {
    (e: 'update:dumps', dumps: DumpSnapshot[]): void;
}

interface DeletionState {
    deleting: boolean;
    timeoutId?: number;
}

/*
 * Constants.
 */

const DELETION_CONFIRMATION_TIMEOUT = 1200;

/*
 * Component Setup.
 */

const props = defineProps<AppResponseDieAndDumpProps>();
const emits = defineEmits<AppResponseDieAndDumpEmits>();

/*
 * State.
 */

const dumpSnapshots: Ref<DumpSnapshot[]> = ref([]);
const selectedDumpIndex = ref<number>(0);
const deletionStatesForDumps = reactive(new Map<string, DeletionState>());

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

const clearDumpDeletionState = (dumpId: string): void => {
    const state = deletionStatesForDumps.get(dumpId);

    if (state?.timeoutId) {
        clearTimeout(state.timeoutId);
    }

    deletionStatesForDumps.delete(dumpId);
};

const setDumpDeletionState = (dumpId: string): void => {
    const timeoutId: number = window.setTimeout(() => {
        deletionStatesForDumps.delete(dumpId);
    }, DELETION_CONFIRMATION_TIMEOUT);

    deletionStatesForDumps.set(dumpId, {
        deleting: true,
        timeoutId,
    });
};

const initiateDumpDeletion = (dumpId: string): boolean => {
    const state = deletionStatesForDumps.get(dumpId);

    if (state?.deleting) {
        clearDumpDeletionState(dumpId);

        return true;
    }

    setDumpDeletionState(dumpId);

    return false;
};

const isDumpMarkedForDeletion = (dumpId: string): boolean => {
    return deletionStatesForDumps.get(dumpId)?.deleting ?? false;
};

const handleDeleteDump = (): void => {
    const currentDump = selectedDumpsLog.value;

    if (!currentDump) {
        return;
    }

    const shouldDelete = initiateDumpDeletion(currentDump.id);

    if (!shouldDelete) {
        return;
    }

    const currentIndex = selectedDumpIndex.value;
    const dumpsArray = [...dumpSnapshots.value];

    dumpsArray.splice(currentIndex, 1);

    // Update local state first
    dumpSnapshots.value = dumpsArray;

    // Then emit the update
    emits('update:dumps', dumpsArray);

    // Adjust the selected index
    if (dumpsArray.length > 0) {
        selectedDumpIndex.value = Math.min(currentIndex, dumpsArray.length - 1);
    } else {
        selectedDumpIndex.value = 0;
    }

    // Clean up the deletion state
    clearDumpDeletionState(currentDump.id);
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

            // Check if we already have this dump in our session history
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
            console.error('Failed to parse dump snapshot:', error);
        }
    },
    { immediate: true },
);
</script>

<template>
    <PanelSubHeader class="border-b">
        <div class="gap-.5 flex flex-col">
            <span class="text-foreground text-xs">
                {{ selectedDumpsLog?.source ?? 'Unknown Source' }}
            </span>
        </div>
        <template #toolbox>
            <div class="flex items-center gap-2">
                <div v-if="hasMultipleDumps" class="flex items-center gap-1">
                    <AppButton
                        variant="ghost"
                        size="xs"
                        :disabled="!canGoPrevious"
                        data-testid="previous-dump-button"
                        @click="goToPrevious"
                    >
                        <ChevronLeft class="size-3" />
                    </AppButton>
                    <span class="text-xxs text-subtle-foreground select-none">
                        {{ selectedDumpIndex + 1 }} /
                        {{ dumpSnapshots.length }}
                    </span>
                    <AppButton
                        variant="ghost"
                        size="xs"
                        :disabled="!canGoNext"
                        data-testid="next-dump-button"
                        @click="goToNext"
                    >
                        <ChevronRight class="size-3" />
                    </AppButton>
                    <AppButton
                        v-if="selectedDumpsLog"
                        variant="outline"
                        size="xs"
                        class="shadow-none"
                        data-testid="delete-dump-button"
                        @click="handleDeleteDump"
                    >
                        <Trash2Icon
                            class="size-3"
                            :class="{
                                'text-destructive': isDumpMarkedForDeletion(
                                    selectedDumpsLog.id,
                                ),
                            }"
                        />
                    </AppButton>
                </div>
            </div>
        </template>
    </PanelSubHeader>
    <div
        class="p-panel bg-subtle flex h-full min-h-0 flex-1 flex-col gap-1 overflow-y-auto"
    >
        <div
            v-if="selectedDumpsLog?.timestamp"
            class="text-xxs text-subtle-foreground my-1"
        >
            Dumped At:
            {{ selectedDumpsLog?.timestamp ?? '' }}
        </div>

        <div
            v-if="selectedDumpsLog === undefined || selectedDumpsLog === null"
            class="border-subtle bg-card rounded-md border text-sm"
        >
            <div class="px-panel flex items-center gap-1.5 border-b py-1">
                <AppRoundIndicator class="text-subtle-foreground" />
                Info
            </div>
            <div class="p-panel">Please make sure one dump snapshot is selected.</div>
        </div>

        <div
            v-else-if="selectedDumpsLog.dumps.length === 0"
            class="border-subtle bg-card rounded-md border text-sm"
        >
            <div class="px-panel flex items-center gap-1.5 border-b py-1">
                <AppRoundIndicator class="text-destructive" />
                Error
            </div>
            <div class="p-panel">
                <p>
                    Something Went Wrong! Die and Dump is detected but there is no dumps.
                </p>
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
            </div>
        </div>

        <div v-else :key="selectedDumpsLog.id" class="flex flex-col gap-2.5">
            <div
                v-for="(dump, index) in selectedDumpsLog?.dumps ?? []"
                :key="index"
                class="border-subtle bg-card rounded-md border"
                :data-testid="`dump-value-${index}`"
            >
                <div class="px-panel relative z-10 flex gap-1.5 border-b py-1">
                    <AppRoundIndicator class="text-subtle-foreground pt-1" />
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
