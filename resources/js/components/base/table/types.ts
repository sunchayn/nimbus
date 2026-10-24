import type { TableData, TableProps } from '@nuxt/ui/components/Table.vue';

export type TableState = NonNullable<TableProps['state']>;

export interface AppTableProps<D extends TableData = TableData> extends TableProps<D> {
    globalFilter?: string;
    columnFilters?: TableState['columnFilters'];
    columnOrder?: TableState['columnOrder'];
    columnVisibility?: TableState['columnVisibility'];
    columnPinning?: TableState['columnPinning'];
    columnSizing?: TableState['columnSizing'];
    columnSizingInfo?: TableState['columnSizingInfo'];
    rowSelection?: TableState['rowSelection'];
    rowPinning?: TableState['rowPinning'];
    sorting?: TableState['sorting'];
    grouping?: TableState['grouping'];
    expanded?: TableState['expanded'];
    pagination?: TableState['pagination'];
}
