<script setup lang="ts">
/**
 * @component InsightQueryRuns
 * @description A closed list inside a query group that shows the other queries of the group with their own bindings and time.
 * The first query is the one the card already shows, so it is not repeated here.
 */
import { AppCollapsible } from '@/components/base/collapsible';
import AppIcon from '@/components/base/icons/AppIcon.vue';
import type { InsightQuery } from '@/interfaces/http';
import { fillBindings, formatMilliseconds, type QueryGroup } from '@/utils/insight';
import { computed, ref } from 'vue';
import InsightSql from './InsightSql.vue';

const props = defineProps<{
    group: QueryGroup;
    queries: InsightQuery[];
}>();

/** The runs that are listed, so a long N+1 does not fill the card. */
const RUN_LIMIT = 24;

const open = ref(false);

const others = computed(() => props.group.indices.slice(1));

const runs = computed(() => others.value.slice(0, RUN_LIMIT));
</script>

<template>
    <AppCollapsible v-model:open="open" data-testid="insight-query-runs">
        <button
            type="button"
            class="group/runs gap-base hover:bg-elevated/50 -mx-tight px-tight h-control-xs flex w-full items-center rounded-sm text-left text-xs"
            data-testid="insight-query-runs-trigger"
        >
            <AppIcon
                name="i-hugeicons-arrow-right-01"
                class="text-muted size-icon-xs duration-base shrink-0 transition-transform group-data-[state=open]/runs:rotate-90 motion-reduce:transition-none"
            />
            <span class="text-default">{{ others.length }} more queries</span>
            <span class="text-muted">
                Average {{ formatMilliseconds(group.avgMs) }}, slowest
                {{ formatMilliseconds(group.maxMs) }}
            </span>
        </button>

        <template #content>
            <ul
                class="gap-base pt-base border-accented ms-tight ps-loose before:bg-accented flex flex-col border-s"
            >
                <li
                    v-for="run in runs"
                    :key="run"
                    class="before:bg-accented before:w-base before:h-line before:-start-loose relative before:absolute before:top-3"
                    data-testid="insight-query-run-row"
                >
                    <InsightSql
                        :sql="fillBindings(queries[run].sql, queries[run].bindings)"
                    />
                </li>
            </ul>
            <p
                v-if="others.length > RUN_LIMIT"
                class="text-muted pt-base text-xs"
                data-testid="insight-query-runs-more"
            >
                and {{ others.length - RUN_LIMIT }} more
            </p>
        </template>
    </AppCollapsible>
</template>
