<script setup lang="ts">
/**
 * @component CodeEditor
 * @description A rich code editor wrapper around CodeMirror with support for JSON and plain text.
 */
import {
    fallbackExtensions,
    jsonExtensions,
} from '@/components/domain/CodeEditor/extensions';
import type { Extension } from '@codemirror/state';
import type { JSONSchema7 } from 'json-schema';
import { computed, type HTMLAttributes } from 'vue';
import { Codemirror } from 'vue-codemirror';

/*
 * Types & Interfaces.
 */

export interface CodeEditorProps {
    class?: HTMLAttributes['class'];
    placeholder?: string;
    language: 'json' | 'plain';
    readonly?: boolean;
    disabled?: boolean;
    validationSchema?: JSONSchema7;
    autoHeight?: boolean;
    customExtensions?: Extension[];
}

/*
 * Component Setup.
 */

const props = withDefaults(defineProps<CodeEditorProps>(), {
    readonly: false,
    placeholder: 'Payload...',
    disabled: false,
    class: '',
    validationSchema: undefined,
    autoHeight: false,
    customExtensions: () => [],
});

const model = defineModel<string>({
    default: () => '',
});

/*
 * Computed & Methods.
 */

const extensions = computed(() => {
    if (props.language === 'json') {
        return [
            ...jsonExtensions(props.readonly, props.validationSchema),
            ...props.customExtensions,
        ];
    }

    return [...fallbackExtensions(props.readonly), ...props.customExtensions];
});

const updateModel = (value: string) => {
    model.value = value;
};
</script>

<template>
    <Codemirror
        :placeholder="placeholder"
        :style="{ height: autoHeight ? 'auto' : '100%', minHeight: '100%' }"
        :extensions="extensions"
        :indent-with-tab="true"
        :tab-size="4"
        :model-value="model"
        :disabled="disabled"
        @change="updateModel"
    />
</template>
