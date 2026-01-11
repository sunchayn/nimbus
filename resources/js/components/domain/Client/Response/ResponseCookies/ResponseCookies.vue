<script setup lang="ts">
import AppGlowingButton from '@/components/base/button/AppGlowingButton.vue';
import CopyButton from '@/components/common/CopyButton.vue';
import KeyValueDisplayList from '@/components/common/KeyValueDisplayList/KeyValueDisplayList.vue';
import PanelSubHeader from '@/components/layout/PanelSubHeader/PanelSubHeader.vue';
import { ResponseCookie } from '@/interfaces/http';
import { uniquePersistenceKey } from '@/utils/stores';
import { useClipboard, useStorage } from '@vueuse/core';
import { LockIcon, LockOpenIcon } from 'lucide-vue-next';
import { computed } from 'vue';

interface ResponseCookiesProps {
    cookies: ResponseCookie[];
}

interface NormalizeCookieShape {
    key: string;
    value: string;
    isDecryptable: boolean;
}

const props = defineProps<ResponseCookiesProps>();

const decryptedCookies = useStorage(
    uniquePersistenceKey('response-viewer-cookies-decrypted'),
    false,
);

defineSlots<{
    value: (props: { item: ResponseCookie }) => string | number | boolean;
}>();

const normalizedCookies = computed<NormalizeCookieShape[]>(() => {
    return props.cookies.map((cookie: ResponseCookie): NormalizeCookieShape => {
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

const { copy, copied } = useClipboard();

const copyAll = () => {
    const copyValue: string = normalizedCookies.value.reduce(
        (carry: string, current: NormalizeCookieShape): string => {
            return `${carry}${current.key}: ${current.value}\n`;
        },
        '',
    );

    copy(copyValue);
};

const hasCookies = computed(() => props.cookies?.length ?? false);
</script>

<template>
    <PanelSubHeader class="border-b">
        Cookies ({{ normalizedCookies.length }})
        <template #toolbox>
            <div class="flex translate-x-2 items-center space-x-2">
                <AppGlowingButton
                    :disabled="!hasCookies"
                    @click="() => (decryptedCookies = !decryptedCookies)"
                >
                    <template v-if="decryptedCookies">
                        <LockIcon class="size-5" />
                        Encrypt
                    </template>
                    <template v-else>
                        <LockOpenIcon class="size-5" />
                        Decrypt
                    </template>
                </AppGlowingButton>
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
            :is="KeyValueDisplayList<NormalizeCookieShape>"
            :items="normalizedCookies"
        >
            <template #value="{ item }">
                <span>
                    <small
                        v-if="!item.isDecryptable"
                        class="mb-1 flex items-center gap-1 text-green-500"
                    >
                        <LockOpenIcon :size="10" />
                        Non-Encrypted Value
                    </small>
                    {{ item.value }}
                </span>
            </template>
        </component>
    </div>
</template>
