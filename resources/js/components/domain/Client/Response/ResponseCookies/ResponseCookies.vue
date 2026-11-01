<script setup lang="ts">
/**
 * @component ResponseCookies
 * @description Displays the cookies returned in the response, with encryption/decryption options.
 */
import AppGlowingButton from '@/components/base/button/AppGlowingButton.vue';
import { AppIcon } from '@/components/base/icons';
import {
    AppPanel,
    AppPanelDescription,
    AppPanelHeader,
    AppPanelTitle,
} from '@/components/base/panel';
import { AppTooltip } from '@/components/base/tooltip';
import CopyButton from '@/components/common/CopyButton.vue';
import KeyValueDisplayList from '@/components/common/KeyValueDisplayList/KeyValueDisplayList.vue';
import PanelSubHeader from '@/components/layout/PanelSubHeader/PanelSubHeader.vue';
import { type ResponseCookie } from '@/interfaces/http';
import { useEnvironmentVariablesStore, useTabsStore } from '@/stores';
import { singletonPersistenceKey } from '@/utils/stores/uniquePersistenceKey';
import { useClipboard, useStorage } from '@vueuse/core';
import { computed } from 'vue';

/*
 * Types & Interfaces.
 */

export interface ResponseCookiesProps {
    cookies: ResponseCookie[];
}

export interface AppNormalizeCookieShape {
    key: string;
    value: string;
    isDecryptable: boolean;
}

/*
 * Component Setup.
 */

const props = defineProps<ResponseCookiesProps>();

defineSlots<{
    value: (props: { item: ResponseCookie }) => string | number | boolean;
}>();

const tabsStore = useTabsStore();
const environmentStore = useEnvironmentVariablesStore();

/*
 * State.
 */

const isCurrentHost = computed(() => environmentStore.isCurrentHost);

const decryptedCookies = useStorage(
    singletonPersistenceKey(
        'response-viewer-cookies-decrypted-' + tabsStore.activeTab?.id,
    ),
    false,
);

const { copy, copied } = useClipboard();

/*
 * Computed & Methods.
 */

const normalizedCookies = computed<AppNormalizeCookieShape[]>(() => {
    return props.cookies.map((cookie: ResponseCookie): AppNormalizeCookieShape => {
        const isDecryptable = isCurrentHost.value && cookie.value.decrypted !== null;

        const value: string =
            isCurrentHost.value && decryptedCookies.value
                ? (cookie.value.decrypted ?? cookie.value.raw)
                : cookie.value.raw;

        return {
            key: cookie.key,
            value: value,
            isDecryptable: isDecryptable,
        };
    });
});

const hasCookies = computed(() => props.cookies?.length ?? false);

const copyAll = () => {
    const copyValue: string = normalizedCookies.value.reduce(
        (carry: string, current: AppNormalizeCookieShape): string => {
            return `${carry}${current.key}: ${current.value}\n`;
        },
        '',
    );

    copy(copyValue);
};
</script>

<template>
    <PanelSubHeader class="border-b">
        Cookies ({{ normalizedCookies.length }})
        <template #toolbox>
            <div class="gap-base flex translate-x-2 items-center">
                <AppTooltip
                    text="Only available on the current host"
                    :disabled="isCurrentHost"
                >
                    <span class="inline-flex" data-testid="cookies-decrypt-wrapper">
                        <AppGlowingButton
                            :disabled="!isCurrentHost || !hasCookies"
                            :icon="
                                decryptedCookies && isCurrentHost
                                    ? 'i-hugeicons-square-lock-02'
                                    : 'i-hugeicons-square-unlock-01'
                            "
                            :label="
                                decryptedCookies && isCurrentHost ? 'Encrypt' : 'Decrypt'
                            "
                            @click="() => (decryptedCookies = !decryptedCookies)"
                        />
                    </span>
                </AppTooltip>
                <div class="px-panel flex-start flex w-10 items-center">
                    <CopyButton
                        :disabled="!hasCookies"
                        :on-click="copyAll"
                        :copied="copied"
                    />
                </div>
            </div>
        </template>
    </PanelSubHeader>
    <AppPanel v-if="!hasCookies" class="border-b shadow-none" data-testid="cookies-empty">
        <AppPanelHeader class="items-start">
            <AppIcon name="i-hugeicons-cookie" class="size-icon-lg shrink-0" />
            <div class="flex flex-col">
                <AppPanelTitle>No Cookies</AppPanelTitle>
                <AppPanelDescription>
                    The response did not set any cookies.
                </AppPanelDescription>
            </div>
        </AppPanelHeader>
    </AppPanel>
    <div v-else class="min-h-0 flex-1 overflow-y-auto">
        <component
            :is="KeyValueDisplayList<AppNormalizeCookieShape>"
            :items="normalizedCookies"
        >
            <template #value="{ item }">
                <span>
                    <small
                        v-if="isCurrentHost && !item.isDecryptable"
                        class="text-success mb-tight gap-tight flex items-center"
                    >
                        <AppIcon
                            name="i-hugeicons-square-unlock-01"
                            class="size-icon-xs"
                        />
                        Non-Encrypted Value
                    </small>
                    {{ item.value }}
                </span>
            </template>
        </component>
    </div>
</template>
