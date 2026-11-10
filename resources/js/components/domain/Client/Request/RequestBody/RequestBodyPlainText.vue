<script setup lang="ts">
/**
 * @component RequestBodyPlainText
 * @description Plain text editor for request bodies, also used for HTML and XML.
 */
import CodeEditor from '@/components/domain/CodeEditor/CodeEditor.vue';
import { envVariablesCheck } from '@/components/domain/CodeEditor/extensions';
import { useEnvironmentVariablesStore } from '@/stores';
import { computed } from 'vue';

/*
 * Types & Interfaces.
 */

export interface RequestBodyPlainTextProps {
    placeholder?: string;
    wrapKey?: string;
}

/*
 * Component Setup.
 */

withDefaults(defineProps<RequestBodyPlainTextProps>(), {
    placeholder: 'Your Plain Text Content',
    wrapKey: 'request-plain-text',
});

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
        wrappable
        :wrap-key="wrapKey"
        :readonly="false"
        :placeholder="placeholder"
        :custom-extensions="customExtensions"
    />
</template>
