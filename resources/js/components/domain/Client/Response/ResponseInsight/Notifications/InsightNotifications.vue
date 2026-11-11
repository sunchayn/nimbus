<script setup lang="ts">
/**
 * @component InsightNotifications
 * @description Lists the notifications the request sent, with the channel of each.
 */
import { AppBadge } from '@/components/base/badge';
import InsightCard from '@/components/domain/Client/Response/ResponseInsight/Shared/InsightCard.vue';
import InsightSection from '@/components/domain/Client/Response/ResponseInsight/Shared/InsightSection.vue';
import type { InsightNotificationEntry } from '@/interfaces/http';

defineProps<{ entries: InsightNotificationEntry[]; dropped?: number }>();
</script>

<template>
    <InsightSection name="Notifications" :dropped="dropped">
        <InsightCard
            v-for="(entry, index) in entries"
            :key="index"
            data-testid="insight-notification"
        >
            <template #header>
                <span class="min-w-0 flex-1 font-mono text-xs break-all">
                    {{ entry.notification }}
                </span>
                <AppBadge color="neutral" variant="subtle" size="xs">
                    {{ entry.channel }}
                </AppBadge>
            </template>
        </InsightCard>
    </InsightSection>
</template>
