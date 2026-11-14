<script setup lang="ts">
/**
 * @component InsightMail
 * @description Lists the messages the request sent, with the subject, the recipients, and the mailable class.
 */
import InsightCard from '@/components/domain/Client/Response/ResponseInsight/Shared/InsightCard.vue';
import InsightSection from '@/components/domain/Client/Response/ResponseInsight/Shared/InsightSection.vue';
import type { InsightMailEntry } from '@/interfaces/http';

defineProps<{ entries: InsightMailEntry[]; dropped?: number }>();

const hasDetails = (entry: InsightMailEntry) =>
    entry.to.length > 0 || entry.mailable !== null;
</script>

<template>
    <InsightSection name="Mail" :dropped="dropped">
        <InsightCard
            v-for="(entry, index) in entries"
            :key="index"
            data-testid="insight-mail-message"
        >
            <template #header>
                <span class="text-default min-w-0 flex-1 text-xs break-words">
                    {{ entry.subject || 'No subject' }}
                </span>
            </template>

            <div
                v-if="hasDetails(entry)"
                class="px-panel py-base gap-tight flex flex-col text-xs"
            >
                <span v-if="entry.to.length" class="text-muted break-all">
                    To {{ entry.to.join(', ') }}
                </span>
                <span
                    v-if="entry.mailable"
                    class="text-muted font-mono break-all"
                    data-testid="insight-mail-mailable"
                >
                    {{ entry.mailable }}
                </span>
            </div>
        </InsightCard>
    </InsightSection>
</template>
