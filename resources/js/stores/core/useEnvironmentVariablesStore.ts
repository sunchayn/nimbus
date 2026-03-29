import {
    EnvVariableCheckStatus,
    PLACEHOLDER_PATTERN,
    type StringSegment,
} from '@/interfaces/common/env-vars';
import { type ParameterContract, ParameterType } from '@/interfaces/ui';
import { defineStore } from 'pinia';
import type { ComputedRef, Ref } from 'vue';
import { computed, onMounted, ref } from 'vue';

/*
 * Types & Interfaces.
 */

export type EnvironmentCollection = {
    id: string;
    name: string;
    variables: ParameterContract[];
};

export { EnvVariableCheckStatus, PLACEHOLDER_PATTERN, type StringSegment };

/*
 * Store Definition.
 */

export const useEnvironmentVariablesStore = defineStore(
    'environmentVariables',
    () => {
        /*
         * State.
         */
        const collections: Ref<EnvironmentCollection[]> = ref<EnvironmentCollection[]>(
            [],
        );
        const activeCollectionId = ref<string | null | undefined>(null);
        const nextVariableId = ref(0);
        const isRenamingActiveCollection = ref<boolean>(false);

        /*
         * Private Methods.
         */

        const generateVariableId = () => {
            nextVariableId.value += 1;

            return nextVariableId.value;
        };

        const createEmptyCollectionVariable = (): ParameterContract => ({
            id: generateVariableId(),
            type: ParameterType.Text,
            key: '',
            value: '',
            enabled: true,
        });

        /*
         * Getters (Computed).
         */

        const activeCollection = computed(() => {
            return (
                collections.value.find(
                    collection => collection.id === activeCollectionId.value,
                ) ?? null
            );
        });

        const editableVariables = computed(() => activeCollection.value?.variables ?? []);

        const variables: ComputedRef<Map<string, string>> = computed(() => {
            const variables: [string, string][] = editableVariables.value
                .filter(variable => variable.enabled && variable.key.trim() !== '')
                .map((parameter: ParameterContract) => [
                    parameter.key.trim(),
                    resolve(parameter.value),
                ]);

            return new Map(variables);
        });

        const hasCollections = computed(() => collections.value.length > 0);

        /*
         * Actions.
         */

        const select = (collectionId: string | null | undefined) => {
            activeCollectionId.value = collectionId;
        };

        const addCollection = () => {
            const collection = createDefaultCollection(collections.value.length + 1);
            collection.variables = [createEmptyCollectionVariable()];

            collections.value.push(collection);
            activeCollectionId.value = collection.id;

            isRenamingActiveCollection.value = true;
        };

        const removeCollection = (collectionId: string) => {
            collections.value = collections.value.filter(
                collection => collection.id !== collectionId,
            );

            if (collections.value.length === 0) {
                activeCollectionId.value = null;

                return;
            }

            if (activeCollectionId.value === collectionId) {
                activeCollectionId.value = collections.value[0]?.id ?? null;
            }
        };

        const renameActive = (name: string) => {
            if (!activeCollection.value) {
                return;
            }

            collections.value = collections.value.map(collection => {
                if (collection.id !== activeCollection.value!.id) {
                    return collection;
                }

                return {
                    ...collection,
                    name,
                };
            });

            completeRenaming();
        };

        const updateVariables = (variables: ParameterContract[]) => {
            if (!activeCollection.value) {
                return;
            }

            const normalizedVariables = variables.length
                ? variables
                : [createEmptyCollectionVariable()];

            collections.value = collections.value.map(collection => {
                if (collection.id !== activeCollection.value!.id) {
                    return collection;
                }

                return {
                    ...collection,
                    variables: normalizedVariables,
                };
            });
        };

        const completeRenaming = () => {
            isRenamingActiveCollection.value = false;
        };

        /**
         * Resolves all environment variable placeholders in a string.
         */
        const resolve = (value: string | number | boolean | null | undefined): string => {
            if (value === null || value === undefined) {
                return '';
            }

            if (typeof value !== 'string') {
                return String(value);
            }

            if (!value.includes('{{')) {
                return value;
            }

            return value.replace(PLACEHOLDER_PATTERN, (match, key) => {
                const normalizedKey = String(key).trim();

                if (!variables.value.has(normalizedKey)) {
                    return match;
                }

                return variables.value.get(normalizedKey) ?? '';
            });
        };

        /**
         * Checks the status of a specific environment variable key.
         */
        const check = (key: string | null | undefined): EnvVariableCheckStatus => {
            if (!key) {
                return EnvVariableCheckStatus.None;
            }

            const normalizedKey = key.trim();

            if (!variables.value.has(normalizedKey)) {
                return EnvVariableCheckStatus.Missing;
            }

            if ((variables.value.get(normalizedKey) ?? '') === '') {
                return EnvVariableCheckStatus.Empty;
            }

            return EnvVariableCheckStatus.Resolved;
        };

        /**
         * Parses a string into segments with their resolution status and values.
         */
        const getSegments = (value: string | null | undefined): StringSegment[] => {
            if (!value) {
                return [];
            }

            return getStringSegments(value, variables.value);
        };

        /*
         * Lifecycle.
         */

        onMounted(() => (isRenamingActiveCollection.value = false));

        return {
            // State
            collections,
            activeCollectionId,
            isRenamingActiveCollection,

            // Getters
            activeCollection,
            editableVariables,
            variables,
            hasCollections,

            // Actions
            select,
            addCollection,
            removeCollection,
            renameActive,
            updateVariables,
            completeRenaming,
            resolve,
            check,
            getSegments,
        };
    },
    {
        persist: true,
    },
);

/*
 * Helpers.
 */

function createDefaultCollection(index: number): EnvironmentCollection {
    return {
        id: crypto.randomUUID(),
        name: `Collection ${index}`,
        variables: [],
    };
}

/**
 * Parses a string into segments with their resolution status and values.
 */
function getStringSegments(
    value: string,
    variables: Map<string, string>,
): StringSegment[] {
    const segments: StringSegment[] = [];

    let lastIndex = 0;

    const matches = Array.from(value.matchAll(PLACEHOLDER_PATTERN));

    for (const match of matches) {
        const index = match.index!;

        if (index > lastIndex) {
            segments.push({
                text: value.substring(lastIndex, index),
                isEnvVariable: false,
                status: EnvVariableCheckStatus.None,
                resolvedValue: null,
            });
        }

        const text = match[0];
        const key = match[1].trim();

        let status = EnvVariableCheckStatus.Missing;
        let resolvedValue = null;

        if (variables.has(key)) {
            resolvedValue = variables.get(key) ?? '';
            status =
                resolvedValue === ''
                    ? EnvVariableCheckStatus.Empty
                    : EnvVariableCheckStatus.Resolved;
        }

        segments.push({
            text,
            isEnvVariable: true,
            status,
            resolvedValue,
        });

        lastIndex = index + match[0].length;
    }

    if (lastIndex < value.length) {
        segments.push({
            text: value.substring(lastIndex),
            isEnvVariable: false,
            status: EnvVariableCheckStatus.None,
            resolvedValue: null,
        });
    }

    return segments;
}
