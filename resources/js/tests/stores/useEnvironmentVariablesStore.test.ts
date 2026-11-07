import { ParameterType } from '@/interfaces/ui';
import { useEnvironmentVariablesStore } from '@/stores/core/useEnvironmentVariablesStore';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it } from 'vitest';

const setServerEnvironments = () => {
    window.Nimbus = {
        apiBaseUrl: 'http://app.test',
        environments: JSON.stringify([
            {
                id: 'current',
                name: 'Current host',
                baseUrl: 'http://app.test',
                enabled: true,
                source: 'current',
                detected: null,
            },
            {
                id: 'staging',
                name: 'Staging',
                baseUrl: 'https://staging.example.com',
                enabled: false,
                source: 'config',
            },
        ]),
    } as unknown as typeof window.Nimbus;
};

/** Adds a custom environment and makes it the active one. */
const addActive = (store: ReturnType<typeof useEnvironmentVariablesStore>) => {
    store.addEnvironment();
    store.select(store.editingEnvironment.id);
};

describe('useEnvironmentVariablesStore', () => {
    beforeEach(() => {
        setServerEnvironments();
        setActivePinia(createPinia());
    });

    describe('environments', () => {
        it('lists the current host first and keeps config environments disabled by default', () => {
            const store = useEnvironmentVariablesStore();

            expect(store.environments.map(env => env.id)).toEqual(['current', 'staging']);
            expect(store.enabledEnvironments.map(env => env.id)).toEqual(['current']);
            expect(store.activeEnvironment.id).toBe('current');
            expect(store.isCurrentHost).toBe(true);
            expect(store.baseUrl).toBe('http://app.test');
        });

        it('enables a config environment and switches to it', () => {
            const store = useEnvironmentVariablesStore();

            store.select('staging');
            expect(store.activeEnvironment.id).toBe('current');

            store.setEnabled('staging', true);
            store.select('staging');

            expect(store.activeEnvironment.id).toBe('staging');
            expect(store.isCurrentHost).toBe(false);
            expect(store.baseUrl).toBe('https://staging.example.com');
        });

        it('falls back to the current host when the active environment gets disabled', () => {
            const store = useEnvironmentVariablesStore();
            store.setEnabled('staging', true);
            store.select('staging');

            store.setEnabled('staging', false);

            expect(store.activeEnvironment.id).toBe('current');
        });

        it('never disables the current host', () => {
            const store = useEnvironmentVariablesStore();

            store.setEnabled('current', false);

            expect(store.environments[0].enabled).toBe(true);
        });

        it('stores variables of config environments as user overrides', () => {
            const store = useEnvironmentVariablesStore();
            store.edit('staging');

            store.updateVariables([
                {
                    id: 1,
                    type: ParameterType.Text,
                    key: 'a',
                    value: '1',
                    enabled: true,
                },
            ]);

            expect(store.environments[1].variables).toHaveLength(1);
            expect(store.environments[1].baseUrl).toBe('https://staging.example.com');
        });

        it('creates a custom environment with the current host base URL', () => {
            const store = useEnvironmentVariablesStore();

            store.addEnvironment();

            expect(store.customEnvironments).toHaveLength(1);
            expect(store.editingEnvironment.baseUrl).toBe('http://app.test');
            expect(store.editingEnvironment.source).toBe('custom');
            expect(store.editingEnvironment.variables).toHaveLength(1);
        });

        it('renames custom environments only', () => {
            const store = useEnvironmentVariablesStore();
            store.addEnvironment();
            store.renameEditing('Mine');
            expect(store.editingEnvironment.name).toBe('Mine');

            store.edit('staging');
            store.renameEditing('Hacked');
            expect(store.editingEnvironment.name).toBe('Staging');
        });

        it('validates the base URL of custom environments', () => {
            const store = useEnvironmentVariablesStore();
            store.addEnvironment();
            const id = store.editingEnvironment.id;

            expect(store.setBaseUrl(id, 'ftp://example.com')).toBe(false);
            expect(store.setBaseUrl(id, 'example.com')).toBe(false);
            expect(store.setBaseUrl(id, 'https://example.com/')).toBe(true);
            expect(store.editingEnvironment.baseUrl).toBe('https://example.com');
        });

        it('removes a custom environment and falls back to the current host', () => {
            const store = useEnvironmentVariablesStore();
            addActive(store);
            const id = store.activeEnvironment.id;

            store.removeEnvironment(id);

            expect(store.customEnvironments).toHaveLength(0);
            expect(store.activeEnvironment.id).toBe('current');
        });

        it('keeps at least one variable when the edited environment receives none', () => {
            const store = useEnvironmentVariablesStore();
            store.addEnvironment();

            store.updateVariables([]);

            expect(store.editingEnvironment.variables).toHaveLength(1);
            expect(store.editingEnvironment.variables[0].type).toBe(ParameterType.Text);
        });

        it('does not expose the base URL as a variable', () => {
            const store = useEnvironmentVariablesStore();

            expect(store.resolve('{{base_url}}/x')).toBe('{{base_url}}/x');
        });
    });

    describe('legacy migration', () => {
        const legacy = () => {
            const store = useEnvironmentVariablesStore();
            store.collections = [
                {
                    id: 'c1',
                    name: 'Old',
                    variables: [
                        {
                            id: 1,
                            type: ParameterType.Text,
                            key: 'k',
                            value: 'v',
                            enabled: true,
                        },
                    ],
                },
            ];
            store.activeCollectionId = 'c1';

            return store;
        };

        it('turns collections into custom environments and keeps the active one', () => {
            const store = legacy();

            store.migrateLegacyCollections();

            expect(store.customEnvironments).toHaveLength(1);
            expect(store.customEnvironments[0]).toMatchObject({
                id: 'c1',
                name: 'Old',
                baseUrl: 'http://app.test',
                source: 'custom',
            });
            expect(store.activeEnvironment.id).toBe('c1');
            expect(store.resolve('{{k}}')).toBe('v');
            expect(store.collections).toHaveLength(0);
        });

        it('is safe to run twice', () => {
            const store = legacy();

            store.migrateLegacyCollections();
            store.migrateLegacyCollections();

            expect(store.customEnvironments).toHaveLength(1);
        });

        it('keeps the current host active when no collection was active', () => {
            const store = legacy();
            store.activeCollectionId = null;

            store.migrateLegacyCollections();

            expect(store.activeEnvironment.id).toBe('current');
        });
    });

    describe('resolution', () => {
        it('resolves multiple environment variables in a single string', () => {
            const store = useEnvironmentVariablesStore();
            addActive(store);
            store.updateVariables([
                {
                    id: 1,
                    type: ParameterType.Text,
                    key: 'proto',
                    value: 'https',
                    enabled: true,
                },
                {
                    id: 2,
                    type: ParameterType.Text,
                    key: 'host',
                    value: 'nimbus.test',
                    enabled: true,
                },
            ]);

            expect(store.resolve('{{proto}}://{{host}}')).toBe('https://nimbus.test');
        });

        it('ignores disabled variables during resolution', () => {
            const store = useEnvironmentVariablesStore();
            addActive(store);
            store.updateVariables([
                {
                    id: 1,
                    type: ParameterType.Text,
                    key: 'api_key',
                    value: 'secret',
                    enabled: false,
                },
            ]);

            expect(store.resolve('Bearer {{api_key}}')).toBe('Bearer {{api_key}}');
            expect(store.check('api_key')).toBe('missing');
        });

        it('reactively updates resolution when variables change', () => {
            const store = useEnvironmentVariablesStore();
            addActive(store);
            const variables = [
                {
                    id: 1,
                    type: ParameterType.Text,
                    key: 'env',
                    value: 'staging',
                    enabled: true,
                },
            ];
            store.updateVariables(variables);

            expect(store.resolve('Current: {{env}}')).toBe('Current: staging');

            // Simulate update
            store.updateVariables([
                {
                    ...variables[0],
                    value: 'production',
                },
            ]);

            expect(store.resolve('Current: {{env}}')).toBe('Current: production');
        });

        it('handles whitespace within placeholders', () => {
            const store = useEnvironmentVariablesStore();
            addActive(store);
            store.updateVariables([
                {
                    id: 1,
                    type: ParameterType.Text,
                    key: 'token',
                    value: 'abc',
                    enabled: true,
                },
            ]);

            expect(store.resolve('{{  token  }}')).toBe('abc');
            expect(store.resolve('{{token}}')).toBe('abc');
        });

        it('updates resolution status when a variable becomes empty', () => {
            const store = useEnvironmentVariablesStore();
            addActive(store);
            const variables = [
                {
                    id: 1,
                    type: ParameterType.Text,
                    key: 'token',
                    value: 'secret',
                    enabled: true,
                },
            ];
            store.updateVariables(variables);

            expect(store.check('token')).toBe('resolved');

            store.updateVariables([
                {
                    ...variables[0],
                    value: '',
                },
            ]);

            expect(store.check('token ')).toBe('empty'); // Testing trim
        });

        it('resolves based on the active environment', () => {
            const store = useEnvironmentVariablesStore();
            const set = (value: string) =>
                store.updateVariables([
                    {
                        id: 1,
                        type: ParameterType.Text,
                        key: 'v',
                        value,
                        enabled: true,
                    },
                ]);

            addActive(store);
            const first = store.activeEnvironment.id;
            set('one');

            addActive(store);
            const second = store.activeEnvironment.id;
            set('two');

            store.select(first);
            expect(store.resolve('{{v}}')).toBe('one');

            store.select(second);
            expect(store.resolve('{{v}}')).toBe('two');

            store.select('current');
            expect(store.resolve('{{v}}')).toBe('{{v}}');
        });

        it('provides atomic enriched segments for mixed resolution states', () => {
            const store = useEnvironmentVariablesStore();
            addActive(store);
            store.updateVariables([
                {
                    id: 1,
                    type: ParameterType.Text,
                    key: 'ok',
                    value: 'yes',
                    enabled: true,
                },
                {
                    id: 2,
                    type: ParameterType.Text,
                    key: 'empty',
                    value: '',
                    enabled: true,
                },
            ]);

            const segments = store.getSegments('{{ok}} {{empty}} {{missing}}');

            expect(segments).toHaveLength(5); // 3 variables + 2 spaces

            // OK
            expect(segments[0]).toMatchObject({
                text: '{{ok}}',
                isEnvVariable: true,
                status: 'resolved',
                resolvedValue: 'yes',
            });

            // Space
            expect(segments[1].isEnvVariable).toBe(false);

            // Empty
            expect(segments[2]).toMatchObject({
                text: '{{empty}}',
                isEnvVariable: true,
                status: 'empty',
                resolvedValue: '',
            });

            // Missing
            expect(segments[4]).toMatchObject({
                text: '{{missing}}',
                isEnvVariable: true,
                status: 'missing',
                resolvedValue: null,
            });
        });

        describe('safety and edge cases', () => {
            it('handles null or undefined resolution inputs gracefully', () => {
                const store = useEnvironmentVariablesStore();

                expect(store.resolve(null)).toBe('');
                expect(store.resolve(undefined)).toBe('');
                expect(store.check(null)).toBe('none');
                expect(store.getSegments(null)).toEqual([]);
            });

            it('returns the placeholder if a variable is missing', () => {
                const store = useEnvironmentVariablesStore();
                expect(store.resolve('{{missing}}')).toBe('{{missing}}');
            });

            it('returns empty string if variable is present but its value is empty', () => {
                const store = useEnvironmentVariablesStore();
                addActive(store);
                store.updateVariables([
                    {
                        id: 1,
                        type: ParameterType.Text,
                        key: 'empty',
                        value: '',
                        enabled: true,
                    },
                ]);

                expect(store.resolve('{{empty}}')).toBe('');
            });
        });
    });

    describe('dynamic variables', () => {
        it('resolves a dynamic variable written with single braces', () => {
            const store = useEnvironmentVariablesStore();

            expect(store.resolve('{$uuid}')).toMatch(/^[0-9a-f-]{36}$/);
            expect(store.resolve('{$timestamp}')).toMatch(/^\d+$/);
            expect(store.resolve('{$unknown}')).toBe('{$unknown}');
        });

        it('does not treat the double brace syntax as a dynamic variable', () => {
            const store = useEnvironmentVariablesStore();

            expect(store.resolve('{{$uuid}}')).toBe('{{$uuid}}');
            expect(store.check('$uuid')).toBe('missing');
        });

        it('does not report a dynamic variable as an environment variable', () => {
            const store = useEnvironmentVariablesStore();

            const segments = store.getSegments('id-{$uuid}');

            expect(segments).toHaveLength(1);
            expect(segments[0].isEnvVariable).toBe(false);
        });

        it('resolves a dynamic variable once per send', () => {
            const store = useEnvironmentVariablesStore();
            const resolveForSend = store.createSendResolver();
            const [one, two] = resolveForSend('{$uuid}/{$uuid}').split('/');

            expect(one).toBe(two);
            expect(resolveForSend('{$uuid}')).toBe(one);
            expect(store.createSendResolver()('{$uuid}')).not.toBe(one);
        });

        it('keeps a variable named like a dynamic one separate', () => {
            const store = useEnvironmentVariablesStore();

            addActive(store);
            store.updateVariables([
                {
                    id: 1,
                    type: ParameterType.Text,
                    key: '$uuid',
                    value: 'fixed',
                    enabled: true,
                },
            ]);

            expect(store.resolve('{{$uuid}}')).toBe('fixed');
            expect(store.resolve('{$uuid}')).not.toBe('fixed');
        });
    });
});
