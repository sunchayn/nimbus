<script setup lang="ts">
/**
 * @component InsightCache
 * @description Lists the cache operations of the request, each with its key.
 */
import InsightCard from '@/components/domain/Client/Response/ResponseInsight/Shared/InsightCard.vue';
import InsightFlag, {
    type InsightFlagTone,
} from '@/components/domain/Client/Response/ResponseInsight/Shared/InsightFlag.vue';
import InsightSection from '@/components/domain/Client/Response/ResponseInsight/Shared/InsightSection.vue';
import type { InsightCacheEntry } from '@/interfaces/http';

defineProps<{ entries: InsightCacheEntry[]; dropped?: number }>();

// A small dot tells the operations apart, and the label says which one it is.
const tones: Record<InsightCacheEntry['operation'], InsightFlagTone> = {
    hit: 'success',
    miss: 'muted',
    write: 'primary',
    forget: 'error',
};
</script>

<template>
    <InsightSection name="Cache" :dropped="dropped">
        <InsightCard
            v-for="(entry, index) in entries"
            :key="index"
            data-testid="insight-cache-entry"
        >
            <template #header>
                <InsightFlag
                    :tone="tones[entry.operation]"
                    data-testid="insight-cache-operation"
                >
                    {{ entry.operation }}
                </InsightFlag>
                <span class="min-w-0 flex-1 font-mono text-xs break-all">
                    {{ entry.key }}
                </span>
            </template>
        </InsightCard>
    </InsightSection>
</template>
