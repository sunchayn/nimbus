<script setup lang="ts">
import KeyValueParametersBuilder from '@/components/common/KeyValueParameters/KeyValueParameters.vue';
import { ParameterContract } from '@/interfaces/ui';
import { ParameterType } from '@/interfaces/ui/key-value-parameters';
import { nextTick, ref, watch } from 'vue';

/*
 * Model.
 */

const model = defineModel<FormData | null>({
    default: () => null,
});

const emit = defineEmits(['update:modelValue']);

/*
 * State.
 */

// A guard flag to prevent endless syncing looping between the component and parent as it will mutate its dependency.
const isPropagatingChangesToParent = ref(false);

const payload = ref<ParameterContract[]>([]);

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

const handlePayloadUpdate = (parameters: ParameterContract[]) => {
    isPropagatingChangesToParent.value = true;
    payload.value = parameters;
    emit('update:modelValue', convertParametersArrayToFormData(parameters));
};

/*
 * Actions.
 */

function convertParametersArrayToFormData(parameters: ParameterContract[]): FormData {
    const formData = new FormData();

    for (const parameter of parameters) {
        if (parameter.value === null) {
            formData.set(parameter.key, '');

            continue;
        }

        if ((parameter.value as unknown) instanceof Blob) {
            formData.set(parameter.key, parameter.value);

            continue;
        }

        formData.set(parameter.key, String(parameter.value));
    }

    return formData;
}

function convertFormDataToParametersArray(form: FormData): ParameterContract[] {
    const parameters: ParameterContract[] = [];

    form.forEach((value: FormDataEntryValue, key: string) => {
        if (value instanceof File) {
            // For files, we'll store the filename as a placeholder
            // Note: File uploads are not properly tested or verified.
            // TODO [Feature] Properly support file uploads.
            parameters.push({
                type: ParameterType.File,
                key: key,
                value: value.name,
                enabled: true,
            });

            return;
        }

        parameters.push({
            type: ParameterType.Text,
            key: key,
            value: value,
            enabled: true,
        });
    });

    return parameters;
}
</script>

<template>
    <KeyValueParametersBuilder
        :model-value="payload"
        :free-form-types="true"
        @update:parameters="handlePayloadUpdate"
    />
</template>
