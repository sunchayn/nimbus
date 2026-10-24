<script setup lang="ts">
/**
 * @component RequestBodyJson
 * @description JSON content editor for request bodies, with schema validation support.
 */
import CodeEditor from '@/components/domain/CodeEditor/CodeEditor.vue';
import { envVariablesCheck } from '@/components/domain/CodeEditor/extensions';
import { useEnvironmentVariablesStore } from '@/stores';
import type { JSONSchema7 } from 'json-schema';
import { computed } from 'vue';

/*
 * Types & Interfaces.
 */

export interface RequestBodyJsonProps {
    schema: JSONSchema7 | undefined;
}

/*
 * Component Setup.
 */

defineProps<RequestBodyJsonProps>();

const model = defineModel<string>({
    default: '',
});

const environmentVariablesStore = useEnvironmentVariablesStore();

const customExtensions = computed(() => {
    return [envVariablesCheck(key => environmentVariablesStore.check(key))];
});
</script>

<template>
    <CodeEditor
        v-model="model"
        language="json"
        :readonly="false"
        placeholder="Your JSON Payload"
        :validation-schema="schema"
        :custom-extensions="customExtensions"
    />
</template>
