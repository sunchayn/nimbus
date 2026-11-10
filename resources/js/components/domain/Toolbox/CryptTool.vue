<script setup lang="ts">
/**
 * @component CryptTool
 * @description Decrypts or encrypts a value with the app key, with an optional cookie prefix.
 */
import { AppButton } from '@/components/base/button';
import { AppInput } from '@/components/base/input';
import { AppTextarea } from '@/components/base/textarea';
import { useToolRequest } from '@/composables/toolbox/useToolRequest';
import { shallowRef } from 'vue';
import ToolResult from './ToolResult.vue';

/*
 * State.
 */

const value = shallowRef('');
const cookieName = shallowRef('');

const decryption = useToolRequest('decrypt');
const encryption = useToolRequest('encrypt');

const shown = shallowRef<'decrypt' | 'encrypt'>('decrypt');

/*
 * Methods.
 */

const submit = (mode: 'decrypt' | 'encrypt') => {
    shown.value = mode;

    (mode === 'decrypt' ? decryption : encryption).run({
        value: value.value,
        cookie_name: cookieName.value || null,
    });
};
</script>

<template>
    <div class="gap-base flex flex-col" data-testid="crypt-tool">
        <AppTextarea
            v-model="value"
            placeholder="Value or encrypted cookie"
            :rows="4"
            data-testid="crypt-value"
        />
        <AppInput
            v-model="cookieName"
            placeholder="Cookie name (optional, checks the cookie prefix)"
            data-testid="crypt-cookie"
        />
        <div class="gap-base flex">
            <AppButton
                label="Decrypt"
                :loading="decryption.loading.value"
                data-testid="crypt-decrypt"
                @click="submit('decrypt')"
            />
            <AppButton
                label="Encrypt"
                variant="outline"
                :loading="encryption.loading.value"
                data-testid="crypt-encrypt"
                @click="submit('encrypt')"
            />
        </div>
        <ToolResult
            :result="
                shown === 'decrypt' ? decryption.result.value : encryption.result.value
            "
            :error="shown === 'decrypt' ? decryption.error.value : encryption.error.value"
        />
    </div>
</template>
