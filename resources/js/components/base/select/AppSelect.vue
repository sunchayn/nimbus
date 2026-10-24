<script
    setup
    lang="ts"
    generic="
        T extends ArrayOrNested<SelectItem>,
        VK extends GetItemKeys<T> = 'value',
        M extends boolean = false
    "
>
/**
 * @component AppSelect
 * @description Select built on Nuxt UI. Pass options through `items` and bind with `v-model`.
 */
import { useForwardedProps } from '@/components/base/useForwardedProps';
import type {
    SelectItem,
    SelectProps,
    SelectSlots,
} from '@nuxt/ui/components/Select.vue';
import USelect from '@nuxt/ui/components/Select.vue';
import type { ModelModifiers } from '@nuxt/ui/runtime/types/input.js';
import type {
    ArrayOrNested,
    GetItemKeys,
    GetModelValue,
    NestedItem,
} from '@nuxt/ui/runtime/types/utils.js';
import type { ComputedRef } from 'vue';

/*
 * Types & Interfaces.
 */

type ExcludeItem = { type: 'label' | 'separator' };

/*
 * Component Setup.
 */

const props = defineProps<SelectProps<T, VK, M>>();

const emit = defineEmits<{
    'update:modelValue': [value: GetModelValue<T, VK, M, ExcludeItem>];
    'update:open': [value: boolean];
    change: [event: Event];
    blur: [event: FocusEvent];
    focus: [event: FocusEvent];
}>();

defineSlots<SelectSlots<T, VK, M, Omit<ModelModifiers, 'lazy'>, NestedItem<T>>>();

// The forwarded props are re-checked by Nuxt UI generics that cannot be matched from inside the wrapper.
const forwarded = useForwardedProps(props) as unknown as ComputedRef<
    Record<string, unknown>
>;
</script>

<template>
    <USelect
        v-bind="forwarded"
        @update:model-value="emit('update:modelValue', $event as never)"
        @update:open="value => emit('update:open', value)"
        @change="event => emit('change', event)"
        @blur="event => emit('blur', event)"
        @focus="event => emit('focus', event)"
    >
        <template v-for="(_, name) in $slots" :key="name" #[name]="slotProps">
            <slot :name="name as never" v-bind="slotProps ?? {}" />
        </template>
    </USelect>
</template>
