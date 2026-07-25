<script setup lang="ts">
/**
 * @component AppSwitch
 * @description A toggle switch component using reka-ui primitives.
 */
import { cn } from '@/utils/ui';
import {
    SwitchRoot,
    type SwitchRootEmits,
    type SwitchRootProps,
    SwitchThumb,
    useForwardPropsEmits,
} from 'reka-ui';
import { computed, type HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppSwitchProps extends SwitchRootProps {
    class?: HTMLAttributes['class'];
    variant?: 'default' | 'compact';
}

/*
 * Component Setup.
 */

const props = withDefaults(defineProps<AppSwitchProps>(), {
    variant: 'compact',
    class: undefined,
});
const emits = defineEmits<SwitchRootEmits>();

/*
 * Computed & Methods.
 */

const delegatedProps = computed(() => {
    const { class: _, variant: __, ...delegated } = props;

    return delegated;
});

const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>

<template>
    <SwitchRoot
        v-bind="forwarded"
        :class="
            cn(
                props.variant === 'default' ? 'h-5 w-9' : 'h-4 w-8',
                'peer inline-flex shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent shadow-sm transition-colors focus-visible:ring-2 focus-visible:ring-zinc-950 focus-visible:ring-offset-2 focus-visible:ring-offset-white focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 data-[state=checked]:bg-zinc-900 data-[state=unchecked]:bg-zinc-200 dark:focus-visible:ring-zinc-300 dark:focus-visible:ring-offset-zinc-950 dark:data-[state=checked]:bg-zinc-50 dark:data-[state=unchecked]:bg-zinc-700',
                props.class,
            )
        "
    >
        <SwitchThumb
            :class="
                cn(
                    props.variant === 'default' ? 'size-4' : 'size-3',
                    'pointer-events-none block rounded-full bg-white shadow-lg ring-0 transition-transform data-[state=checked]:translate-x-4 data-[state=unchecked]:translate-x-0 dark:bg-zinc-950',
                )
            "
        >
            <slot name="thumb" />
        </SwitchThumb>
    </SwitchRoot>
</template>
