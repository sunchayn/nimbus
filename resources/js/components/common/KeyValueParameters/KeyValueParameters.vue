<script setup lang="ts">
/**
 * @component KeyValueParameters
 * @description An interactive editor for key-value parameter lists with integrated value generation.
 */
import { AppBadge } from '@/components/base/badge';
import { AppButton } from '@/components/base/button';
import { AppIcon } from '@/components/base/icons';
import AppSwapIcon from '@/components/base/icons/AppSwapIcon.vue';
import { AppSelect } from '@/components/base/select';
import { AppSwitch } from '@/components/base/switch';
import { AppTooltip } from '@/components/base/tooltip';
import EnvironmentAwareInput from '@/components/common/EnvironmentAwareInput.vue';
import GeneratorInput from '@/components/common/GeneratorInput.vue';
import { useKeyValueParameters } from '@/composables/ui/useKeyValueParameters';
import { type ParameterContract } from '@/interfaces/ui';
import { useValueGeneratorStore } from '@/stores';
import { cn } from '@/utils/ui';
import { useTimeoutFn } from '@vueuse/core';
import { computed, type HTMLAttributes, ref, shallowRef } from 'vue';

/*
 * Types & Interfaces.
 */

export interface KeyValueParametersProps {
    modelValue?: ParameterContract[];
    freeFormTypes?: boolean;
    class?: HTMLAttributes['class'];
}

export interface KeyValueParametersEmits {
    (e: 'update:parameters', parameters: ParameterContract[]): void;
}

/*
 * Component Setup.
 */

const props = withDefaults(defineProps<KeyValueParametersProps>(), {
    modelValue: () => [],
    freeFormTypes: false,
    class: undefined,
});

const emit = defineEmits<KeyValueParametersEmits>();

const { openCommand, closeCommand } = useValueGeneratorStore();
const { start: scheduleCloseCommand } = useTimeoutFn(closeCommand, 100, {
    immediate: false,
});

/*
 * State.
 */

const typeItems = [
    { label: 'Text', value: 'text' },
    { label: 'File', value: 'file', disabled: true },
];

/*
 * Refs.
 */

const focusedInputIndex = shallowRef<number | null>(null);
const focusedInputRef = ref<HTMLInputElement | null>(null);

/*
 * Composables.
 */

const modelValueRef = computed(() => props.modelValue);

const handleParametersUpdate = (parameters: ParameterContract[]) => {
    emit('update:parameters', parameters);
};

const {
    parameters,
    deletingAll,
    isParameterMarkedForDeletion,
    areAllParametersDisabled,
    addNewEmptyParameter,
    toggleAllParametersEnabledState,
    triggerParameterDeletion,
    deleteAllParameters,
} = useKeyValueParameters(modelValueRef, handleParametersUpdate);

/*
 * Computed & Methods.
 */

const shouldShowGeneratorIcon = (index: number, parameter: ParameterContract) => {
    return focusedInputIndex.value === index && parameter.enabled;
};

const handleValueInputFocus = (index: number, inputRef: HTMLInputElement) => {
    focusedInputIndex.value = index;
    focusedInputRef.value = inputRef;
};

const handleValueInputBlur = (event: FocusEvent) => {
    const relatedTarget = event.relatedTarget as HTMLElement;

    // Prevent blur if focus is moving to the value generator menu
    if (relatedTarget?.closest('[data-ValueGenerator-focus-hook]')) {
        return;
    }

    focusedInputIndex.value = null;
    focusedInputRef.value = null;

    // Clicking the generator button fires blur first, which would close the command too early.
    // The short delay lets openCommand finish before closeCommand runs.
    scheduleCloseCommand();
};

const handleGeneratorClick = () => {
    if (!focusedInputRef.value) {
        return;
    }

    openCommand(focusedInputRef.value);
};

// Rows only animate for edits the user makes here, so switching requests stays instant.
const animateRows = shallowRef(false);

const { start: scheduleRowMotionEnd } = useTimeoutFn(
    () => (animateRows.value = false),
    300,
    {
        immediate: false,
    },
);

const withRowMotion = (action: () => void) => {
    animateRows.value = true;
    action();
    scheduleRowMotionEnd();
};

const handleDeleteParameter = (index: number) => {
    withRowMotion(() => triggerParameterDeletion(index));
};
</script>
<template>
    <div
        :class="cn('flex h-full flex-col overflow-hidden', props.class)"
        data-testid="kv-container"
    >
        <!-- Header Actions -->
        <div class="h-control-lg flex items-center overflow-hidden border-b p-0">
            <AppButton
                variant="ghost"
                size="xs"
                icon="i-hugeicons-plus-sign"
                label="Add"
                class="px-panel h-full -translate-x-0.5 rounded-none"
                data-testid="add-button"
                @click="withRowMotion(addNewEmptyParameter)"
            />

            <AppButton
                variant="ghost"
                size="xs"
                :icon="
                    areAllParametersDisabled
                        ? 'i-hugeicons-view'
                        : 'i-hugeicons-view-off-slash'
                "
                :label="areAllParametersDisabled ? 'Enable All' : 'Disable All'"
                class="px-panel h-full -translate-x-0.5 rounded-none"
                :disabled="parameters.length <= 1"
                data-testid="enable-all-button"
                @click="toggleAllParametersEnabledState"
            />

            <AppButton
                variant="ghost"
                size="xs"
                icon="i-hugeicons-delete-02"
                label="Delete All"
                :color="deletingAll ? 'error' : undefined"
                class="px-panel h-full -translate-x-0.5 rounded-none"
                :disabled="parameters.length <= 1"
                data-testid="delete-all-button"
                @click="withRowMotion(deleteAllParameters)"
            />
        </div>

        <!-- Parameters List -->
        <TransitionGroup
            tag="div"
            name="kv-row"
            :css="animateRows"
            class="flex-1 overflow-y-auto"
        >
            <div
                v-for="(parameter, index) in parameters"
                :key="parameter.id"
                class="h-control-lg flex border-b"
                data-testid="parameter-row"
            >
                <!-- Parameter Inputs -->
                <div class="flex flex-1">
                    <!-- Type Selector (conditional) -->
                    <AppSelect
                        v-if="freeFormTypes"
                        v-model="parameter.type"
                        default-value="text"
                        :items="typeItems"
                        variant="ghost"
                        placeholder="Select a Type"
                        class="ps-panel border-default h-full w-20 rounded-none border-r"
                        data-testid="type-selector"
                    >
                        <template #item-label="{ item }">
                            {{ item.label }}
                            <AppBadge
                                v-if="item.value === 'file'"
                                variant="outline"
                                color="neutral"
                            >
                                soon
                            </AppBadge>
                        </template>
                    </AppSelect>

                    <!-- Key Input -->
                    <GeneratorInput
                        v-model="parameter.key"
                        placeholder="Key"
                        variant="none"
                        class="selector-key h-full flex-1 border-r"
                        :disabled="!parameter.enabled"
                        name="kv-key"
                        data-testid="kv-key"
                        autocomplete="off"
                    />

                    <!-- Value Input -->
                    <EnvironmentAwareInput
                        v-model="parameter.value"
                        placeholder="Value"
                        variant="none"
                        :disabled="!parameter.enabled"
                        name="kv-value"
                        data-testid="kv-value"
                        autocomplete="off"
                        class="border-r"
                        input-class="ps-panel h-full flex-1"
                        @keydown.ctrl.space.prevent="handleGeneratorClick"
                        @focus="handleValueInputFocus(index, $event.target)"
                        @blur="handleValueInputBlur"
                    />
                </div>

                <!-- Enable/Disable Toggle -->
                <div class="flex items-center justify-center border-r px-2">
                    <AppTooltip text="Enable/Disable">
                        <AppSwitch
                            v-model="parameter.enabled"
                            :aria-label="
                                parameter.key
                                    ? `Enable ${parameter.key}`
                                    : 'Enable parameter'
                            "
                        />
                    </AppTooltip>
                </div>

                <!-- Action button, either the generator or delete -->
                <Transition name="fade-fast" mode="out-in">
                    <button
                        v-if="shouldShowGeneratorIcon(index, parameter)"
                        type="button"
                        aria-label="Generate value"
                        aria-keyshortcuts="Control+Space"
                        class="flex cursor-pointer items-center justify-center p-2"
                        title="Generate value (Ctrl+Space)"
                        data-testid="generator-button"
                        @mousedown.prevent="handleGeneratorClick"
                    >
                        <AppIcon
                            name="i-hugeicons-sparkles"
                            class="text-muted hover:text-highlighted size-icon-md transition-colors"
                        />
                    </button>

                    <div
                        v-else
                        class="flex items-center justify-center px-1"
                        data-testid="delete-button"
                    >
                        <AppTooltip text="Delete">
                            <button
                                type="button"
                                :aria-label="
                                    isParameterMarkedForDeletion(index)
                                        ? 'Confirm delete'
                                        : 'Delete'
                                "
                                class="p-1"
                                @click="handleDeleteParameter(index)"
                            >
                                <AppSwapIcon
                                    :name="
                                        isParameterMarkedForDeletion(index)
                                            ? 'i-hugeicons-tick-02'
                                            : 'i-hugeicons-delete-02'
                                    "
                                    class="size-icon-md"
                                    :class="{
                                        'text-error': isParameterMarkedForDeletion(index),
                                    }"
                                />
                            </button>
                        </AppTooltip>
                    </div>
                </Transition>
            </div>
        </TransitionGroup>
    </div>
</template>
