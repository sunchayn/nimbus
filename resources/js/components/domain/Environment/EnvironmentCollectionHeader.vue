<script setup lang="ts">
/**
 * @component EnvironmentCollectionHeader
 * @description Header for the active environment collection, featuring inline renaming and management actions.
 */
import { AppButton } from '@/components/base/button';
import { AppEditable } from '@/components/base/editable';
import AppSwapIcon from '@/components/base/icons/AppSwapIcon.vue';
import { AppTooltip } from '@/components/base/tooltip';
import { useConfirmationAction } from '@/composables/ui/useConfirmationAction';
import { useEnvironmentVariablesStore } from '@/stores';
import { nextTick, shallowRef, useTemplateRef, watch } from 'vue';

/*
 * Stores & Dependencies.
 */

const environmentVariablesStore = useEnvironmentVariablesStore();

/*
 * State.
 */

const editingCollectionName = shallowRef('');

const collectionNameEditor =
    useTemplateRef<InstanceType<typeof AppEditable>>('collectionNameEditor');

/*
 * Composables.
 */

const {
    isConfirming: isConfirmingRemoval,
    trigger: triggerRemovalConfirmation,
    cancel: cancelRemovalConfirmation,
} = useConfirmationAction({
    duration: 3000,
});

/*
 * Computed & Methods.
 */

const triggerCollectionNameEdit = async () => {
    if (!environmentVariablesStore.activeCollection) {
        return;
    }

    editingCollectionName.value = environmentVariablesStore.isRenamingActiveCollection
        ? ''
        : environmentVariablesStore.activeCollection.name;

    nextTick(() => {
        collectionNameEditor.value?.focus();
    });
};

const submitCollectionName = () => {
    if (editingCollectionName.value.trim()) {
        environmentVariablesStore.renameActive(editingCollectionName.value);
    }

    environmentVariablesStore.completeRenaming();
};

const handleCollectionRemoval = () => {
    if (!environmentVariablesStore.activeCollectionId) {
        return;
    }

    triggerRemovalConfirmation(() => {
        const collectionId = environmentVariablesStore.activeCollectionId;

        if (collectionId) {
            environmentVariablesStore.removeCollection(collectionId);
        }
    });
};

/*
 * Watchers.
 */

watch(
    [
        () => environmentVariablesStore.activeCollectionId,
        () => environmentVariablesStore.isRenamingActiveCollection,
    ],
    ([newId, picking], [oldId]) => {
        if (newId !== oldId) {
            editingCollectionName.value =
                environmentVariablesStore.activeCollection?.name ?? '';
            cancelRemovalConfirmation();
        }

        if (picking) {
            nextTick(() => triggerCollectionNameEdit());
        }
    },
    { immediate: true },
);
</script>

<template>
    <div
        class="h-toolbar bg-muted px-panel relative flex w-full items-center justify-between gap-1"
    >
        <div class="flex min-w-0 flex-1 items-center">
            <AppEditable
                v-if="environmentVariablesStore.activeCollection"
                ref="collectionNameEditor"
                v-model="editingCollectionName"
                placeholder="Collection name..."
                data-testid="collection-name-editor"
                @submit="submitCollectionName"
            >
                {{ environmentVariablesStore.activeCollection.name }}
            </AppEditable>
            <span v-else class="text-muted text-xs">[Collection name]</span>
        </div>

        <!-- Management Actions -->
        <div class="flex items-center gap-1">
            <AppTooltip text="Rename">
                <AppButton
                    size="xs"
                    variant="outline"
                    icon="i-hugeicons-folder-edit"
                    aria-label="Rename collection"
                    square
                    :disabled="!environmentVariablesStore.activeCollection"
                    @click="triggerCollectionNameEdit"
                />
            </AppTooltip>

            <AppTooltip text="Delete">
                <AppButton
                    size="xs"
                    variant="outline"
                    :aria-label="
                        isConfirmingRemoval()
                            ? 'Confirm delete collection'
                            : 'Delete collection'
                    "
                    square
                    :color="isConfirmingRemoval() ? 'error' : undefined"
                    :disabled="!environmentVariablesStore.activeCollection"
                    @click="handleCollectionRemoval"
                >
                    <template #leading>
                        <AppSwapIcon
                            :name="
                                isConfirmingRemoval()
                                    ? 'i-hugeicons-tick-02'
                                    : 'i-hugeicons-delete-02'
                            "
                            class="size-icon-sm"
                        />
                    </template>
                </AppButton>
            </AppTooltip>
        </div>
    </div>
</template>
