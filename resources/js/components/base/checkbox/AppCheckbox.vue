<script setup lang="ts">
/**
 * @component AppCheckbox
 * @description A boolean input component for toggling states.
 */
import { cn } from '@/utils/ui';
import { Check } from 'lucide-vue-next';
import type { CheckboxRootEmits, CheckboxRootProps } from 'reka-ui';
import { CheckboxIndicator, CheckboxRoot, useForwardPropsEmits } from 'reka-ui';
import { computed, type HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppCheckboxProps extends CheckboxRootProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppCheckboxProps>();
const emits = defineEmits<CheckboxRootEmits>();

const delegatedProps = computed(() => {
    const { class: _, ...delegated } = props;

    return delegated;
});

const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>

<template>
    <CheckboxRoot
        v-bind="forwarded"
        :class="
            cn(
                'peer border-input focus-visible:ring-ring data-[state=checked]:bg-primary data-[state=checked]:text-primary-foreground h-4 w-4 shrink-0 rounded-sm border shadow focus-visible:ring-1 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50',
                props.class,
            )
        "
    >
        <CheckboxIndicator
            class="flex h-full w-full items-center justify-center text-current"
        >
            <slot>
                <Check class="h-4 w-4" />
            </slot>
        </CheckboxIndicator>
    </CheckboxRoot>
</template>
