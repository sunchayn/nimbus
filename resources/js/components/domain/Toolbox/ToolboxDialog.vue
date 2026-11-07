<script setup lang="ts">
/**
 * @component ToolboxDialog
 * @description A dialog with small Laravel tools to decode, sign, and inspect values.
 * The dialog stays mounted when it closes, so every tool keeps its fields and result.
 */
import { AppDialog } from '@/components/base/dialog';
import { AppIcon } from '@/components/base/icons';
import { AppTabs } from '@/components/base/tabs';
import CryptTool from '@/components/domain/Toolbox/CryptTool.vue';
import HashTool from '@/components/domain/Toolbox/HashTool.vue';
import SessionTool from '@/components/domain/Toolbox/SessionTool.vue';
import SignedUrlTool from '@/components/domain/Toolbox/SignedUrlTool.vue';
import TokenTool from '@/components/domain/Toolbox/TokenTool.vue';
import { computed, shallowRef } from 'vue';

/*
 * Component Setup.
 */

const open = defineModel<boolean>('open', { default: false });

/*
 * Tools.
 */

const tools = [
    {
        value: 'crypt',
        label: 'Encryption',
        icon: 'i-hugeicons-lock-key',
        description: 'Encrypt a value or decrypt a payload with the app key.',
        component: CryptTool,
    },
    {
        value: 'signed-url',
        label: 'Signed URL',
        icon: 'i-hugeicons-link-04',
        description: 'Create a signed URL or check the signature of one.',
        component: SignedUrlTool,
    },
    {
        value: 'hash',
        label: 'Hash',
        icon: 'i-hugeicons-hashtag',
        description: 'Make a hash or check a value against a hash.',
        component: HashTool,
    },
    {
        value: 'token',
        label: 'Tokens',
        icon: 'i-hugeicons-key-01',
        description: 'Decode a JWT or look up a Sanctum token.',
        component: TokenTool,
    },
    {
        value: 'session',
        label: 'Session',
        icon: 'i-hugeicons-database-01',
        description: 'Read the data stored for a session ID.',
        component: SessionTool,
    },
];

const tabs = tools.map(({ value, label, icon }) => ({ value, label, icon }));

/*
 * State.
 */

const selected = shallowRef(tools[0].value);

const activeTool = computed(
    () => tools.find(tool => tool.value === selected.value) ?? tools[0],
);
</script>

<template>
    <AppDialog
        v-model:open="open"
        :unmount-on-hide="false"
        :description="activeTool.description"
        :ui="{ content: 'max-w-3xl' }"
    >
        <template #title>
            <AppIcon name="i-hugeicons-tools" class="size-icon-md" />
            Toolbox
        </template>

        <template #body>
            <div class="gap-loose flex items-stretch" data-testid="toolbox-dialog">
                <AppTabs
                    v-model="selected"
                    :items="tabs"
                    :content="false"
                    orientation="vertical"
                    variant="pill"
                    color="neutral"
                    class="w-auto shrink-0"
                    :ui="{
                        list: 'bg-muted p-tight w-44 h-full',
                        trigger:
                            'text-muted data-[state=active]:text-highlighted justify-start gap-base',
                        indicator: 'bg-default border-inverted border shadow-none',
                    }"
                    data-testid="toolbox-tabs"
                />

                <div class="gap-base flex min-w-0 flex-1 flex-col">
                    <component
                        :is="tool.component"
                        v-for="tool in tools"
                        v-show="tool.value === selected"
                        :key="tool.value"
                    />
                </div>
            </div>
        </template>
    </AppDialog>
</template>
