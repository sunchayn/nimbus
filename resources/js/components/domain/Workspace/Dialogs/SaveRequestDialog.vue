<script setup lang="ts">
/**
 * @component SaveRequestDialog
 * @description Asks for the name of the request before it is written to the workspace.
 */
import { AppButton } from '@/components/base/button';
import { AppDialog } from '@/components/base/dialog';
import { AppInput } from '@/components/base/input';
import { AppLabel } from '@/components/base/label';
import { useTabsStore, useWorkspaceStore } from '@/stores';
import { shallowRef, watch } from 'vue';

const open = defineModel<boolean>('open', { required: true });

const tabsStore = useTabsStore();
const workspaceStore = useWorkspaceStore();

const name = shallowRef('');
const folder = shallowRef('');

// The name starts as the saved name, or the label of the tab when the request is new.
watch(open, isOpen => {
    if (isOpen) {
        const tab = tabsStore.activeTab;

        name.value = tab?.workspace?.name ?? tab?.customTitle ?? tab?.title ?? '';
        folder.value = '';
    }
});

const submit = async () => {
    if (!name.value.trim()) {
        return;
    }

    const isSaved = await workspaceStore.save({
        name: name.value,
        asNew: workspaceStore.saveAsNew,
        folder: folder.value,
    });

    if (isSaved) {
        open.value = false;
    }
};
</script>

<template>
    <AppDialog
        v-model:open="open"
        :title="workspaceStore.saveAsNew ? 'Save as' : 'Save request'"
        description="The request is saved as a file in your workspace folder, exactly as you typed it. Use variables for secrets."
    >
        <template #body>
            <form
                id="save-request-form"
                class="gap-base flex flex-col"
                data-testid="save-request-form"
                @submit.prevent="submit"
            >
                <AppLabel for="save-request-name">Name</AppLabel>
                <AppInput
                    id="save-request-name"
                    v-model="name"
                    placeholder="Create user"
                    data-testid="save-request-name"
                />
                <template
                    v-if="!tabsStore.activeTab?.workspace || workspaceStore.saveAsNew"
                >
                    <AppLabel for="save-request-folder">Folder</AppLabel>
                    <AppInput
                        id="save-request-folder"
                        v-model="folder"
                        placeholder="Optional, such as users/admin"
                        data-testid="save-request-folder"
                    />
                </template>
            </form>
        </template>

        <template #footer>
            <AppButton size="sm" variant="outline" color="neutral" @click="open = false">
                Cancel
            </AppButton>
            <AppButton
                size="sm"
                type="submit"
                form="save-request-form"
                label="Save"
                :loading="workspaceStore.isSaving"
                :disabled="!name.trim()"
                data-testid="save-request-submit"
            />
        </template>
    </AppDialog>
</template>
