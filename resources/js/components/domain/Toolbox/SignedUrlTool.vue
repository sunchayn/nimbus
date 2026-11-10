<script setup lang="ts">
/**
 * @component SignedUrlTool
 * @description Verifies a signed URL, or creates one from a route name.
 */
import { AppButton } from '@/components/base/button';
import { AppInput } from '@/components/base/input';
import { AppSelectMenu } from '@/components/base/select';
import { AppTabs } from '@/components/base/tabs';
import { AppTextarea } from '@/components/base/textarea';
import { useToolRequest } from '@/composables/toolbox/useToolRequest';
import { useRoutesStore } from '@/stores';
import { listRoutes } from '@/utils/routes';
import { computed, shallowRef } from 'vue';
import ToolResult from './ToolResult.vue';

/*
 * Routes.
 */

const routesStore = useRoutesStore();

// Only named routes can be signed, and a name is shown once even when several methods share it.
const routeItems = computed(() => {
    const items = new Map<string, { label: string; description: string }>();

    for (const { route } of listRoutes(routesStore.routes)) {
        const name = route.metadata?.name;

        if (name && !items.has(name)) {
            items.set(name, {
                label: name,
                description: `${route.method.toUpperCase()} ${route.shortEndpoint || route.endpoint}`,
            });
        }
    }

    return [...items.values()];
});

/*
 * State.
 */

const url = shallowRef('');
const route = shallowRef('');
const parameters = shallowRef('');
const expiresInMinutes = shallowRef('');
const parametersError = shallowRef<string | null>(null);

const verification = useToolRequest('signed-url/verify');
const creation = useToolRequest('signed-url/create');

const shown = shallowRef<'verify' | 'create'>('verify');

const modes = [
    { label: 'Verify', value: 'verify', icon: 'i-hugeicons-checkmark-circle-02' },
    { label: 'Sign', value: 'create', icon: 'i-hugeicons-link-04' },
];

/*
 * Methods.
 */

const verify = () => {
    shown.value = 'verify';
    verification.run({ url: url.value });
};

const create = () => {
    shown.value = 'create';
    parametersError.value = null;

    let parsed: unknown = {};

    try {
        parsed = parameters.value.trim() ? JSON.parse(parameters.value) : {};
    } catch {
        parametersError.value = 'The parameters must be valid JSON.';

        return;
    }

    creation.run({
        route: route.value,
        parameters: parsed,
        expires_in_minutes: expiresInMinutes.value
            ? Number(expiresInMinutes.value)
            : null,
    });
};
</script>

<template>
    <div class="gap-base flex flex-col" data-testid="signed-url-tool">
        <AppTabs
            v-model="shown"
            :items="modes"
            :content="false"
            variant="pill"
            color="neutral"
            class="w-full"
            :ui="{
                list: 'bg-muted p-tight w-full',
                trigger: 'text-muted data-[state=active]:text-highlighted flex-1',
                indicator: 'bg-default border-inverted border shadow-none',
            }"
            data-testid="signed-url-mode"
        />

        <div v-show="shown === 'verify'" class="gap-base flex flex-col">
            <AppTextarea
                v-model="url"
                placeholder="Signed URL to verify"
                :rows="3"
                data-testid="signed-url-url"
            />
            <div>
                <AppButton
                    label="Verify"
                    :loading="verification.loading.value"
                    data-testid="signed-url-verify"
                    @click="verify"
                />
            </div>
        </div>

        <div v-show="shown === 'create'" class="gap-base flex flex-col">
            <AppSelectMenu
                v-model="route"
                :items="routeItems"
                value-key="label"
                :filter-fields="['label', 'description']"
                placeholder="Select a route"
                :search-input="{ placeholder: 'Search routes...' }"
                class="w-full"
                :ui="{
                    item: 'py-tight',
                    itemLabel: 'text-highlighted text-sm font-medium',
                    itemDescription: 'text-muted text-xs',
                }"
                data-testid="signed-url-route"
            />
            <AppTextarea
                v-model="parameters"
                placeholder='Parameters as JSON, such as {"user": 1}'
                :rows="2"
                data-testid="signed-url-parameters"
            />
            <AppInput
                v-model="expiresInMinutes"
                type="number"
                placeholder="Expires in minutes (optional)"
                data-testid="signed-url-expiry"
            />
            <div>
                <AppButton
                    label="Sign"
                    :loading="creation.loading.value"
                    data-testid="signed-url-create"
                    @click="create"
                />
            </div>
        </div>

        <ToolResult
            :result="
                shown === 'verify' ? verification.result.value : creation.result.value
            "
            :error="
                shown === 'verify'
                    ? verification.error.value
                    : (parametersError ?? creation.error.value)
            "
        />
    </div>
</template>
