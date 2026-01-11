<script setup lang="ts">
import { AppBadge } from '@/components/base/badge';
import { AppButton } from '@/components/base/button';
import { AppInput } from '@/components/base/input';
import {
    AppSelect,
    AppSelectContent,
    AppSelectItem,
    AppSelectTrigger,
    AppSelectValue,
} from '@/components/base/select';
import { AppSwitch } from '@/components/base/switch';
import { useKeyValueParameters } from '@/composables/ui/useKeyValueParameters';
import { ExtendedParameter, ParametersExternalContract } from '@/interfaces/ui';
import { useValueGeneratorStore } from '@/stores';
import { cn } from '@/utils';
import {
    EyeClosedIcon,
    EyeIcon,
    PlusIcon,
    SparklesIcon,
    Trash2Icon,
} from 'lucide-vue-next';
import { computed, type HTMLAttributes, ref } from 'vue';
import AppTooltipWrapper from '../../base/tooltip/AppTooltipWrapper.vue';

/*
 * Props.
 */

const props = withDefaults(
    defineProps<{
        freeFormTypes?: boolean;
        class?: HTMLAttributes['class'];
        persistenceKey?: string;
    }>(),
    {
        freeFormTypes: false,
        class: undefined,
        persistenceKey: undefined,
    },
);

/*
 * Model.
 */

const model = defineModel<ParametersExternalContract[]>();

const modelRef = computed({
    get: () => model.value ?? [],
    set: value => (model.value = value),
});

/*
 * Composables.
 */

const {
    parameters,
    deletingAll,
    isParameterMarkedForDeletion,
    areAllParametersDisabled,
    addNewEmptyParameter,
    toggleAllParametersEnabledState,
    triggerParameterDeletion,
    deleteAllParameters,
} = useKeyValueParameters(modelRef, props.persistenceKey);

const { openCommand, closeCommand } = useValueGeneratorStore();

// Focus management
const focusedInputIndex = ref<number | null>(null);
const focusedInputRef = ref<HTMLInputElement | null>(null);

/*
 * Event Handlers.
 */

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

    // Delay command closure to prevent race condition between blur and command opening
    // Problem: When user clicks generator button, blur fires immediately and closes command
    // before openCommand can complete, causing command to flash open then close
    // Solution: 100ms delay allows openCommand to finish before closeCommand executes
    setTimeout(closeCommand, 100);
};

const handleGeneratorClick = () => {
    if (!focusedInputRef.value) {
        return;
    }

    openCommand(focusedInputRef.value);
};

const handleDeleteParameter = (index: number) => {
    triggerParameterDeletion(parameters.value, index);
};

/*
 * Computed Properties.
 */

const shouldShowGeneratorIcon = (index: number, parameter: ExtendedParameter) => {
    return focusedInputIndex.value === index && parameter.enabled;
};
</script>
<template>
    <div
        :class="cn('flex h-full flex-col overflow-hidden', props.class)"
        data-testid="kv-container"
    >
        <!-- Header Actions -->
        <div class="flex h-8 items-center overflow-hidden border-b p-0">
            <AppButton
                variant="ghost"
                size="xs"
                class="px-panel h-full -translate-x-0.5 rounded-none text-xs"
                data-testid="add-button"
                @click="addNewEmptyParameter"
            >
                <PlusIcon />
                Add
            </AppButton>

            <AppButton
                variant="ghost"
                size="xs"
                class="px-panel h-full -translate-x-0.5 rounded-none text-xs"
                :disabled="parameters.length === 0"
                data-testid="enable-all-button"
                @click="toggleAllParametersEnabledState"
            >
                <EyeIcon v-if="areAllParametersDisabled" />
                <EyeClosedIcon v-else />
                {{ areAllParametersDisabled ? 'Enable All' : 'Disable All' }}
            </AppButton>

            <AppButton
                variant="ghost"
                size="xs"
                class="px-panel h-full -translate-x-0.5 rounded-none text-xs"
                :class="{
                    '!text-red-500 hover:text-red-500 dark:!text-rose-700 dark:hover:text-red-700':
                        deletingAll,
                }"
                :disabled="parameters.length === 0"
                data-testid="delete-all-button"
                @click="deleteAllParameters"
            >
                <Trash2Icon />
                Delete All
            </AppButton>
        </div>

        <!-- Parameters List -->
        <div class="flex-1 overflow-y-auto">
            <div
                v-for="(parameter, index) in parameters"
                :key="parameter.id"
                class="flex h-8 border-b"
                data-testid="parameter-row"
            >
                <!-- Parameter Inputs -->
                <div class="flex flex-1">
                    <!-- Type Selector (conditional) -->
                    <AppSelect
                        v-if="freeFormTypes"
                        v-model="parameter.type"
                        default-value="text"
                    >
                        <AppSelectTrigger
                            class="pl-panel h-full w-[80px] rounded-none border-0 border-r p-0 text-xs shadow-none focus:ring-0"
                            data-testid="type-selector"
                        >
                            <AppSelectValue placeholder="Select a Type" />
                        </AppSelectTrigger>
                        <AppSelectContent>
                            <AppSelectItem value="text">Text</AppSelectItem>
                            <AppSelectItem value="file" disabled>
                                File
                                <AppBadge variant="outline" class="ml-0.5 px-1 py-0">
                                    soon
                                </AppBadge>
                            </AppSelectItem>
                        </AppSelectContent>
                    </AppSelect>

                    <!-- Key Input -->
                    <AppInput
                        v-model="parameter.key"
                        placeholder="Key"
                        class="selector-key h-full flex-1 rounded-none border-0 border-r shadow-none focus:ring-0 focus-visible:ring-0"
                        :disabled="!parameter.enabled"
                        name="kv-key"
                        data-testid="kv-key"
                    />

                    <!-- Value Input -->
                    <AppInput
                        v-model="parameter.value"
                        placeholder="Value"
                        class="pl-panel h-full flex-1 rounded-none border-0 border-r shadow-none focus:ring-0 focus-visible:ring-0"
                        :disabled="!parameter.enabled"
                        name="kv-value"
                        data-testid="kv-value"
                        @focus="handleValueInputFocus(index, $event.target)"
                        @blur="handleValueInputBlur"
                    />
                </div>

                <!-- Enable/Disable Toggle -->
                <div class="flex items-center justify-center border-r px-2">
                    <AppTooltipWrapper value="Enable/Disable">
                        <AppSwitch v-model="parameter.enabled" class="h-4 w-8" />
                    </AppTooltipWrapper>
                </div>

                <!-- Action Button: Generator or Delete -->
                <div
                    v-if="shouldShowGeneratorIcon(index, parameter)"
                    class="flex cursor-pointer items-center justify-center p-2"
                    title="Generate Value"
                    data-testid="generator-button"
                    @mousedown.prevent="handleGeneratorClick"
                >
                    <SparklesIcon
                        class="text-subtle size-4 transition-colors hover:text-zinc-800 dark:hover:text-zinc-200"
                    />
                </div>

                <div
                    v-else
                    class="flex items-center justify-center px-2"
                    data-testid="delete-button"
                >
                    <AppTooltipWrapper
                        value="Delete"
                        :on-click="() => handleDeleteParameter(index)"
                    >
                        <Trash2Icon
                            class="size-4"
                            :class="{
                                'text-rose-500 dark:text-rose-700':
                                    isParameterMarkedForDeletion(parameter.id),
                            }"
                        />
                    </AppTooltipWrapper>
                </div>
            </div>
        </div>
    </div>
</template>
