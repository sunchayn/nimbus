<script setup lang="ts">
/**
 * @component RequestBodyPlainText
 * @description Plain text editor for request bodies.
 */
import CodeEditor from '@/components/domain/CodeEditor/CodeEditor.vue';

/*
 * Types & Interfaces.
 */

export interface AppRequestBodyPlainTextProps {}

/*
 * Component Setup.
 */

defineProps<AppRequestBodyPlainTextProps>();
</script>

<template>
    <CodeEditor
        language="plain"
        :readonly="false"
        placeholder="Your Plain Text Content"
    />
</template>
