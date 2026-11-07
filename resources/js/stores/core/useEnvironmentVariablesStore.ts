import { resolveDynamicPlaceholders } from '@/config/dynamic-variables';
import {
    EnvVariableCheckStatus,
    PLACEHOLDER_PATTERN,
    type ResolverFn,
    type StringSegment,
} from '@/interfaces/common/env-vars';
import { EnvironmentId, EnvironmentSource } from '@/interfaces/generated';
import { type ParameterContract, ParameterType } from '@/interfaces/ui';
import { isValidBaseUrl, normalizeBaseUrl } from '@/utils/environment';
import { defineStore } from 'pinia';
import { v4 as uuidv4 } from 'uuid';
import type { ComputedRef } from 'vue';
import { computed, onMounted, ref, shallowRef } from 'vue';
import { useConfigStore } from './useConfigStore';

/*
 * Types & Interfaces.
 */

export type Environment = {
    id: string;
    name: string;
    baseUrl: string;
    source: EnvironmentSource;
    enabled: boolean;
    detected: string | null;
    variables: ParameterContract[];
};

/** User state kept for environments that come from the server. */
type EnvironmentOverride = {
    enabled?: boolean;
    variables?: ParameterContract[];
};

type LegacyCollection = {
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
         * Stores.
         */

        const configStore = useConfigStore();

        /*
         * State.
         */

        const customEnvironments = ref<Environment[]>([]);
        const overrides = ref<Record<string, EnvironmentOverride>>({});
        const activeEnvironmentId = shallowRef<string>(EnvironmentId.Current);
        const editingEnvironmentId = shallowRef<string | null>(null);
        const nextVariableId = shallowRef(0);
        const isRenamingEditingEnvironment = shallowRef<boolean>(false);

        // Legacy state, only read once by the migration.
        const collections = ref<LegacyCollection[]>([]);
        const activeCollectionId = shallowRef<string | null | undefined>(null);

        /*
         * Private Methods.
         */

        const generateVariableId = () => {
            nextVariableId.value += 1;

            return nextVariableId.value;
        };

        const createEmptyVariable = (): ParameterContract => ({
            id: generateVariableId(),
            type: ParameterType.Text,
            key: '',
            value: '',
            enabled: true,
        });

        /*
         * Getters (Computed).
         */

        const currentHostBaseUrl = computed(
            () =>
                configStore.environments.find(env => env.id === EnvironmentId.Current)
                    ?.baseUrl ?? configStore.apiUrl,
        );

        const environments = computed<Environment[]>(() => {
            const serverEnvironments = configStore.environments.length
                ? configStore.environments
                : [
                      {
                          id: EnvironmentId.Current,
                          name: 'Current host',
                          baseUrl: configStore.apiUrl,
                          enabled: true,
                          source: EnvironmentSource.Current,
                          detected: null,
                      },
                  ];

            const merged: Environment[] = serverEnvironments.map(env => {
                const override = overrides.value[env.id];

                return {
                    ...env,
                    enabled:
                        env.source === EnvironmentSource.Current
                            ? true
                            : (override?.enabled ?? env.enabled),
                    variables: override?.variables ?? [],
                };
            });

            return [...merged, ...customEnvironments.value];
        });

        const enabledEnvironments = computed(() =>
            environments.value.filter(env => env.enabled),
        );

        const currentHostEnvironment = computed(
            () =>
                environments.value.find(env => env.source === EnvironmentSource.Current)!,
        );

        // A missing or disabled active environment falls back to the current host.
        const activeEnvironment = computed<Environment>(
            () =>
                enabledEnvironments.value.find(
                    env => env.id === activeEnvironmentId.value,
                ) ?? currentHostEnvironment.value,
        );

        const isCurrentHost = computed(
            () => activeEnvironment.value.source === EnvironmentSource.Current,
        );

        const baseUrl = computed(() => normalizeBaseUrl(activeEnvironment.value.baseUrl));

        const editingEnvironment = computed<Environment>(
            () =>
                environments.value.find(env => env.id === editingEnvironmentId.value) ??
                activeEnvironment.value,
        );

        const editableVariables = computed(() => editingEnvironment.value.variables);

        const variables: ComputedRef<Map<string, string>> = computed(() => {
            const entries: [string, string][] = activeEnvironment.value.variables
                .filter(variable => variable.enabled && variable.key.trim() !== '')
                .map((parameter: ParameterContract) => [
                    parameter.key.trim(),
                    resolve(parameter.value),
                ]);

            return new Map(entries);
        });

        const variableNames = computed(() => [...variables.value.keys()]);

        /*
         * Actions.
         */

        const select = (environmentId: string) => {
            if (enabledEnvironments.value.some(env => env.id === environmentId)) {
                activeEnvironmentId.value = environmentId;
            }
        };

        const edit = (environmentId: string) => {
            editingEnvironmentId.value = environmentId;
        };

        const updateCustom = (id: string, patch: Partial<Environment>) => {
            customEnvironments.value = customEnvironments.value.map(env =>
                env.id === id ? { ...env, ...patch } : env,
            );
        };

        const addEnvironment = () => {
            const environment: Environment = {
                id: uuidv4(),
                name: `Environment ${customEnvironments.value.length + 1}`,
                baseUrl: currentHostBaseUrl.value,
                source: EnvironmentSource.Custom,
                enabled: true,
                detected: null,
                variables: [createEmptyVariable()],
            };

            customEnvironments.value.push(environment);
            editingEnvironmentId.value = environment.id;
            isRenamingEditingEnvironment.value = true;
        };

        const removeEnvironment = (environmentId: string) => {
            customEnvironments.value = customEnvironments.value.filter(
                env => env.id !== environmentId,
            );

            if (editingEnvironmentId.value === environmentId) {
                editingEnvironmentId.value = null;
            }

            if (activeEnvironmentId.value === environmentId) {
                activeEnvironmentId.value = EnvironmentId.Current;
            }
        };

        const renameEditing = (name: string) => {
            if (editingEnvironment.value.source === EnvironmentSource.Custom) {
                updateCustom(editingEnvironment.value.id, { name });
            }

            completeRenaming();
        };

        /**
         * Updates the base URL of a custom environment. Returns false for invalid URLs.
         */
        const setBaseUrl = (environmentId: string, url: string): boolean => {
            const target = customEnvironments.value.find(env => env.id === environmentId);

            if (!target || !isValidBaseUrl(url)) {
                return false;
            }

            updateCustom(environmentId, { baseUrl: normalizeBaseUrl(url) });

            return true;
        };

        const setEnabled = (environmentId: string, enabled: boolean) => {
            const target = environments.value.find(env => env.id === environmentId);

            if (!target || target.source === EnvironmentSource.Current) {
                return;
            }

            if (target.source === EnvironmentSource.Custom) {
                updateCustom(environmentId, { enabled });
            } else {
                overrides.value[environmentId] = {
                    ...overrides.value[environmentId],
                    enabled,
                };
            }
        };

        const updateVariables = (newVariables: ParameterContract[]) => {
            const target = editingEnvironment.value;
            const normalized = newVariables.length
                ? newVariables
                : [createEmptyVariable()];

            if (target.source === EnvironmentSource.Custom) {
                updateCustom(target.id, { variables: normalized });

                return;
            }

            overrides.value[target.id] = {
                ...overrides.value[target.id],
                variables: normalized,
            };
        };

        const completeRenaming = () => {
            isRenamingEditingEnvironment.value = false;
        };

        /**
         * Turns persisted collections into custom environments. Running it twice changes nothing.
         */
        const migrateLegacyCollections = () => {
            if (!collections.value.length) {
                return;
            }

            const knownIds = new Set(customEnvironments.value.map(env => env.id));

            collections.value
                .filter(collection => !knownIds.has(collection.id))
                .forEach(collection =>
                    customEnvironments.value.push({
                        id: collection.id,
                        name: collection.name,
                        baseUrl: currentHostBaseUrl.value,
                        source: EnvironmentSource.Custom,
                        enabled: true,
                        detected: null,
                        variables: collection.variables,
                    }),
                );

            if (
                activeCollectionId.value &&
                customEnvironments.value.some(env => env.id === activeCollectionId.value)
            ) {
                activeEnvironmentId.value = activeCollectionId.value;
            }

            collections.value = [];
            activeCollectionId.value = null;
        };

        /**
         * Resolves the dynamic variables and the environment variable placeholders in a string.
         */
        const resolveWith = (
            value: string | number | boolean | null | undefined,
            dynamicCache: Map<string, string> | null,
        ): string => {
            if (value === null || value === undefined) {
                return '';
            }

            if (typeof value !== 'string') {
                return String(value);
            }

            const withDynamicValues = resolveDynamicPlaceholders(value, dynamicCache);

            if (!withDynamicValues.includes('{{')) {
                return withDynamicValues;
            }

            return withDynamicValues.replace(PLACEHOLDER_PATTERN, (match, key) => {
                const normalizedKey = String(key).trim();

                if (!variables.value.has(normalizedKey)) {
                    return match;
                }

                return variables.value.get(normalizedKey) ?? '';
            });
        };

        const resolve = (value: string | number | boolean | null | undefined): string =>
            resolveWith(value, null);

        /**
         * Creates a resolver for one send, so a dynamic variable has one value in the whole request.
         */
        const createSendResolver = (): ResolverFn => {
            const dynamicCache = new Map<string, string>();

            return value => resolveWith(value, dynamicCache);
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

        onMounted(() => (isRenamingEditingEnvironment.value = false));

        return {
            // State
            customEnvironments,
            overrides,
            activeEnvironmentId,
            editingEnvironmentId,
            isRenamingEditingEnvironment,
            nextVariableId,
            collections,
            activeCollectionId,

            // Getters
            environments,
            enabledEnvironments,
            activeEnvironment,
            editingEnvironment,
            isCurrentHost,
            baseUrl,
            editableVariables,
            variables,
            variableNames,

            // Actions
            select,
            edit,
            addEnvironment,
            removeEnvironment,
            renameEditing,
            setBaseUrl,
            setEnabled,
            updateVariables,
            completeRenaming,
            migrateLegacyCollections,
            resolve,
            createSendResolver,
            check,
            getSegments,
        };
    },
    {
        persist: {
            pick: [
                'customEnvironments',
                'overrides',
                'activeEnvironmentId',
                'nextVariableId',
                'collections',
                'activeCollectionId',
            ],
            afterHydrate: context => {
                context.store.migrateLegacyCollections();
            },
        },
    },
);

/*
 * Helpers.
 */

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
        const index = match.index;

        if (index === undefined) {
            continue;
        }

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
