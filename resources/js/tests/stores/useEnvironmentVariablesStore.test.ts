import { ParameterType } from '@/interfaces/ui';
import { useEnvironmentVariablesStore } from '@/stores/core/useEnvironmentVariablesStore';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it } from 'vitest';

describe('useEnvironmentVariablesStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
    });

    it('creates a collection with one empty variable and sets it active', () => {
        const store = useEnvironmentVariablesStore();

        store.addCollection();

        expect(store.collections).toHaveLength(1);
        expect(store.activeCollectionId).toBe(store.collections[0].id);
        expect(store.collections[0].variables).toHaveLength(1);
        expect(store.collections[0].variables[0]).toMatchObject({
            type: ParameterType.Text,
            key: '',
            value: '',
            enabled: true,
        });
    });

    it('updates active collection name through action', () => {
        const store = useEnvironmentVariablesStore();
        store.addCollection();

        store.renameActive('Local API');

        expect(store.activeCollection?.name).toBe('Local API');
    });

    it('keeps at least one variable when active collection receives empty variables', () => {
        const store = useEnvironmentVariablesStore();
        store.addCollection();

        store.updateVariables([]);

        expect(store.activeCollection?.variables.length).toBe(1);
        expect(store.activeCollection?.variables[0].type).toBe(ParameterType.Text);
    });

    it('clears active collection when all collections are removed', () => {
        const store = useEnvironmentVariablesStore();
        store.addCollection();
        const id = store.collections[0].id;

        store.removeCollection(id);

        expect(store.collections).toHaveLength(0);
        expect(store.activeCollection).toBeNull();
        expect(store.activeCollectionId).toBeNull();
    });

    describe('resolution', () => {
        it('resolves multiple environment variables in a single string', () => {
            const store = useEnvironmentVariablesStore();
            store.addCollection();
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
            store.addCollection();
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
            store.addCollection();
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
            store.addCollection();
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
            store.addCollection();
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

        it('resolves based on the active collection', () => {
            const store = useEnvironmentVariablesStore();

            // Collection 1
            store.addCollection();
            const col1 = store.collections[0].id;
            store.updateVariables([
                {
                    id: 1,
                    type: ParameterType.Text,
                    key: 'v',
                    value: 'one',
                    enabled: true,
                },
            ]);

            // Collection 2
            store.addCollection();
            const col2 = store.collections[1].id;
            store.updateVariables([
                {
                    id: 2,
                    type: ParameterType.Text,
                    key: 'v',
                    value: 'two',
                    enabled: true,
                },
            ]);

            store.select(col1);
            expect(store.resolve('{{v}}')).toBe('one');

            store.select(col2);
            expect(store.resolve('{{v}}')).toBe('two');

            store.select(null);
            expect(store.resolve('{{v}}')).toBe('{{v}}');
        });

        it('provides atomic enriched segments for mixed resolution states', () => {
            const store = useEnvironmentVariablesStore();
            store.addCollection();
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
                store.addCollection();
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
});
