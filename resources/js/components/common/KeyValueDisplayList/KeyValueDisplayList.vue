<script
    setup
    lang="ts"
    generic="T extends { key: string; value: string | number | boolean }"
>
/**
 * @component KeyValueDisplayList
 * @description A list-based table for displaying key-value pairs with copy support.
 */
import { AppTable, AppTableBody } from '@/components/base/table';
import KeyValueDisplayListItem from '@/components/common/KeyValueDisplayList/KeyValueDisplayListItem.vue';

/*
 * Types & Interfaces.
 */

export interface AppKeyValueDisplayListProps<T> {
    items: Array<T>;
}

/*
 * Component Setup.
 */

defineProps<AppKeyValueDisplayListProps<T>>();
</script>

<template>
    <AppTable class="table-fixed text-xs">
        <AppTableBody class="max-w-full">
            <KeyValueDisplayListItem
                v-for="(displayItem, index) in items"
                :key="index"
                :item="displayItem"
            >
                <template #value="{ item }">
                    <slot name="value" :item="item" />
                </template>
            </KeyValueDisplayListItem>
        </AppTableBody>
    </AppTable>
</template>
