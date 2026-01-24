<script setup lang="ts">
/**
 * @component AppFormControl
 * @description Provides accessibility attributes to form control elements.
 */
import { Slot } from 'reka-ui';
import { useFormField } from './useFormField';

/*
 * Types & Interfaces.
 */

export interface AppFormControlProps {}

/*
 * Component Setup.
 */

const { error, formItemId, formDescriptionId, formMessageId } = useFormField();
</script>

<template>
    <Slot
        :id="formItemId"
        :aria-describedby="
            !error ? `${formDescriptionId}` : `${formDescriptionId} ${formMessageId}`
        "
        :aria-invalid="!!error"
    >
        <slot />
    </Slot>
</template>
