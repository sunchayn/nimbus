<script setup lang="ts">
/**
 * @component ExampleComponent
 * @description Brief description of the component's purpose.
 *
 * USAGE:
 * Copy this template when creating new components to ensure consistent
 * Props/Emits contracts and TypeScript interfaces.
 */
import { computed } from "vue";

/*
 * Types & Interfaces.
 */

/**
 * Props interface - defines all accepted properties.
 * Export this interface if consumers need to type-check component usage.
 */
export interface ExampleComponentProps {
    /** Primary data to display (required) */
    modelValue: string;
    /** Visual variant of the component */
    variant?: "default" | "primary" | "destructive";
    /** Disabled state - prevents user interaction */
    disabled?: boolean;
}

/**
 * Emits interface - defines all events with payload types.
 * Using type-based declaration for ESLint compliance.
 */
export interface ExampleComponentEmits {
    (event: "update:modelValue", value: string): void;
    (event: "submit"): void;
    (event: "error", error: Error): void;
}

/*
 * Component Setup.
 */

const props = withDefaults(defineProps<ExampleComponentProps>(), {
    variant: "default",
    disabled: false,
});

const emit = defineEmits<ExampleComponentEmits>();

/*
 * Computed & Methods.
 */

const computedClasses = computed(() => ({
    "is-disabled": props.disabled,
    [`variant-${props.variant}`]: true,
}));

function handleSubmit(): void {
    if (props.disabled) {
        return;
    }
    emit("submit");
}

function handleError(error: Error): void {
    emit("error", error);
}
</script>

<template>
    <div :class="computedClasses">
        <!-- Default slot for content -->
        <slot />

        <!-- Named slot example -->
        <slot name="actions">
            <button type="button" :disabled="disabled" @click="handleSubmit">
                Submit
            </button>
        </slot>
    </div>
</template>
