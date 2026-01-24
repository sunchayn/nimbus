<script setup lang="ts">
/**
 * @component RequestBodyJson
 * @description JSON content editor for request bodies, with schema validation support.
 */
import CodeEditor from '@/components/domain/CodeEditor/CodeEditor.vue';
import type { JSONSchema7 } from 'json-schema';

/*
 * Types & Interfaces.
 */

export interface AppRequestBodyJsonProps {
    schema: JSONSchema7 | undefined;
}

/*
 * Component Setup.
 */

defineProps<AppRequestBodyJsonProps>();

const model = defineModel<string>({
    default: () => '',
});
</script>

<template>
    <CodeEditor
        v-model="model"
        language="json"
        :readonly="false"
        placeholder="Your JSON Payload"
        :validation-schema="schema"
    />
</template>
