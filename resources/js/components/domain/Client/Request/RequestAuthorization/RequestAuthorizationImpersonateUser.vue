<script setup lang="ts">
/**
 * @component RequestAuthorizationImpersonateUser
 * @description Guard select and user picker to impersonate as authentication.
 * The key can still be typed, so any key type is accepted.
 */
import { AppButton } from '@/components/base/button';
import { AppIcon } from '@/components/base/icons';
import { AppPopover } from '@/components/base/popover';
import { AppSelect } from '@/components/base/select';
import GeneratorInput from '@/components/common/GeneratorInput.vue';
import { useActors, type Actor } from '@/composables/request/useActors';
import { useConfigStore } from '@/stores';
import { useDebounceFn } from '@vueuse/core';
import {
    computed,
    nextTick,
    onMounted,
    shallowRef,
    useTemplateRef,
    watch,
    type ModelRef,
} from 'vue';

/*
 * Types & Interfaces.
 */

export interface RequestAuthorizationImpersonateUserProps {
    guard?: string;
    tokensOnly?: boolean;
}

/*
 * Component Setup.
 */

const props = defineProps<RequestAuthorizationImpersonateUserProps>();

const emit = defineEmits<{
    'update:guard': [value: string];
}>();

const model: ModelRef<number | string> = defineModel<number | string>({
    default: () => '',
});

const { actors, isLoading, hasFailed, search } = useActors();
const configStore = useConfigStore();

/*
 * State.
 */

const isOpen = shallowRef(false);
const query = shallowRef('');
const searchInputRef =
    useTemplateRef<InstanceType<typeof GeneratorInput>>('searchInputRef');

/*
 * Computed & Methods.
 */

// The guards are provided on page load. With tokens only, they are the ones that can create tokens.
const availableGuards = computed(() =>
    props.tokensOnly ? configStore.tokenGuards : configStore.guards,
);

const effectiveGuard = computed(() => {
    if (!props.tokensOnly) {
        return props.guard ?? configStore.defaultGuard ?? undefined;
    }

    return props.guard && availableGuards.value.includes(props.guard)
        ? props.guard
        : availableGuards.value[0];
});

const selectedGuard = computed(() => effectiveGuard.value);

const hasNoTokenGuard = computed(
    () => props.tokensOnly && configStore.tokenGuards.length === 0,
);

const runSearch = () => {
    if (!hasNoTokenGuard.value) {
        search(effectiveGuard.value, query.value.trim());
    }
};
const runSearchDebounced = useDebounceFn(runSearch, 250);

const pick = (actor: Actor) => {
    model.value = actor.id;
    isOpen.value = false;
};

onMounted(runSearch);

watch(isOpen, async open => {
    if (open) {
        runSearch();
        await nextTick();
        searchInputRef.value?.inputRef?.focus();
    } else {
        query.value = '';
    }
});

watch(query, runSearchDebounced);

// The list belongs to one guard, so a guard change loads it again.
watch(effectiveGuard, runSearch);

// The guard that is shown must be the one that is sent.
watch(
    effectiveGuard,
    guard => {
        if (props.tokensOnly && guard && guard !== props.guard) {
            emit('update:guard', guard);
        }
    },
    { immediate: true },
);
</script>

<template>
    <div>
        <div
            v-if="availableGuards.length > 1"
            class="h-control-lg grid grid-cols-3 border-b"
        >
            <label
                class="px-panel h-control-lg py-tight flex items-center border-r text-xs"
                for="actor-guard"
            >
                Guard
            </label>
            <AppSelect
                id="actor-guard"
                :model-value="selectedGuard"
                :items="availableGuards"
                variant="none"
                size="sm"
                class="col-span-2 h-full"
                data-testid="actor-guard-select"
                @update:model-value="value => emit('update:guard', String(value))"
            />
        </div>
        <div class="h-control-lg grid grid-cols-3 border-b">
            <label
                class="px-panel h-control-lg py-tight flex items-center border-r text-xs"
                for="user-id"
            >
                User
            </label>
            <div class="col-span-2 flex h-full items-center">
                <GeneratorInput
                    id="user-id"
                    v-model="model"
                    placeholder="Pick a user, or type an ID, UUID, or ULID."
                    variant="none"
                    class="h-full flex-1"
                    :ui="{ base: 'text-xs' }"
                />
                <AppPopover
                    v-model:open="isOpen"
                    :content="{ align: 'end', sideOffset: 12, collisionPadding: 8 }"
                    :ui="{
                        content:
                            'flex w-screen max-h-(--reka-popover-content-available-height) max-w-popover flex-col p-0',
                    }"
                >
                    <AppButton
                        variant="ghost"
                        color="neutral"
                        size="sm"
                        icon="i-hugeicons-search-01"
                        aria-label="Pick a user"
                        :loading="isLoading"
                        data-testid="actor-picker-button"
                        square
                    />

                    <template #content>
                        <div
                            class="border-default gap-tight p-tight flex items-center border-b"
                        >
                            <GeneratorInput
                                ref="searchInputRef"
                                v-model="query"
                                variant="none"
                                size="sm"
                                class="min-w-0 flex-1"
                                leading-icon="i-hugeicons-search-01"
                                placeholder="Search by ID, name, or email..."
                                data-testid="actor-search-input"
                            />
                        </div>

                        <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain">
                            <div
                                v-if="hasFailed"
                                class="text-muted gap-base flex flex-col items-center p-6 text-center text-sm"
                                data-testid="actor-error"
                            >
                                <p>The users could not be loaded.</p>
                            </div>
                            <div
                                v-else-if="!isLoading && actors.length === 0"
                                class="text-muted gap-base flex flex-col items-center p-6 text-center text-sm"
                                data-testid="actor-empty"
                            >
                                <AppIcon
                                    name="i-hugeicons-search-01"
                                    class="size-8 opacity-20"
                                />
                                <p>No users found.</p>
                            </div>
                            <ul v-else class="divide-default divide-y">
                                <li v-for="actor in actors" :key="actor.id">
                                    <button
                                        type="button"
                                        class="px-panel py-tight hover:bg-elevated flex w-full items-center justify-between text-left text-xs"
                                        data-testid="actor-option"
                                        @click="pick(actor)"
                                    >
                                        <span class="truncate">{{ actor.label }}</span>
                                        <span class="text-muted ml-base shrink-0">
                                            {{ actor.id }}
                                        </span>
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </template>
                </AppPopover>
            </div>
        </div>
    </div>
</template>
