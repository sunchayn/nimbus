<script setup lang="ts">
/**
 * @component AppCommandInput
 * @description The search input field for filtering command items.
 */
import { cn } from '@/utils/ui';
import { reactiveOmit } from '@vueuse/core';
import { Search } from 'lucide-vue-next';
import type { ListboxFilterProps } from 'reka-ui';
import { ListboxFilter, useForwardProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { useCommand } from '.';

defineOptions({
    inheritAttrs: false,
});

/*
 * Types & Interfaces.
 */

export interface AppCommandInputProps extends ListboxFilterProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppCommandInputProps>();

const delegatedProps = reactiveOmit(props, 'class');

const forwardedProps = useForwardProps(delegatedProps);

/*
 * Computed & Methods.
 */

const { filterState } = useCommand();
</script>

<template>
    <div
        data-slot="command-input-wrapper"
        class="flex h-12 items-center gap-2 border-b px-3"
    >
        <Search class="size-4 shrink-0 opacity-50" />
        <ListboxFilter
            v-bind="{ ...forwardedProps, ...$attrs }"
            v-model="filterState.search"
            data-slot="command-input"
            auto-focus
            :class="
                cn(
                    'placeholder:text-muted-foreground flex h-12 w-full rounded-md bg-transparent py-3 text-sm outline-hidden disabled:cursor-not-allowed disabled:opacity-50',
                    props.class,
                )
            "
        />
    </div>
</template>
