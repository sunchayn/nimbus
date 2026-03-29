<script setup lang="ts">
/**
 * @component RequestBodyFormData
 * @description Key-value editor for FormData request bodies.
 */
import KeyValueParametersBuilder from '@/components/common/KeyValueParameters/KeyValueParameters.vue';
import { type ParameterContract } from '@/interfaces/ui';
import { ParameterType } from '@/interfaces/ui/key-value-parameters';
import { nextTick, ref, watch } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppRequestBodyFormDataProps {}

export interface AppRequestBodyFormDataEmits {
    (e: 'update:modelValue', value: FormData | null): void;
}

/*
 * Component Setup.
 */

defineProps<AppRequestBodyFormDataProps>();
const emit = defineEmits<AppRequestBodyFormDataEmits>();

const model = defineModel<FormData | null>({
    default: () => null,
});

/*
 * State.
 */

// A guard flag to prevent endless syncing looping between the component and parent as it will mutate its dependency.
const isPropagatingChangesToParent = ref(false);

const payload = ref<ParameterContract[]>([]);

/*
 * Actions.
 */

function convertParametersArrayToFormData(parameters: ParameterContract[]): FormData {
    const formData = new FormData();

    for (const parameter of parameters) {
        formData.set(parameter.key, parameter.value);
    }

    return formData;
}

function convertFormDataToParametersArray(form: FormData): ParameterContract[] {
    const parameters: ParameterContract[] = [];

    form.forEach((entryValue: FormDataEntryValue, key: string) => {
        if (entryValue instanceof File) {
            // For files, we'll store the filename as a placeholder
            // Note: File uploads are not properly tested or verified.
            // TODO [Feature] Properly support file uploads.
            parameters.push({
                type: ParameterType.File,
                key: key,
                value: entryValue.name,
                enabled: true,
            });

            return;
        }

        parameters.push({
            type: ParameterType.Text,
            key: key,
            value: String(entryValue),
            enabled: true,
        });
    });

    return parameters;
}

const handlePayloadUpdate = (parameters: ParameterContract[]) => {
    isPropagatingChangesToParent.value = true;
    payload.value = parameters;
    emit('update:modelValue', convertParametersArrayToFormData(parameters));
};

/*
 * Watchers.
 */

watch(
    model,
    (newModel: FormData | null) => {
        if (newModel === null) {
            return;
        }

        if (isPropagatingChangesToParent.value) {
            return;
        }

        // Re-initialize the payload if the parent updated the payload from an exterior source.
        // For instance, when the user changes to a different endpoint.
        payload.value =
            newModel instanceof FormData
                ? convertFormDataToParametersArray(newModel)
                : [];

        nextTick(() => {
            isPropagatingChangesToParent.value = false;
        });
    },
    { deep: true },
);
</script>

<template>
    <KeyValueParametersBuilder
        :model-value="payload"
        :free-form-types="true"
        @update:parameters="handlePayloadUpdate"
    />
</template>
