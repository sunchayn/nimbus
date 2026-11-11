<script setup lang="ts">
/**
 * @component InsightSql
 * @description Shows a SQL statement with colored keywords, strings, numbers, and comments, and a button to copy it.
 */
import CopyButton from '@/components/common/CopyButton.vue';
import { tokenizeSql, type SqlTokenType } from '@/utils/insight';
import { useClipboard } from '@vueuse/core';
import { computed } from 'vue';

const props = defineProps<{ sql: string }>();

const { copy, copied } = useClipboard();

const tokens = computed(() => tokenizeSql(props.sql));

const tokenClasses: Record<SqlTokenType, string> = {
    keyword: 'text-highlighted font-semibold',
    string: 'text-muted',
    number: 'text-muted',
    comment: 'text-dimmed italic',
    identifier: 'text-default',
    text: '',
};
</script>

<template>
    <div class="group/sql relative">
        <code
            class="bg-muted p-base block rounded font-mono text-xs wrap-break-word whitespace-pre-wrap"
            data-testid="insight-sql"
        >
            <span
                v-for="(token, index) in tokens"
                :key="index"
                :class="tokenClasses[token.type]"
                :data-token="token.type"
            >
                {{ token.text }}
            </span>
        </code>
        <CopyButton
            class="bg-elevated hover:bg-accented top-tight right-tight duration-fast absolute opacity-0 group-focus-within/sql:opacity-100 group-hover/sql:opacity-100 focus-visible:opacity-100 motion-safe:transition-opacity"
            :on-click="() => copy(sql)"
            :copied="copied"
            data-testid="insight-sql-copy"
        />
    </div>
</template>
