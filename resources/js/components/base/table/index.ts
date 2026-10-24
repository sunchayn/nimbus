import type { TableColumn, TableData } from '@nuxt/ui/components/Table.vue';

export { default as AppTable } from './AppTable.vue';
export type { AppTableProps } from './types';

export type AppTableColumn<T extends TableData> = TableColumn<T>;
