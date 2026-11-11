<script setup lang="ts">
/**
 * @component InsightEvents
 * @description Lists the events the application dispatched during the request.
 */
import InsightCard from '@/components/domain/Client/Response/ResponseInsight/Shared/InsightCard.vue';
import InsightSection from '@/components/domain/Client/Response/ResponseInsight/Shared/InsightSection.vue';
import type { InsightEventEntry } from '@/interfaces/http';

defineProps<{ entries: InsightEventEntry[]; dropped?: number }>();
</script>

<template>
    <InsightSection name="Events" :dropped="dropped">
        <InsightCard
            v-for="(entry, index) in entries"
            :key="index"
            data-testid="insight-event"
        >
            <template #header>
                <span class="min-w-0 flex-1 font-mono text-xs break-all">
                    {{ entry.name }}
                </span>
            </template>
        </InsightCard>
    </InsightSection>
</template>
