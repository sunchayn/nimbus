<script setup lang="ts">
import {
    fallbackExtensions,
    jsonExtensions,
} from '@/components/domain/CodeEditor/extensions';
import type { JSONSchema7 } from 'json-schema';
import { PrimitiveProps } from 'reka-ui';
import { computed, HTMLAttributes } from 'vue';
import { Codemirror } from 'vue-codemirror';

interface CodeEditorProps extends PrimitiveProps {
    class?: HTMLAttributes['class'];
    placeholder?: string;
    language: 'json' | 'plain';
    readonly?: boolean;
    disabled?: boolean;
    validationSchema?: JSONSchema7;
}

const props = withDefaults(defineProps<CodeEditorProps>(), {
    readonly: false,
    placeholder: 'Payload...',
    disabled: false,
    class: '',
    validationSchema: undefined,
});

const model = defineModel<string>({
    default: () => '',
});

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
