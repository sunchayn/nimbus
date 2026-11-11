<script setup lang="ts">
/**
 * @component InsightGates
 * @description Lists the gate and policy checks that ran in cards, denied ones first.
 */
import InsightCard from '@/components/domain/Client/Response/ResponseInsight/Shared/InsightCard.vue';
import InsightFlag from '@/components/domain/Client/Response/ResponseInsight/Shared/InsightFlag.vue';
import InsightSection from '@/components/domain/Client/Response/ResponseInsight/Shared/InsightSection.vue';
import type { InsightGate } from '@/interfaces/http';
import { deniedFirst } from '@/utils/insight';
import { computed } from 'vue';

const props = defineProps<{ gates: InsightGate[] }>();

const sorted = computed(() => deniedFirst(props.gates));

const label = (result: boolean | null) =>
    result === false ? 'Denied' : result === true ? 'Allowed' : 'No decision';
const hasDetails = (gate: InsightGate) => gate.arguments.length > 0 || gate.user !== null;
</script>

<template>
    <InsightSection
        name="Gates"
        :empty="gates.length ? undefined : 'No gate was checked.'"
    >
        <InsightCard
            v-for="(gate, index) in sorted"
            :key="index"
            data-testid="insight-gate"
        >
            <template #header>
                <span class="min-w-0 flex-1 font-mono text-xs break-all">
                    {{ gate.ability }}
                </span>
                <InsightFlag :tone="gate.result === false ? 'error' : null">
                    {{ label(gate.result) }}
                </InsightFlag>
            </template>

            <div
                v-if="hasDetails(gate)"
                class="px-panel py-base gap-tight flex flex-col text-xs"
            >
                <span v-if="gate.arguments.length" class="text-muted font-mono break-all">
                    ({{ gate.arguments.join(', ') }})
                </span>
                <span v-if="gate.user" class="text-muted">for {{ gate.user }}</span>
            </div>
        </InsightCard>
    </InsightSection>
</template>
