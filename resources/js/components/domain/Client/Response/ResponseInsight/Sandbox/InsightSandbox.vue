<script setup lang="ts">
/**
 * @component InsightSandbox
 * @description Lists what sandbox mode faked during the request, grouped by the effect that was sandboxed.
 * A group is shown for every effect that was sandboxed, with a note when nothing was captured.
 */
import { AppBadge } from '@/components/base/badge';
import InsightCard from '@/components/domain/Client/Response/ResponseInsight/Shared/InsightCard.vue';
import InsightSection from '@/components/domain/Client/Response/ResponseInsight/Shared/InsightSection.vue';
import type { InsightSandbox } from '@/interfaces/http';
import { reactive } from 'vue';

const props = defineProps<{ sandbox: InsightSandbox }>();

// A lone message opens on its own, since there is nothing else to compare it with.
const opened = reactive<Record<number, boolean>>({});

const isOpen = (index: number) => opened[index] ?? props.sandbox.mail?.length === 1;
</script>

<template>
    <InsightSection name="Sandbox">
        <p class="text-muted text-xs">
            The request ran with the sandbox mode ON. These side effects were sandboxed.
        </p>

        <section
            v-if="sandbox.mail"
            class="gap-tight flex flex-col"
            data-testid="sandbox-mail-group"
        >
            <h3 class="text-muted px-hair text-xs">
                Mail
                <span class="text-dimmed font-mono tabular-nums">
                    {{ sandbox.mail.length }}
                </span>
            </h3>
            <p v-if="!sandbox.mail.length" class="text-dimmed px-hair text-xs">
                No mail was sent.
            </p>
            <InsightCard
                v-for="(message, index) in sandbox.mail"
                :key="index"
                collapsible
                :open="isOpen(index)"
                data-testid="sandbox-mail"
                @update:open="value => (opened[index] = value)"
            >
                <template #header>
                    <span class="text-default min-w-0 flex-1 text-xs break-words">
                        {{ message.subject || 'No subject' }}
                    </span>
                    <span class="text-muted shrink-0 text-xs break-all">
                        To {{ message.to.join(', ') }}
                    </span>
                </template>
                <pre
                    class="bg-muted p-base max-h-64 overflow-auto font-mono text-xs break-all whitespace-pre-wrap"
                    data-testid="sandbox-mail-body"
                    >{{ message.body }}</pre
                >
            </InsightCard>
        </section>

        <section
            v-if="sandbox.notifications"
            class="gap-tight flex flex-col"
            data-testid="sandbox-notification-group"
        >
            <h3 class="text-muted px-hair text-xs">
                Notifications
                <span class="text-dimmed font-mono tabular-nums">
                    {{ sandbox.notifications.length }}
                </span>
            </h3>
            <p v-if="!sandbox.notifications.length" class="text-dimmed px-hair text-xs">
                No notification was sent.
            </p>
            <InsightCard
                v-for="(item, index) in sandbox.notifications"
                :key="index"
                data-testid="sandbox-notification"
            >
                <template #header>
                    <span
                        class="min-w-0 flex-1 truncate font-mono text-xs"
                        :title="item.notification"
                    >
                        {{ item.notification }}
                    </span>
                    <AppBadge
                        v-for="channel in item.channels"
                        :key="channel"
                        color="neutral"
                        variant="subtle"
                        size="xs"
                        class="shrink-0"
                        data-testid="sandbox-channel"
                    >
                        {{ channel }}
                    </AppBadge>
                </template>
                <p class="text-muted px-panel py-base text-xs break-all">
                    To {{ item.notifiable }}
                </p>
            </InsightCard>
        </section>

        <section
            v-if="sandbox.jobs"
            class="gap-tight flex flex-col"
            data-testid="sandbox-job-group"
        >
            <h3 class="text-muted px-hair text-xs">
                Queued jobs
                <span class="text-dimmed font-mono tabular-nums">
                    {{ sandbox.jobs.length }}
                </span>
            </h3>
            <p v-if="!sandbox.jobs.length" class="text-dimmed px-hair text-xs">
                No job was queued.
            </p>
            <InsightCard
                v-for="(item, index) in sandbox.jobs"
                :key="index"
                data-testid="sandbox-job"
            >
                <template #header>
                    <span class="min-w-0 flex-1 font-mono text-xs break-all">
                        {{ item.job }}
                    </span>
                    <span class="text-muted shrink-0 font-mono text-xs">
                        {{ item.queue }}
                    </span>
                </template>
            </InsightCard>
        </section>
    </InsightSection>
</template>
