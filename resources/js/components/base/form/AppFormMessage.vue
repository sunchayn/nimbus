<script setup lang="ts">
/**
 * @component AppFormMessage
 * @description Displays validation error messages for a form field.
 */
import { ErrorMessage } from 'vee-validate';
import { toValue } from 'vue';
import { useFormField } from './useFormField';

/*
 * Types & Interfaces.
 */

export interface AppFormMessageProps {}

/*
 * Component Setup.
 */

const { name, formMessageId } = useFormField();
</script>

<template>
    <ErrorMessage
        :id="formMessageId"
        as="p"
        :name="toValue(name)"
        class="text-[0.8rem] font-medium text-red-500 dark:text-red-900"
    />
</template>
