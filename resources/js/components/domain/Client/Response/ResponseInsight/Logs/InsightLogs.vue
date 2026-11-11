<script setup lang="ts">
/**
 * @component InsightLogs
 * @description Lists the log entries written during the request, each with its context when it has one.
 */
import InsightCard from '@/components/domain/Client/Response/ResponseInsight/Shared/InsightCard.vue';
import InsightFlag from '@/components/domain/Client/Response/ResponseInsight/Shared/InsightFlag.vue';
import InsightSection from '@/components/domain/Client/Response/ResponseInsight/Shared/InsightSection.vue';
import type { InsightLog } from '@/interfaces/http';

defineProps<{ logs: InsightLog[]; dropped?: number }>();

const isSevere = (level: string) =>
    ['emergency', 'alert', 'critical', 'error'].includes(level);

const hasContext = (log: InsightLog) => Object.keys(log.context).length > 0;
</script>

<template>
    <InsightSection
        name="Logs"
        :empty="logs.length ? undefined : 'Nothing was logged.'"
        :dropped="dropped"
    >
        <InsightCard
            v-for="(log, index) in logs"
            :key="index"
            :collapsible="hasContext(log)"
            data-testid="insight-log"
        >
            <template #header>
                <InsightFlag :tone="isSevere(log.level) ? 'error' : null">
                    {{ log.level }}
                </InsightFlag>
                <span class="min-w-0 flex-1 font-mono text-xs break-all">
                    {{ log.message }}
                </span>
            </template>

            <div v-if="hasContext(log)" class="p-base">
                <pre
                    class="bg-muted p-base overflow-x-auto rounded font-mono text-xs"
                    data-testid="insight-log-context"
                    >{{ JSON.stringify(log.context, null, 2) }}</pre
                >
            </div>
        </InsightCard>
    </InsightSection>
</template>
