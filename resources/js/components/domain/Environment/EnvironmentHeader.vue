<script setup lang="ts">
/**
 * @component EnvironmentHeader
 * @description Header for the environment being edited, featuring inline renaming and management actions.
 */
import { AppButton } from '@/components/base/button';
import { AppEditable } from '@/components/base/editable';
import AppSwapIcon from '@/components/base/icons/AppSwapIcon.vue';
import { AppTooltip } from '@/components/base/tooltip';
import { useConfirmationAction } from '@/composables/ui/useConfirmationAction';
import { EnvironmentSource } from '@/interfaces/generated';
import { useEnvironmentVariablesStore } from '@/stores';
import { computed, nextTick, shallowRef, useTemplateRef, watch } from 'vue';

/*
 * Stores & Dependencies.
 */

const environmentVariablesStore = useEnvironmentVariablesStore();

/*
 * State.
 */

const editingEnvironmentName = shallowRef('');

const environmentNameEditor = useTemplateRef<InstanceType<typeof AppEditable>>(
    'environmentNameEditor',
);

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

const isCustom = computed(
    () =>
        environmentVariablesStore.editingEnvironment.source === EnvironmentSource.Custom,
);

const triggerNameEdit = async () => {
    if (!isCustom.value) {
        return;
    }

    editingEnvironmentName.value = environmentVariablesStore.isRenamingEditingEnvironment
        ? ''
        : environmentVariablesStore.editingEnvironment.name;

    nextTick(() => {
        environmentNameEditor.value?.focus();
    });
};

const submitName = () => {
    if (editingEnvironmentName.value.trim()) {
        environmentVariablesStore.renameEditing(editingEnvironmentName.value);
    }

    environmentVariablesStore.completeRenaming();
};

const handleRemoval = () => {
    if (!isCustom.value) {
        return;
    }

    triggerRemovalConfirmation(() => {
        environmentVariablesStore.removeEnvironment(
            environmentVariablesStore.editingEnvironment.id,
        );
    });
};

/*
 * Watchers.
 */

watch(
    [
        () => environmentVariablesStore.editingEnvironment.id,
        () => environmentVariablesStore.isRenamingEditingEnvironment,
    ],
    ([newId, picking], [oldId]) => {
        if (newId !== oldId) {
            editingEnvironmentName.value =
                environmentVariablesStore.editingEnvironment.name;
            cancelRemovalConfirmation();
        }

        if (picking) {
            nextTick(() => triggerNameEdit());
        }
    },
    { immediate: true },
);
</script>

<template>
    <div
        class="h-toolbar bg-muted px-panel gap-tight relative flex w-full items-center justify-between"
    >
        <div class="flex min-w-0 flex-1 items-center">
            <AppEditable
                v-if="isCustom"
                ref="environmentNameEditor"
                v-model="editingEnvironmentName"
                placeholder="Environment name..."
                data-testid="environment-name-editor"
                @submit="submitName"
            >
                {{ environmentVariablesStore.editingEnvironment.name }}
            </AppEditable>
            <span v-else class="text-xs font-medium" data-testid="environment-name">
                {{ environmentVariablesStore.editingEnvironment.name }}
            </span>
        </div>

        <!-- Management Actions -->
        <div v-if="isCustom" class="gap-tight flex items-center">
            <AppTooltip text="Rename">
                <AppButton
                    size="xs"
                    variant="outline"
                    icon="i-hugeicons-folder-edit"
                    aria-label="Rename environment"
                    square
                    :disabled="!isCustom"
                    @click="triggerNameEdit"
                />
            </AppTooltip>

            <AppTooltip text="Delete">
                <AppButton
                    size="xs"
                    variant="outline"
                    :aria-label="
                        isConfirmingRemoval()
                            ? 'Confirm delete environment'
                            : 'Delete environment'
                    "
                    square
                    :color="isConfirmingRemoval() ? 'error' : undefined"
                    :disabled="!isCustom"
                    @click="handleRemoval"
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
