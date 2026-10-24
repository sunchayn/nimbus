<script setup lang="ts" generic="T extends TableData">
/**
 * @component AppTable
 * @description Data table built on Nuxt UI. Pass `data` and `columns` and use `<column>-cell` slots for custom cells.
 */
import type { AppTableProps, TableState } from '@/components/base/table/types';
import { useForwardedProps } from '@/components/base/useForwardedProps';
import type { TableData, TableSlots } from '@nuxt/ui/components/Table.vue';
import UTable from '@nuxt/ui/components/Table.vue';

/*
 * Component Setup.
 */

const props = defineProps<AppTableProps<T>>();

const emit = defineEmits<{
    'update:globalFilter': [value: string | undefined];
    'update:columnFilters': [value: TableState['columnFilters'] | undefined];
    'update:columnOrder': [value: TableState['columnOrder'] | undefined];
    'update:columnVisibility': [value: TableState['columnVisibility'] | undefined];
    'update:columnPinning': [value: TableState['columnPinning'] | undefined];
    'update:columnSizing': [value: TableState['columnSizing'] | undefined];
    'update:columnSizingInfo': [value: TableState['columnSizingInfo'] | undefined];
    'update:rowSelection': [value: TableState['rowSelection'] | undefined];
    'update:rowPinning': [value: TableState['rowPinning'] | undefined];
    'update:sorting': [value: TableState['sorting'] | undefined];
    'update:grouping': [value: TableState['grouping'] | undefined];
    'update:expanded': [value: TableState['expanded'] | undefined];
    'update:pagination': [value: TableState['pagination'] | undefined];
}>();

defineSlots<TableSlots<T>>();

const forwarded = useForwardedProps(props);
</script>

<template>
    <UTable
        v-bind="forwarded"
        @update:global-filter="value => emit('update:globalFilter', value)"
        @update:column-filters="value => emit('update:columnFilters', value)"
        @update:column-order="value => emit('update:columnOrder', value)"
        @update:column-visibility="value => emit('update:columnVisibility', value)"
        @update:column-pinning="value => emit('update:columnPinning', value)"
        @update:column-sizing="value => emit('update:columnSizing', value)"
        @update:column-sizing-info="value => emit('update:columnSizingInfo', value)"
        @update:row-selection="value => emit('update:rowSelection', value)"
        @update:row-pinning="value => emit('update:rowPinning', value)"
        @update:sorting="value => emit('update:sorting', value)"
        @update:grouping="value => emit('update:grouping', value)"
        @update:expanded="value => emit('update:expanded', value)"
        @update:pagination="value => emit('update:pagination', value)"
    >
        <template v-for="(_, name) in $slots" :key="name" #[name]="slotProps">
            <slot :name="name as never" v-bind="slotProps ?? {}" />
        </template>
    </UTable>
</template>
