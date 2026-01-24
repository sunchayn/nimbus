<script setup lang="ts">
/**
 * @component RequestAuthorization
 * @description The main container for configuring authentication methods for the request.
 */
import RequestAuthorizationBasicAuth from '@/components/domain/Client/Request/RequestAuthorization/RequestAuthorizationBasicAuth.vue';
import RequestAuthorizationBearer from '@/components/domain/Client/Request/RequestAuthorization/RequestAuthorizationBearer.vue';
import RequestAuthorizationCurrentUser from '@/components/domain/Client/Request/RequestAuthorization/RequestAuthorizationCurrentUser.vue';
import RequestAuthorizationImpersonateUser from '@/components/domain/Client/Request/RequestAuthorization/RequestAuthorizationImpersonateUser.vue';
import RequestAuthorizationNone from '@/components/domain/Client/Request/RequestAuthorization/RequestAuthorizationNone.vue';
import RequestAuthorizationSelector from '@/components/domain/Client/Request/RequestAuthorization/RequestAuthorizationSelector.vue';
import PanelSubHeader from '@/components/layout/PanelSubHeader/PanelSubHeader.vue';
import { useRequestAuthorization } from '@/composables/request/useRequestAuthorization';
import { AuthorizationType } from '@/interfaces/generated';

/*
 * Types & Interfaces.
 */

export interface AppRequestAuthorizationProps {}

/*
 * Component Setup.
 */

defineProps<AppRequestAuthorizationProps>();

/*
 * Composables.
 */

const { authorization, selectedType, types, updateCurrentAuthorizationValue } =
    useRequestAuthorization();
</script>

<template>
    <div class="flex h-full flex-col">
        <PanelSubHeader class="border-b">
            <RequestAuthorizationSelector v-model="selectedType" :types="types" />
        </PanelSubHeader>
        <div class="relative w-full flex-1 overflow-hidden">
            <RequestAuthorizationNone
                v-if="!authorization || authorization.type === AuthorizationType.None"
            />
            <RequestAuthorizationCurrentUser
                v-else-if="authorization.type === AuthorizationType.CurrentUser"
            />
            <RequestAuthorizationImpersonateUser
                v-else-if="authorization.type === AuthorizationType.Impersonate"
                :model-value="authorization.value as number"
                @update:model-value="value => updateCurrentAuthorizationValue(value)"
            />
            <RequestAuthorizationBearer
                v-else-if="authorization.type === AuthorizationType.Bearer"
                :model-value="authorization.value"
                @update:model-value="value => updateCurrentAuthorizationValue(value)"
            />
            <RequestAuthorizationBasicAuth
                v-else-if="authorization.type === AuthorizationType.Basic"
                :model-value="{
                    username: authorization.value.username,
                    password: authorization.value.password,
                }"
                @update:model-value="value => updateCurrentAuthorizationValue(value)"
            />
        </div>
    </div>
</template>
