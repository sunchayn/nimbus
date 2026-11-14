<script setup lang="ts">
/**
 * @component InsightHttp
 * @description Lists the calls the request made with the HTTP client, each with its status and duration.
 */
import { AppBadge } from '@/components/base/badge';
import InsightCard from '@/components/domain/Client/Response/ResponseInsight/Shared/InsightCard.vue';
import InsightSection from '@/components/domain/Client/Response/ResponseInsight/Shared/InsightSection.vue';
import type { InsightHttpEntry } from '@/interfaces/http';
import { formatMilliseconds, httpStatusColor } from '@/utils/insight';

defineProps<{ entries: InsightHttpEntry[]; dropped?: number }>();
</script>

<template>
    <InsightSection name="HTTP" :dropped="dropped">
        <InsightCard
            v-for="(entry, index) in entries"
            :key="index"
            data-testid="insight-http-call"
        >
            <template #header>
                <AppBadge
                    :color="httpStatusColor(entry.status)"
                    variant="subtle"
                    size="xs"
                    data-testid="insight-http-status"
                >
                    {{ entry.status ?? 'Failed' }}
                </AppBadge>
                <span class="text-muted shrink-0 font-mono text-xs">
                    {{ entry.method }}
                </span>
                <span
                    class="min-w-0 flex-1 truncate font-mono text-xs"
                    :title="entry.url"
                >
                    {{ entry.url }}
                </span>
                <span
                    v-if="entry.duration_ms !== null"
                    class="text-muted shrink-0 font-mono text-xs tabular-nums"
                >
                    {{ formatMilliseconds(entry.duration_ms) }}
                </span>
            </template>
        </InsightCard>
    </InsightSection>
</template>
