<script setup lang="ts">
/**
 * @component GeneratorInput
 * @description Text input that opens the value generator when Shift is pressed twice.
 */
import { AppInput, type AppInputProps } from '@/components/base/input';
import { useForwardedProps } from '@/components/base/useForwardedProps';
import { useDoubleShiftGenerator } from '@/composables/ui/useDoubleShiftGenerator';
import { useTemplateRef } from 'vue';

/*
 * Types & Interfaces.
 */

export interface GeneratorInputProps extends AppInputProps {}

/*
 * Component Setup.
 */

const props = defineProps<GeneratorInputProps>();

const emit = defineEmits<{
    'update:modelValue': [value: string | number];
}>();

const forwarded = useForwardedProps(props);
const input = useTemplateRef<InstanceType<typeof AppInput>>('input');

const handleKeydown = useDoubleShiftGenerator(() => input.value?.inputRef);

defineExpose({
    get inputRef() {
        return input.value?.inputRef ?? null;
    },
});
</script>

<template>
    <AppInput
        ref="input"
        v-bind="forwarded"
        @update:model-value="value => emit('update:modelValue', value)"
        @keydown="handleKeydown"
    >
        <template v-if="$slots.leading" #leading="slotProps">
            <slot name="leading" v-bind="slotProps" />
        </template>
        <template v-if="$slots.trailing" #trailing="slotProps">
            <slot name="trailing" v-bind="slotProps" />
        </template>
    </AppInput>
</template>
