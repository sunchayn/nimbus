<script setup lang="ts">
/**
 * @component WorkspaceConflictDialog
 * @description Asks what to do when a request file changed on disk after the tab loaded it.
 */
import { AppButton } from '@/components/base/button';
import { AppDialog } from '@/components/base/dialog';
import { useTabsStore } from '@/stores/request/useTabsStore';
import { useWorkspaceStore } from '@/stores/workspace/useWorkspaceStore';

const tabsStore = useTabsStore();
const workspaceStore = useWorkspaceStore();
</script>

<template>
    <AppDialog
        :open="workspaceStore.isConflictOpen"
        title="The file changed on disk"
        :description="`${tabsStore.activeTab?.workspace?.name ?? 'This request'} was changed or removed after you opened it. Choose which version to keep.`"
        @update:open="isOpen => !isOpen && workspaceStore.dismissConflict()"
    >
        <template #body>
            <ul class="gap-tight text-muted flex flex-col text-sm">
                <li>
                    <strong class="text-default">Reload</strong>
                    drops your changes and takes the file as it is on disk.
                </li>
                <li>
                    <strong class="text-default">Overwrite</strong>
                    keeps your changes and replaces the file.
                </li>
            </ul>
        </template>

        <template #footer>
            <AppButton
                size="sm"
                variant="ghost"
                color="neutral"
                label="Cancel"
                data-testid="workspace-conflict-cancel"
                @click="workspaceStore.dismissConflict()"
            />
            <AppButton
                size="sm"
                variant="outline"
                color="neutral"
                label="Reload"
                data-testid="workspace-conflict-reload"
                @click="workspaceStore.resolveConflict('reload')"
            />
            <AppButton
                size="sm"
                color="error"
                label="Overwrite"
                data-testid="workspace-conflict-overwrite"
                @click="workspaceStore.resolveConflict('overwrite')"
            />
        </template>
    </AppDialog>
</template>
