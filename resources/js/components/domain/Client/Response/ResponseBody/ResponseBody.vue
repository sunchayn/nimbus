<script setup lang="ts">
/**
 * @component ResponseBody
 * @description Renders the response body content (usually JSON) in a read-only code editor.
 */
import CodeEditor from '@/components/domain/CodeEditor/CodeEditor.vue';

/*
 * Types & Interfaces.
 */

export interface AppResponseBodyProps {
    content: string;
}

/*
 * Component Setup.
 */

defineProps<AppResponseBodyProps>();
</script>

<template>
    <CodeEditor
        language="json"
        :readonly="true"
        :placeholder="content === '' ? 'Empty' : 'Response JSON Payload'"
        :model-value="content"
        :disabled="content === ''"
        :auto-height="false"
    />
</template>
