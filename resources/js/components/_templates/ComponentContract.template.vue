<script setup lang="ts">
/**
 * @component ExampleComponent
 * @description Brief description of the component's purpose.
 *
 * Copy this template when creating new components to ensure consistent
 * Props/Emits contracts and TypeScript interfaces.
 */
import { computed } from 'vue';

/*
 * Types & Interfaces.
 */

/**
 * Props interface - defines all accepted properties.
 * Export this interface if consumers need to type-check component usage.
 */
export interface ExampleComponentProps {
    /** Nuxt UI color vocabulary, use the same names as the base wrappers */
    color?: 'neutral' | 'error';
    /** Disabled state - prevents user interaction */
    disabled?: boolean;
}

/**
 * Emits interface - defines all events with payload types.
 * Using type-based declaration for ESLint compliance.
 */
export interface ExampleComponentEmits {
    (event: 'submit'): void;
    (event: 'error', error: Error): void;
}

/*
 * Component Setup.
 */

const props = withDefaults(defineProps<ExampleComponentProps>(), {
    color: 'neutral',
    disabled: false,
});

const emit = defineEmits<ExampleComponentEmits>();

/** Two-way bound value, used with `v-model` by the parent. */
const model = defineModel<string>({ required: true });

/*
 * Computed & Methods.
 */

const dataAttributes = computed(() => ({
    'data-color': props.color,
    'data-disabled': props.disabled ? '' : undefined,
}));

function handleSubmit(): void {
    if (props.disabled) {
        return;
    }
    emit('submit');
}

function _handleError(error: Error): void {
    emit('error', error);
}
</script>

<template>
    <div v-bind="dataAttributes">
        <!-- Default slot for content -->
        <slot>{{ model }}</slot>

        <!-- Named slot example -->
        <slot name="actions">
            <button type="button" :disabled="disabled" @click="handleSubmit">
                Submit
            </button>
        </slot>
    </div>
</template>
