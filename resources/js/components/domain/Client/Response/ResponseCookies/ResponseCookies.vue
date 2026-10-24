<script setup lang="ts">
/**
 * @component ResponseCookies
 * @description Displays the cookies returned in the response, with encryption/decryption options.
 */
import AppGlowingButton from '@/components/base/button/AppGlowingButton.vue';
import { AppIcon } from '@/components/base/icons';
import CopyButton from '@/components/common/CopyButton.vue';
import KeyValueDisplayList from '@/components/common/KeyValueDisplayList/KeyValueDisplayList.vue';
import PanelSubHeader from '@/components/layout/PanelSubHeader/PanelSubHeader.vue';
import { type ResponseCookie } from '@/interfaces/http';
import { useTabsStore } from '@/stores';
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

/*
 * State.
 */

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
        const isDecryptable = cookie.value.decrypted !== null;

        const value: string = decryptedCookies.value
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
                <AppGlowingButton
                    :disabled="!hasCookies"
                    :icon="
                        decryptedCookies
                            ? 'i-hugeicons-square-lock-02'
                            : 'i-hugeicons-square-unlock-01'
                    "
                    :label="decryptedCookies ? 'Encrypt' : 'Decrypt'"
                    @click="() => (decryptedCookies = !decryptedCookies)"
                />
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
    <div class="min-h-0 flex-1 overflow-y-auto">
        <component
            :is="KeyValueDisplayList<AppNormalizeCookieShape>"
            :items="normalizedCookies"
        >
            <template #value="{ item }">
                <span>
                    <small
                        v-if="!item.isDecryptable"
                        class="text-success mb-1 flex items-center gap-1"
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
