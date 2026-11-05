<script setup lang="ts">
/**
 * @component CodeEditor
 * @description A rich code editor wrapper around CodeMirror with support for JSON and plain text.
 */
import {
    fallbackExtensions,
    jsonExtensions,
} from '@/components/domain/CodeEditor/extensions';
import { singletonPersistenceKey } from '@/utils/stores/uniquePersistenceKey';
import type { Extension } from '@codemirror/state';
import { EditorView } from '@codemirror/view';
import UCheckbox from '@nuxt/ui/components/Checkbox.vue';
import { useStorage } from '@vueuse/core';
import type { JSONSchema7 } from 'json-schema';
import { computed, type HTMLAttributes, ref } from 'vue';
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
    wrappable?: boolean;
    wrapKey?: string;
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
    wrappable: false,
    wrapKey: 'default',
});

const wrap = useStorage(
    singletonPersistenceKey(`code-editor-wrap-${props.wrapKey}`),
    false,
);

const overflowing = ref(false);

const trackOverflow = EditorView.updateListener.of(update => {
    if (!update.docChanged && !update.geometryChanged && !update.viewportChanged) {
        return;
    }

    update.view.requestMeasure({
        read: view => view.scrollDOM.scrollWidth > view.scrollDOM.clientWidth,
        write: value => {
            overflowing.value = value;
        },
    });
});

const model = defineModel<string>({
    default: () => '',
});

/*
 * Computed & Methods.
 */

const extensions = computed(() => {
    const base =
        props.language === 'json'
            ? jsonExtensions(props.readonly, props.validationSchema)
            : fallbackExtensions(props.readonly);

    const wrapping = props.wrappable
        ? [trackOverflow, ...(wrap.value ? [EditorView.lineWrapping] : [])]
        : [];

    return [...base, ...wrapping, ...props.customExtensions];
});

const updateModel = (value: string) => {
    model.value = value;
};
</script>

<template>
    <div class="group relative h-full min-h-0">
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
        <label
            v-if="wrappable && (wrap || overflowing)"
            class="top-tight right-tight z-sticky bg-default/40 px-base py-tight hover:bg-default has-focus-visible:bg-default absolute flex cursor-pointer items-center rounded-md border opacity-0 shadow-xs backdrop-blur-sm transition group-hover:opacity-60 hover:opacity-100 has-focus-visible:opacity-100"
        >
            <UCheckbox v-model="wrap" size="xs" data-testid="code-editor-wrap-toggle" />
            <span class="ml-base text-xs font-medium">Soft-Wrap</span>
        </label>
    </div>
</template>
