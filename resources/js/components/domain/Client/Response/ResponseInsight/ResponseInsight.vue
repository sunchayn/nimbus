<script setup lang="ts">
/**
 * @component ResponseInsight
 * @description Shows what Laravel did while it handled the request, one pill tab for each kind of data.
 * It owns the selected tab and the query picked on the timeline.
 */
import { AppScrollArea } from '@/components/base/scroll-area';
import { AppTabs, type AppTabsItem } from '@/components/base/tabs';
import type { Insight } from '@/interfaces/http';
import type { QueryFocus, TimelineRow } from '@/utils/insight';
import { computed, ref, watch } from 'vue';
import InsightCache from './Cache/InsightCache.vue';
import InsightEvents from './Events/InsightEvents.vue';
import InsightException from './Exception/InsightException.vue';
import InsightGates from './Gates/InsightGates.vue';
import InsightHttp from './Http/InsightHttp.vue';
import InsightJobs from './Jobs/InsightJobs.vue';
import InsightLogs from './Logs/InsightLogs.vue';
import InsightMail from './Mail/InsightMail.vue';
import InsightNotifications from './Notifications/InsightNotifications.vue';
import InsightQueries from './Queries/InsightQueries.vue';
import InsightTimeline from './Timeline/InsightTimeline.vue';

export interface ResponseInsightProps {
    insight: Insight;
}

interface InsightTab extends AppTabsItem {
    /** Shown after the label. Tabs without a count show none. */
    count?: number;
}

const props = defineProps<ResponseInsightProps>();

/**
 * A kind with no entries has no tab, unless entries were dropped, which still is something to tell.
 */
const kindTab = (
    label: string,
    value: string,
    list: unknown[],
    dropped: number,
): InsightTab[] =>
    list.length || dropped ? [{ label, value, slot: value, count: list.length }] : [];

const tabs = computed<InsightTab[]>(() => [
    ...(props.insight.exception
        ? [
              {
                  label: 'Exception',
                  value: 'exception',
                  slot: 'exception',
              },
          ]
        : []),
    { label: 'Timeline', value: 'timeline', slot: 'timeline' },
    {
        label: 'Queries',
        value: 'queries',
        slot: 'queries',
        count: props.insight.queries.length,
    },
    {
        label: 'Logs',
        value: 'logs',
        slot: 'logs',
        count: props.insight.logs.length,
    },
    {
        label: 'Gates',
        value: 'gates',
        slot: 'gates',
        count: props.insight.gates.length,
    },
    ...kindTab('Cache', 'cache', props.insight.cache, props.insight.dropped.cache),
    ...kindTab('Jobs', 'jobs', props.insight.jobs, props.insight.dropped.jobs),
    ...kindTab('Mail', 'mail', props.insight.mail, props.insight.dropped.mail),
    ...kindTab(
        'Notifications',
        'notifications',
        props.insight.notifications,
        props.insight.dropped.notifications,
    ),
    ...kindTab('HTTP', 'http', props.insight.http, props.insight.dropped.http),
    ...kindTab('Events', 'events', props.insight.events, props.insight.dropped.events),
]);

// An exception is the most likely reason to open the panel, so it opens first.
const tab = ref<string | number>(props.insight.exception ? 'exception' : 'timeline');

// The insight changes with the response, and the open tab may not exist in the new one.
watch(tabs, available => {
    if (!available.some(item => item.value === tab.value)) {
        tab.value = available[0].value as string;
    }
});

// The query picked on the timeline, so the Queries tab can scroll to it.
const focus = ref<QueryFocus | null>(null);

let picks = 0;

const select = ({ kind, index }: Pick<TimelineRow, 'kind' | 'index'>) => {
    if (kind === 'http') {
        tab.value = 'http';

        return;
    }

    tab.value = 'queries';
    focus.value = { index, nonce: ++picks };
};
</script>

<template>
    <div
        class="bg-muted p-panel flex min-h-0 flex-1 flex-col"
        data-testid="response-insight"
    >
        <AppTabs
            v-model="tab"
            :items="tabs"
            variant="pill"
            size="xs"
            color="neutral"
            class="gap-base min-h-0 flex-1 items-stretch"
            :ui="{
                list: 'bg-elevated p-tight w-fit max-w-full',
                trigger: 'text-muted data-[state=active]:text-highlighted',
                content: 'flex min-h-0 flex-1 flex-col overflow-hidden',
            }"
        >
            <template #trailing="{ item }">
                <span
                    v-if="item.count !== undefined"
                    class="font-mono tabular-nums"
                    :class="item.count === 0 ? 'text-dimmed' : 'text-muted'"
                    :data-empty="item.count === 0 || undefined"
                    data-testid="insight-tab-count"
                >
                    {{ item.count }}
                </span>
            </template>

            <template #exception>
                <AppScrollArea class="min-h-0 flex-1">
                    <InsightException
                        v-if="insight.exception"
                        :exception="insight.exception"
                    />
                </AppScrollArea>
            </template>
            <template #timeline>
                <AppScrollArea class="min-h-0 flex-1">
                    <InsightTimeline :insight="insight" @select="select" />
                </AppScrollArea>
            </template>
            <template #queries>
                <AppScrollArea class="min-h-0 flex-1">
                    <InsightQueries
                        :queries="insight.queries"
                        :dropped="insight.dropped.queries"
                        :focus="focus"
                    />
                </AppScrollArea>
            </template>
            <template #logs>
                <AppScrollArea class="min-h-0 flex-1">
                    <InsightLogs :logs="insight.logs" :dropped="insight.dropped.logs" />
                </AppScrollArea>
            </template>
            <template #gates>
                <AppScrollArea class="min-h-0 flex-1">
                    <InsightGates :gates="insight.gates" />
                </AppScrollArea>
            </template>
            <template #cache>
                <AppScrollArea class="min-h-0 flex-1">
                    <InsightCache
                        :entries="insight.cache"
                        :dropped="insight.dropped.cache"
                    />
                </AppScrollArea>
            </template>
            <template #jobs>
                <AppScrollArea class="min-h-0 flex-1">
                    <InsightJobs
                        :entries="insight.jobs"
                        :dropped="insight.dropped.jobs"
                    />
                </AppScrollArea>
            </template>
            <template #mail>
                <AppScrollArea class="min-h-0 flex-1">
                    <InsightMail
                        :entries="insight.mail"
                        :dropped="insight.dropped.mail"
                    />
                </AppScrollArea>
            </template>
            <template #notifications>
                <AppScrollArea class="min-h-0 flex-1">
                    <InsightNotifications
                        :entries="insight.notifications"
                        :dropped="insight.dropped.notifications"
                    />
                </AppScrollArea>
            </template>
            <template #http>
                <AppScrollArea class="min-h-0 flex-1">
                    <InsightHttp
                        :entries="insight.http"
                        :dropped="insight.dropped.http"
                    />
                </AppScrollArea>
            </template>
            <template #events>
                <AppScrollArea class="min-h-0 flex-1">
                    <InsightEvents
                        :entries="insight.events"
                        :dropped="insight.dropped.events"
                    />
                </AppScrollArea>
            </template>
        </AppTabs>
    </div>
</template>
