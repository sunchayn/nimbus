<script setup lang="ts">
/**
 * @component InsightQueries
 * @description Lists the queries grouped by shape in cards, flagging N+1 groups and slow queries.
 * A query selected on the timeline is opened, scrolled into view, and highlighted.
 */
import { AppBadge } from '@/components/base/badge';
import AppIcon from '@/components/base/icons/AppIcon.vue';
import { AppSwitchButton } from '@/components/base/switch';
import InsightCard from '@/components/domain/Client/Response/ResponseInsight/Shared/InsightCard.vue';
import InsightFlag from '@/components/domain/Client/Response/ResponseInsight/Shared/InsightFlag.vue';
import InsightSection from '@/components/domain/Client/Response/ResponseInsight/Shared/InsightSection.vue';
import { useJumpHighlight } from '@/composables/ui/useJumpHighlight';
import type { InsightQuery } from '@/interfaces/http';
import {
    flattenSql,
    formatMilliseconds,
    groupQueries,
    type QueryFocus,
    type QueryGroup,
} from '@/utils/insight';
import { computed, reactive, ref, watch } from 'vue';
import InsightQueryRuns from './InsightQueryRuns.vue';
import InsightSql from './InsightSql.vue';

const props = defineProps<{
    queries: InsightQuery[];
    dropped?: number;
    focus?: QueryFocus | null;
}>();

/** The first groups start open, and so do the ones that need attention. */
const OPEN_BY_DEFAULT = 3;

const groups = computed(() => groupQueries(props.queries));

const hasSeveralConnections = computed(
    () => new Set(props.queries.map(query => query.connection)).size > 1,
);

const summary = computed(() => {
    const total = props.queries.reduce((sum, query) => sum + query.time_ms, 0);
    const count = props.queries.length;
    const noun = count === 1 ? 'query' : 'queries';
    const grouped =
        groups.value.length === count
            ? ''
            : ` in ${groups.value.length} ${groups.value.length === 1 ? 'group' : 'groups'}`;

    return `${count} ${noun}${grouped}, ${formatMilliseconds(total)} total`;
});

const keyOf = (group: QueryGroup) => `${group.connection}\u0000${group.shape}`;

const filters = reactive({ nPlusOne: false, slow: false });

const nPlusOneCount = computed(
    () => groups.value.filter(group => group.isNPlusOne).length,
);
const slowCount = computed(() => groups.value.filter(group => group.isSlow).length);

// Active filters add up, so a group is shown when it matches any of them.
const visibleGroups = computed(() =>
    groups.value
        .map((group, index) => ({ group, index, key: keyOf(group) }))
        .filter(
            ({ group }) =>
                (!filters.nPlusOne && !filters.slow) ||
                (filters.nPlusOne && group.isNPlusOne) ||
                (filters.slow && group.isSlow),
        ),
);

const openOverrides = ref<Record<string, boolean>>({});

const isOpen = (group: QueryGroup, index: number) =>
    openOverrides.value[keyOf(group)] ??
    (index < OPEN_BY_DEFAULT || group.isNPlusOne || group.isSlow);

const setOpen = (group: QueryGroup, value: boolean) => {
    openOverrides.value[keyOf(group)] = value;
};

const { highlighted: highlightedKey, bind: bindCard, jump } = useJumpHighlight();

watch(
    () => props.focus,
    focus => {
        const target = focus
            ? groups.value.find(group => group.indices.includes(focus.index))
            : undefined;

        if (!target) {
            return;
        }

        const key = keyOf(target);

        // A filter may hide the group, and a hidden group cannot be shown.
        if (!visibleGroups.value.some(visible => visible.key === key)) {
            filters.nPlusOne = false;
            filters.slow = false;
        }

        openOverrides.value[key] = true;

        return jump(key);
    },
);
</script>

<template>
    <InsightSection
        name="Queries"
        :empty="queries.length ? undefined : 'No queries were run.'"
        :dropped="dropped"
    >
        <div
            v-if="queries.length"
            class="gap-base flex flex-wrap items-center justify-between"
        >
            <p class="text-muted px-hair text-xs" data-testid="insight-queries-summary">
                {{ summary }}
            </p>
            <div
                v-if="nPlusOneCount || slowCount"
                class="gap-base flex flex-wrap items-center"
                data-testid="insight-query-filters"
            >
                <AppSwitchButton
                    v-if="nPlusOneCount"
                    v-model="filters.nPlusOne"
                    :label="`N+1 (${nPlusOneCount})`"
                    data-testid="insight-filter-n-plus-one"
                />
                <AppSwitchButton
                    v-if="slowCount"
                    v-model="filters.slow"
                    :label="`Slow (${slowCount})`"
                    data-testid="insight-filter-slow"
                />
            </div>
        </div>

        <div
            v-for="{ group, index, key } in visibleGroups"
            :key="key"
            :ref="bindCard(key)"
        >
            <InsightCard
                collapsible
                :open="isOpen(group, index)"
                :highlighted="highlightedKey === key"
                data-testid="insight-query-group"
                @update:open="value => setOpen(group, value)"
            >
                <template #header="{ open }">
                    <AppIcon
                        name="i-hugeicons-database"
                        class="text-muted size-icon-xs shrink-0"
                        data-testid="insight-query-icon"
                    />
                    <AppBadge
                        v-if="group.count > 1"
                        color="neutral"
                        variant="subtle"
                        size="xs"
                        class="shrink-0 whitespace-nowrap"
                    >
                        x{{ group.count }}
                    </AppBadge>
                    <InsightFlag v-if="group.isNPlusOne" tone="warning">N+1</InsightFlag>
                    <InsightFlag v-if="group.isSlow" tone="error">Slow</InsightFlag>
                    <span
                        v-if="!open"
                        class="text-default min-w-0 flex-1 truncate font-mono text-xs"
                        :title="group.sample"
                        data-testid="insight-query-label"
                    >
                        {{ flattenSql(group.sample) }}
                    </span>
                    <span v-else class="flex-1" />
                    <span
                        class="text-muted shrink-0 font-mono text-xs whitespace-nowrap tabular-nums"
                        data-testid="insight-query-timing"
                    >
                        {{ formatMilliseconds(group.totalMs) }}
                        <span v-if="group.count > 1" class="@max-md:hidden">total</span>
                        <template v-if="hasSeveralConnections">
                            on {{ group.connection }}
                        </template>
                    </span>
                </template>

                <div class="p-base gap-base flex flex-col">
                    <InsightSql :sql="group.sample" />

                    <InsightQueryRuns
                        v-if="group.count > 1"
                        :group="group"
                        :queries="queries"
                    />
                </div>
            </InsightCard>
        </div>
    </InsightSection>
</template>
