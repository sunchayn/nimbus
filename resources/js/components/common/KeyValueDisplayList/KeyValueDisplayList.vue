<script
    setup
    lang="ts"
    generic="T extends { key: string; value: string | number | boolean }"
>
/**
 * @component KeyValueDisplayList
 * @description A table for displaying key-value pairs with copy support.
 */
import { AppTable } from '@/components/base/table';
import CopyButton from '@/components/common/CopyButton.vue';
import { useClipboard } from '@vueuse/core';
import { shallowRef } from 'vue';

/*
 * Types & Interfaces.
 */

export interface KeyValueDisplayListProps<T> {
    items: Array<T>;
}

/*
 * Component Setup.
 */

defineProps<KeyValueDisplayListProps<T>>();

const { copy, copied } = useClipboard();

/*
 * State.
 */

const copiedRowIndex = shallowRef<number | null>(null);

// The collapsed header row sets the fixed column widths without taking height.
// Only the value and copy cells draw the row line, so the key column reads as a label.
const rowLine = 'border-b-line border-default';

const columns = [
    {
        accessorKey: 'key',
        header: 'Key',
        meta: { class: { th: 'w-1/4', td: 'font-medium' } },
    },
    { accessorKey: 'value', header: 'Value', meta: { class: { td: rowLine } } },
    { id: 'copy', header: 'Copy', meta: { class: { th: 'w-10', td: rowLine } } },
];

/*
 * Computed & Methods.
 */

const copyValue = (index: number, value: unknown) => {
    copiedRowIndex.value = index;
    copy(String(value));
};
</script>

<template>
    <AppTable
        :data="items"
        :columns="columns"
        :ui="{
            thead: 'collapse',
            td: 'px-panel py-base',
        }"
    >
        <template #value-cell="{ row }">
            <slot name="value" :item="row.original">
                {{ row.original.value }}
            </slot>
        </template>
        <template #copy-cell="{ row }">
            <CopyButton
                :on-click="() => copyValue(row.index, row.original.value)"
                :copied="copied && copiedRowIndex === row.index"
            />
        </template>
    </AppTable>
</template>
