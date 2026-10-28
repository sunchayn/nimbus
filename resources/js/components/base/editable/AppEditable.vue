<script setup lang="ts">
/**
 * @component AppEditable
 * @description Inline text editing. Click the preview to edit and press Enter to submit.
 */
import { EditableInput, EditablePreview, EditableRoot } from 'reka-ui';
import { ref } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppEditableProps {
    placeholder?: string;
    submitMode?: 'blur' | 'enter' | 'both';
}

/*
 * Component Setup.
 */

withDefaults(defineProps<AppEditableProps>(), {
    placeholder: undefined,
    submitMode: 'both',
});

const modelValue = defineModel<string>({ default: '' });

const emit = defineEmits<{
    submit: [value: string | null | undefined];
}>();

// Nuxt UI has no equivalent, so this wraps Reka's editable parts directly.
const preview = ref<InstanceType<typeof EditablePreview> | null>(null);

/*
 * Computed & Methods.
 */

/**
 * Focusing the preview is how the editor enters edit mode.
 */
const focus = () => {
    const element = preview.value?.$el;

    if (element instanceof HTMLElement) {
        element.focus();
    }
};

defineExpose({ focus });
</script>

<template>
    <EditableRoot
        :model-value="modelValue"
        :placeholder="placeholder"
        :submit-mode="submitMode"
        class="flex w-full min-w-0 items-center"
        @update:model-value="(value: string | undefined) => (modelValue = value ?? '')"
        @submit="(value: string | null | undefined) => emit('submit', value)"
    >
        <EditablePreview
            ref="preview"
            class="hover:bg-elevated -ml-tight px-tight cursor-text rounded-sm text-sm font-medium transition-colors"
        >
            <slot>{{ modelValue }}</slot>
        </EditablePreview>
        <EditableInput class="bg-elevated w-full border-none p-0 text-sm font-medium" />
    </EditableRoot>
</template>
