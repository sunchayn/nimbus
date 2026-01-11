<script setup lang="ts">
import CodeEditor from '@/components/domain/CodeEditor/CodeEditor.vue';
import type { JSONSchema7 } from 'json-schema';

defineProps<RequestBodyJsonProps>();

const model = defineModel<string>({
    default: () => '',
});

interface RequestBodyJsonProps {
    schema: JSONSchema7 | undefined;
}
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
