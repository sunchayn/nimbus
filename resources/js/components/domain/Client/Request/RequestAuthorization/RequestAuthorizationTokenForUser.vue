<script setup lang="ts">
/**
 * @component RequestAuthorizationTokenForUser
 * @description User key and abilities for a personal access token that Nimbus creates and deletes for one request.
 */
import { AppIcon } from '@/components/base/icons';
import {
    AppPanel,
    AppPanelDescription,
    AppPanelHeader,
    AppPanelTitle,
} from '@/components/base/panel';
import { AppInput } from '@/components/base/input';
import { useConfigStore } from '@/stores/core/useConfigStore';
import RequestAuthorizationImpersonateUser from './RequestAuthorizationImpersonateUser.vue';

/*
 * Component Setup.
 */

defineProps<{ guard?: string }>();

const emit = defineEmits<{
    'update:guard': [value: string];
}>();

const model = defineModel<number | string>({ default: () => '' });

const isUnavailable = useConfigStore().tokenGuards.length === 0;

const abilities = defineModel<string>('abilities', { default: () => '*' });
</script>

<template>
    <div>
        <AppPanel v-if="isUnavailable" class="border-b shadow-none">
            <AppPanelHeader class="items-start">
                <AppIcon
                    name="i-hugeicons-alert-circle"
                    class="text-error size-icon-lg shrink-0"
                />
                <div class="flex flex-col" data-testid="no-token-guard">
                    <AppPanelTitle>No guard can create tokens</AppPanelTitle>
                    <AppPanelDescription>
                        Add the HasApiTokens trait of Sanctum or Passport to the user
                        model.
                    </AppPanelDescription>
                </div>
            </AppPanelHeader>
        </AppPanel>
        <div v-else>
            <RequestAuthorizationImpersonateUser
                v-model="model"
                :guard="guard"
                tokens-only
                @update:guard="value => emit('update:guard', value)"
            />
            <div class="h-control-field grid grid-cols-3 border-b">
                <label
                    class="px-panel h-control-field py-tight flex items-center border-r text-xs"
                    for="token-abilities"
                >
                    Abilities
                </label>
                <AppInput
                    id="token-abilities"
                    v-model="abilities"
                    placeholder="Comma separated, for example posts:read, posts:write"
                    variant="none"
                    class="col-span-2 h-full"
                    :ui="{ base: 'text-xs' }"
                    data-testid="token-abilities-input"
                />
            </div>
        </div>
    </div>
</template>
