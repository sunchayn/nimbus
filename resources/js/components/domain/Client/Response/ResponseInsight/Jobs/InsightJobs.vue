<script setup lang="ts">
/**
 * @component InsightJobs
 * @description Lists the jobs the request queued, with the connection and queue of each.
 */
import InsightCard from '@/components/domain/Client/Response/ResponseInsight/Shared/InsightCard.vue';
import InsightSection from '@/components/domain/Client/Response/ResponseInsight/Shared/InsightSection.vue';
import type { InsightJobEntry } from '@/interfaces/http';

defineProps<{ entries: InsightJobEntry[]; dropped?: number }>();

const destination = (entry: InsightJobEntry) =>
    [entry.connection, entry.queue].filter(Boolean).join(' / ');
</script>

<template>
    <InsightSection name="Jobs" :dropped="dropped">
        <InsightCard
            v-for="(entry, index) in entries"
            :key="index"
            data-testid="insight-job"
        >
            <template #header>
                <span class="min-w-0 flex-1 font-mono text-xs break-all">
                    {{ entry.job }}
                </span>
                <span
                    v-if="destination(entry)"
                    class="text-muted shrink-0 font-mono text-xs"
                    data-testid="insight-job-destination"
                >
                    {{ destination(entry) }}
                </span>
            </template>
        </InsightCard>
    </InsightSection>
</template>
