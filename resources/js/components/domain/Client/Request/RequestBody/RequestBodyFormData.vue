<script setup lang="ts">
/**
 * @component RequestBodyFormData
 * @description Key-value editor for FormData request bodies.
 */
import KeyValueParametersBuilder from '@/components/common/KeyValueParameters/KeyValueParameters.vue';
import { type ParameterContract } from '@/interfaces/ui';
import { ParameterType } from '@/interfaces/ui/key-value-parameters';
import { rowsByFormData } from '@/utils/request/formDataRows';
import { ref, watch } from 'vue';

/*
 * Types & Interfaces.
 */

export interface RequestBodyFormDataProps {}

/*
 * Component Setup.
 */

defineProps<RequestBodyFormDataProps>();

const model = defineModel<FormData | null>({
    default: () => null,
});

/*
 * State.
 */

// The rows keep per-row state that FormData cannot hold, such as the enabled flag.
// So the rows are a local view that is rebuilt only when the model comes from outside.
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
            // The filename is stored as a placeholder because file uploads are not verified.
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

// FormData is not made reactive, so its identity tells us the model came from this component.
let lastEmittedFormData: FormData | null = null;

const handlePayloadUpdate = (parameters: ParameterContract[]) => {
    payload.value = parameters;
    lastEmittedFormData = convertParametersArrayToFormData(parameters);
    rowsByFormData.set(lastEmittedFormData, parameters);
    model.value = lastEmittedFormData;
};

/*
 * Watchers.
 */

// Immediate so a remounted editor shows the stored body instead of an empty row that would overwrite it.
watch(
    model,
    (newModel: FormData | null) => {
        if (newModel === null || newModel === lastEmittedFormData) {
            return;
        }

        // Re-initialize the rows when the parent replaced the model, such as on endpoint change.
        payload.value =
            rowsByFormData.get(newModel) ?? convertFormDataToParametersArray(newModel);
    },
    { immediate: true },
);
</script>

<template>
    <KeyValueParametersBuilder
        :model-value="payload"
        :free-form-types="true"
        @update:parameters="handlePayloadUpdate"
    />
</template>
