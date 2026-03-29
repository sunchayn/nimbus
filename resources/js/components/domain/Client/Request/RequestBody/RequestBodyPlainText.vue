<script setup lang="ts">
/**
 * @component RequestBodyPlainText
 * @description Plain text editor for request bodies.
 */
import CodeEditor from '@/components/domain/CodeEditor/CodeEditor.vue';
import { envVariablesCheck } from '@/components/domain/CodeEditor/extensions';
import { useEnvironmentVariablesStore } from '@/stores';
import { computed } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppRequestBodyPlainTextProps {}

/*
 * Component Setup.
 */

defineProps<AppRequestBodyPlainTextProps>();

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
        language="plain"
        :readonly="false"
        placeholder="Your Plain Text Content"
        :custom-extensions="customExtensions"
    />
</template>
