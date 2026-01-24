<script setup lang="ts">
/**
 * @component CodeEditor
 * @description A rich code editor wrapper around CodeMirror with support for JSON and plain text.
 */
import {
    fallbackExtensions,
    jsonExtensions,
} from '@/components/domain/CodeEditor/extensions';
import type { JSONSchema7 } from 'json-schema';
import { type PrimitiveProps } from 'reka-ui';
import { computed, type HTMLAttributes } from 'vue';
import { Codemirror } from 'vue-codemirror';

/*
 * Types & Interfaces.
 */

export interface AppCodeEditorProps extends PrimitiveProps {
    class?: HTMLAttributes['class'];
    placeholder?: string;
    language: 'json' | 'plain';
    readonly?: boolean;
    disabled?: boolean;
    validationSchema?: JSONSchema7;
}

/*
 * Component Setup.
 */

const props = withDefaults(defineProps<AppCodeEditorProps>(), {
    readonly: false,
    placeholder: 'Payload...',
    disabled: false,
    class: '',
    validationSchema: undefined,
});

const model = defineModel<string>({
    default: () => '',
});

/*
 * Computed & Methods.
 */

const extensions = computed(() => {
    if (props.language === 'json') {
        return jsonExtensions(props.readonly, props.validationSchema);
    }

    return fallbackExtensions(props.readonly);
});

const updateModel = (value: string) => {
    model.value = value;
};
</script>

<template>
    <Codemirror
        :placeholder="placeholder"
        :style="{ height: '100%' }"
        :extensions="extensions"
        :indent-with-tab="true"
        :tab-size="4"
        :model-value="model"
        :disabled="disabled"
        @change="updateModel"
    />
</template>
