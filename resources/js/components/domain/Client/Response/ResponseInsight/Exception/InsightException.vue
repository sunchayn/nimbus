<script setup lang="ts">
/**
 * @component InsightException
 * @description Shows the exception thrown during the request, with its stack frames in a collapsed card.
 */
import InsightCard from '@/components/domain/Client/Response/ResponseInsight/Shared/InsightCard.vue';
import InsightSection from '@/components/domain/Client/Response/ResponseInsight/Shared/InsightSection.vue';
import type { InsightExceptionInfo } from '@/interfaces/http';

defineProps<{ exception: InsightExceptionInfo }>();
</script>

<template>
    <InsightSection name="Exception">
        <InsightCard>
            <template #header>
                <span class="text-error font-mono text-xs break-all">
                    {{ exception.class }}
                </span>
            </template>

            <div class="px-panel py-base gap-tight flex flex-col">
                <span class="text-sm break-words" data-testid="insight-exception-message">
                    {{ exception.message }}
                </span>
                <span class="text-muted font-mono text-xs break-all">
                    {{ exception.file }}:{{ exception.line }}
                </span>
            </div>
        </InsightCard>

        <InsightCard v-if="exception.trace.length" collapsible>
            <template #header>
                <span class="text-default">Stack trace</span>
                <span class="text-muted ms-auto font-mono tabular-nums">
                    {{ exception.trace.length }}
                </span>
            </template>

            <div
                v-for="(frame, index) in exception.trace"
                :key="index"
                class="px-panel py-base gap-tight border-muted flex flex-col border-b last:border-b-0"
                data-testid="insight-frame"
            >
                <span class="font-mono text-xs break-all">{{ frame.call }}</span>
                <span class="text-muted font-mono text-xs break-all">
                    {{ frame.file }}:{{ frame.line }}
                </span>
                <pre
                    v-if="Object.keys(frame.code).length"
                    class="bg-muted p-base overflow-x-auto rounded font-mono text-xs"
                    >{{
                        Object.entries(frame.code)
                            .map(([line, code]) => `${line}  ${code}`)
                            .join('\n')
                    }}</pre
                >
            </div>
        </InsightCard>
    </InsightSection>
</template>
